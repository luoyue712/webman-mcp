<?php

namespace Luoyue\WebmanMcp\Command;

use InvalidArgumentException;
use Luoyue\WebmanMcp\Command\Trait\RegistryAccessTrait;
use Luoyue\WebmanMcp\McpServerManager;
use Mcp\Capability\Registry\ReferenceHandler;
use Mcp\Capability\Registry\ResourceTemplateReference;
use Mcp\Exception\ResourceNotFoundException;
use Mcp\Schema\Content\BlobResourceContents;
use Mcp\Schema\Content\ResourceContents;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\Request\ReadResourceRequest;
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

/**
 * Read MCP resources by URI.
 *
 * @author Johannes Wachter <johannes@sulu.io>
 */
#[AsCommand('mcp:resources:read', 'Read MCP resources by URI')]
final class McpResourcesReadCommand extends Command
{
    use RegistryAccessTrait;

    public function configure(): void
    {
        $this
            ->addArgument('service', InputArgument::REQUIRED, 'Service name')
            ->addArgument('uri', InputArgument::REQUIRED, 'URI of the resource to read')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (pretty, json)', 'pretty')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command reads an MCP resource by its URI.

Both static resource URIs and URIs matching a registered resource template are supported.

<info>Usage Examples:</info>

  <comment># Read a static resource</comment>
  %command.full_name% conformance test://static-text

  <comment># Read a templated resource (URI matched against registered templates)</comment>
  %command.full_name% conformance test://template/abc123/data

  <comment># JSON output format</comment>
  %command.full_name% conformance test://template/abc123/data --format=json
HELP
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $verbose = $output->isVerbose();

        $service = (string) $input->getArgument('service');
        $uri = (string) $input->getArgument('uri');
        $format = (string) $input->getOption('format');

        if (!in_array($format, ['pretty', 'json'], true)) {
            $io->error(sprintf('Unsupported format: %s', $format));

            return Command::FAILURE;
        }

        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        try {
            $registry = $this->getRegistry($mcpServerManager, $service);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        try {
            $reference = $registry->getResource($uri);
        } catch (ResourceNotFoundException $e) {
            $io->error(sprintf('Resource "%s" not found', $uri));
            $io->note(sprintf('Use "webman mcp:tools %s" to see all available resources', $service));

            return Command::FAILURE;
        }

        $session = new Session(new InMemorySessionStore());
        $request = new ReadResourceRequest(uri: $uri);

        $arguments = [
            'uri' => $uri,
            '_session' => $session,
            '_request' => $request,
        ];

        if ($reference instanceof ResourceTemplateReference) {
            $arguments = array_merge($arguments, $reference->extractVariables($uri));
            $mimeType = $reference->resourceTemplate->mimeType;
            $name = $reference->resourceTemplate->name;
            $description = $reference->resourceTemplate->description;
        } else {
            $mimeType = $reference->resource->mimeType;
            $name = $reference->resource->name;
            $description = $reference->resource->description;
        }

        if ('pretty' === $format) {
            $io->title(sprintf('Reading Resource: %s', $uri));
            $io->text(sprintf('<info>Name:</info> %s', $name));
            if (null !== $description) {
                $io->text($description);
            }
            $io->newLine();
        }

        try {
            $container = Container::instance();
            \assert($container instanceof ContainerInterface);
            $result = (new ReferenceHandler($container))->handle($reference, $arguments);
            $contents = $reference->formatResult($result, $uri, $mimeType);
        } catch (\Throwable $e) {
            if ($verbose) {
                $io->error(sprintf('Error: %s', $e->getMessage()));
                $io->text($e->getTraceAsString());
            } else {
                $io->error(sprintf('Error: %s', $e->getMessage()));
            }

            return Command::FAILURE;
        }

        if ('json' === $format) {
            $output->writeln(json_encode($contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $io->section('Contents');
        foreach ($contents as $content) {
            $this->renderContent($content, $io);
        }

        return Command::SUCCESS;
    }

    private function renderContent(ResourceContents $content, SymfonyStyle $io): void
    {
        $io->definitionList(
            ['URI' => $content->uri],
            ['MIME Type' => $content->mimeType ?? '<comment>N/A</comment>'],
        );

        if ($content instanceof TextResourceContents) {
            $io->text($content->text);

            return;
        }

        if ($content instanceof BlobResourceContents) {
            $io->text(sprintf('<comment>Binary blob (%d bytes, base64-encoded)</comment>', strlen($content->blob)));

            return;
        }

        $io->text('<comment>Unknown content type</comment>');
    }
}
