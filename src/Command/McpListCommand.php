<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use ReflectionMethod;
use ReflectionProperty;
use support\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:list', 'List all MCP service')]
final class McpListCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['service', 'stdio', 'process_port', 'route', 'endpoint', 'session', 'logger']);
        $table->setHeaderTitle('mcp service list');

        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        $reflectionMethod = new ReflectionMethod($mcpServerManager, 'getServer');

        foreach ($mcpServerManager->getServiceNames() as $name) {
            $config = $mcpServerManager->getServiceConfig($name);
            [, , $builder] = $reflectionMethod->invoke($mcpServerManager, $name);

            $ssProp = new ReflectionProperty($builder, 'sessionStore');
            $sessionStore = $ssProp->getValue($builder);

            $sessionInfo = [];
            if ($sessionStore !== null) {
                try {
                    $sessionInfo['store'] = (new ReflectionProperty($sessionStore, 'store'))->getValue($sessionStore);
                } catch (\ReflectionException) {
                    $sessionInfo['store'] = $sessionStore::class;
                }
                try {
                    $sessionInfo['ttl'] = (new ReflectionProperty($sessionStore, 'ttl'))->getValue($sessionStore);
                } catch (\ReflectionException) {
                }
                foreach (['prefix', 'directory'] as $prop) {
                    try {
                        $sessionInfo[$prop] = (new ReflectionProperty($sessionStore, $prop))->getValue($sessionStore);
                    } catch (\ReflectionException) {
                    }
                }
            }
            $session = json_encode($sessionInfo, JSON_UNESCAPED_SLASHES);

            $loggerProp = new ReflectionProperty($builder, 'logger');
            $logger = $loggerProp->getValue($builder);
            $loggerName = $logger ? $logger::class : '(null)';

            $transport = $config['transport'];
            $httpConfig = $transport['streamable_http'];
            $process = $httpConfig['process'];
            $router = $httpConfig['router'];

            $table->addRow([
                $name,
                $transport['stdio']['enable'] ? 'yes' : 'no',
                $process['enable'] ? $process['port'] ?? '(null)' : '(null)',
                $router['enable'] ? 'yes' : 'no',
                $httpConfig['endpoint'] ?? '(null)',
                $session,
                $loggerName,
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
