<?php

namespace Luoyue\WebmanMcp\Command;

use InvalidArgumentException;
use Luoyue\WebmanMcp\Command\Trait\RegistryAccessTrait;
use Luoyue\WebmanMcp\McpServerManager;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Capability\Registry\ReferenceHandler;
use Mcp\Exception\ToolNotFoundException;
use Mcp\Schema\Content\AudioContent;
use Mcp\Schema\Content\Content;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;
use Psr\Container\ContainerInterface;
use support\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('mcp:tools:call', 'Execute a service tool with JSON parameters')]
final class McpToolsCallCommand extends Command
{
    use RegistryAccessTrait;

    public function configure(): void
    {
        $this
            ->addArgument('service', InputArgument::REQUIRED, 'Service name')
            ->addArgument('tool-name', InputArgument::REQUIRED, 'Name of the tool to execute')
            ->addArgument('json-input', InputArgument::OPTIONAL, 'JSON object with tool parameters', '{}')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (pretty, json)', 'pretty');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $service = (string) $input->getArgument('service');
        $toolName = (string) $input->getArgument('tool-name');
        $jsonInput = (string) $input->getArgument('json-input');
        $format = (string) $input->getOption('format');

        if (!in_array($format, ['pretty', 'json'], true)) {
            return $this->failure($io, $output, $format, sprintf('Unsupported format: %s', $format));
        }

        $params = json_decode($jsonInput, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            return $this->failure($io, $output, $format, sprintf('Invalid JSON: %s', json_last_error_msg()));
        }
        if (!is_array($params)) {
            return $this->failure($io, $output, $format, 'JSON input must be an object');
        }
        if ([] !== $params && array_is_list($params)) {
            return $this->failure($io, $output, $format, 'JSON input must be an object, not a JSON array');
        }

        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        try {
            $registry = $this->getRegistry($mcpServerManager, $service);
        } catch (InvalidArgumentException $e) {
            return $this->failure($io, $output, $format, $e->getMessage());
        }

        try {
            $reference = $registry->getTool($toolName);
        } catch (ToolNotFoundException $e) {
            return $this->failure(
                $io,
                $output,
                $format,
                sprintf('Tool "%s" not found', $toolName),
                sprintf('Use "webman mcp:tools %s" to see all available tools', $service)
            );
        }

        $validationErrors = (new SchemaValidator())->validateAgainstJsonSchema($params, $reference->tool->inputSchema);
        if (!empty($validationErrors)) {
            $messages = array_map(
                static fn (array $error) => $error['message'],
                array_slice($validationErrors, 0, 3)
            );

            return $this->failure(
                $io,
                $output,
                $format,
                sprintf('Invalid parameters for tool "%s": %s', $toolName, implode('; ', $messages))
            );
        }

        $session = new Session(new InMemorySessionStore());
        $request = new CallToolRequest(name: $toolName, arguments: $params);

        $arguments = $params;
        $arguments['_session'] = $session;
        $arguments['_request'] = $request;

        if ('pretty' === $format) {
            $io->title(sprintf('Executing Tool: %s', $toolName));
            if ($reference->tool->description) {
                $io->text($reference->tool->description);
            }
            $io->newLine();
        }

        try {
            $container = Container::instance();
            \assert($container instanceof ContainerInterface);
            $result = (new ReferenceHandler($container))->handle($reference, $arguments);
        } catch (\Throwable $e) {
            return $this->failure($io, $output, $format, sprintf('Error: %s', $e->getMessage()));
        }

        if ('json' === $format) {
            $output->writeln($this->encodeJson($result));
        } else {
            $io->section('Result');
            $this->renderPretty($result, $io);
        }

        return Command::SUCCESS;
    }

    private function failure(
        SymfonyStyle $io,
        OutputInterface $output,
        string $format,
        string $message,
        ?string $note = null,
    ): int {
        if ('json' === $format) {
            $payload = ['error' => $message];
            if (null !== $note) {
                $payload['note'] = $note;
            }
            $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return Command::FAILURE;
        }

        $io->error($message);
        if (null !== $note) {
            $io->note($note);
        }

        return Command::FAILURE;
    }

    private function renderPretty(mixed $result, SymfonyStyle $io): void
    {
        if ($result instanceof CallToolResult) {
            foreach ($result->content as $item) {
                $io->text($this->formatContent($item));
            }

            return;
        }

        if ($result instanceof Content) {
            $io->text($this->formatContent($result));

            return;
        }

        if (is_array($result)) {
            if (array_is_list($result)) {
                foreach ($result as $item) {
                    $io->text($this->formatValue($item));
                }
            } else {
                $io->definitionList(...array_map(
                    fn ($key, $value) => [$key => $this->formatValue($value)],
                    array_keys($result),
                    $result
                ));
            }

            return;
        }

        if (is_string($result)) {
            $io->text($result);

            return;
        }

        if (is_bool($result)) {
            $io->text($result ? 'true' : 'false');

            return;
        }

        if (null === $result) {
            $io->text('<comment>null</comment>');

            return;
        }

        $io->text((string) $result);
    }

    private function formatContent(Content $content): string
    {
        if ($content instanceof TextContent) {
            return (string) $content->text;
        }
        if ($content instanceof ImageContent) {
            return sprintf('[image: %s (%d bytes)]', $content->mimeType, strlen($content->data));
        }
        if ($content instanceof AudioContent) {
            return sprintf('[audio: %s (%d bytes)]', $content->mimeType, strlen($content->data));
        }
        if ($content instanceof EmbeddedResource) {
            return sprintf('[embedded resource: %s]', $content->resource->uri);
        }

        return json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    private function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (null === $value) {
            return 'null';
        }

        return (string) $value;
    }

    private function encodeJson(mixed $result): string
    {
        $normalized = $result instanceof CallToolResult ? $result->jsonSerialize() : $result;

        return json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'null';
    }
}
