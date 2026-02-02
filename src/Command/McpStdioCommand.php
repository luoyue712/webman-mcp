<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:server', 'Starts an MCP server')]
final class McpStdioCommand extends Command
{
    public function configure(): void
    {
        $this->addArgument('service', InputArgument::REQUIRED, 'Service name');
    }

    public function __invoke(InputInterface $input, OutputInterface $output, ?string $service = null): int
    {
        $service ??= $input->getArgument('service');
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        $config = $mcpServerManager->getServiceConfig($service);
        if (!($config['transport']['stdio']['enable'] ?? false)) {
            $output->writeln("<error>MCP service: {$service} not enable stdio</error>");
            return Command::FAILURE;
        }
        /** @var ConsoleOutputInterface $output */
        $output->getErrorOutput()->writeln("<info>Starting MCP service: {$service}</info>");
        $mcpServerManager->start($service);
        return Command::SUCCESS;
    }
}
