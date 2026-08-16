<?php

namespace Luoyue\WebmanMcp\Command;

use InvalidArgumentException;
use Luoyue\WebmanMcp\Command\Trait\RegistryAccessTrait;
use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('mcp:tools', 'List tools, resources and prompts of a service with their schemas')]
final class McpToolsCommand extends Command
{
    use RegistryAccessTrait;

    public function configure(): void
    {
        $this->addArgument('service', InputArgument::REQUIRED, 'Service name');
    }

    public function execute(InputInterface $input, OutputInterface $output, ?string $service = null): int
    {
        $service ??= $input->getArgument('service');
        $style = new SymfonyStyle($input, $output);
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        try {
            $registry = $this->getRegistry($mcpServerManager, $service);
        } catch (InvalidArgumentException $e) {
            $style->error($e->getMessage());

            return Command::FAILURE;
        }

        $tools = $registry->getTools()->references;
        $resources = $registry->getResources()->references;
        $templates = $registry->getResourceTemplates()->references;
        $prompts = $registry->getPrompts()->references;

        $style->title("MCP service: {$service}");
        $style->writeln(sprintf(
            'tools: %d, resources: %d, resource_templates: %d, prompts: %d',
            count($tools),
            count($resources),
            count($templates),
            count($prompts)
        ));

        $this->renderTools($output, $tools);
        $this->renderResources($output, $resources);
        $this->renderResourceTemplates($output, $templates);
        $this->renderPrompts($output, $prompts);

        return Command::SUCCESS;
    }

    /**
     * @param array<int|string, \Mcp\Schema\Tool> $tools
     */
    private function renderTools(OutputInterface $output, array $tools): void
    {
        if ([] === $tools) {
            return;
        }
        $table = new Table($output);
        $table->setHeaders(['name', 'title', 'description', 'input_schema', 'output_schema']);
        foreach ($tools as $tool) {
            $table->addRow([
                $tool->name,
                $tool->title ?? '-',
                $tool->description ?? '-',
                $this->jsonEncode($tool->inputSchema),
                null === $tool->outputSchema ? '-' : $this->jsonEncode($tool->outputSchema),
            ]);
        }
        $table->render();
    }

    /**
     * @param array<int|string, \Mcp\Schema\ResourceDefinition> $resources
     */
    private function renderResources(OutputInterface $output, array $resources): void
    {
        if ([] === $resources) {
            return;
        }
        $table = new Table($output);
        $table->setHeaders(['uri', 'name', 'title', 'description', 'mime_type']);
        foreach ($resources as $resource) {
            $table->addRow([
                $resource->uri,
                $resource->name,
                $resource->title ?? '-',
                $resource->description ?? '-',
                $resource->mimeType ?? '-',
            ]);
        }
        $table->render();
    }

    /**
     * @param array<int|string, \Mcp\Schema\ResourceTemplate> $templates
     */
    private function renderResourceTemplates(OutputInterface $output, array $templates): void
    {
        if ([] === $templates) {
            return;
        }
        $table = new Table($output);
        $table->setHeaders(['uri_template', 'name', 'title', 'description', 'mime_type']);
        foreach ($templates as $template) {
            $table->addRow([
                $template->uriTemplate,
                $template->name,
                $template->title ?? '-',
                $template->description ?? '-',
                $template->mimeType ?? '-',
            ]);
        }
        $table->render();
    }

    /**
     * @param array<int|string, \Mcp\Schema\Prompt> $prompts
     */
    private function renderPrompts(OutputInterface $output, array $prompts): void
    {
        if ([] === $prompts) {
            return;
        }
        $table = new Table($output);
        $table->setHeaders(['name', 'title', 'description', 'arguments']);
        foreach ($prompts as $prompt) {
            $arguments = array_map(static fn ($arg) => $arg->name, $prompt->arguments ?? []);
            $table->addRow([
                $prompt->name,
                $prompt->title ?? '-',
                $prompt->description ?? '-',
                [] === $arguments ? '-' : implode(', ', $arguments),
            ]);
        }
        $table->render();
    }

    /**
     * @param array<mixed> $schema
     */
    private function jsonEncode(array $schema): string
    {
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
