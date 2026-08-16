<?php

namespace Luoyue\WebmanMcp\Command\Trait;

use InvalidArgumentException;
use Luoyue\WebmanMcp\McpServerManager;
use Mcp\Capability\RegistryInterface;
use Mcp\Server;
use Mcp\Server\Handler\Request\ListToolsHandler;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @internal
 */
trait RegistryAccessTrait
{
    private function getRegistry(McpServerManager $mcpServerManager, string $serviceName): RegistryInterface
    {
        $reflectionMethod = new ReflectionMethod($mcpServerManager, 'getServer');
        /** @var Server $builtServer */
        $builtServer = $reflectionMethod->invoke($mcpServerManager, $serviceName)[0];

        $protocol = (new ReflectionProperty(Server::class, 'protocol'))->getValue($builtServer);
        $requestHandlers = (new ReflectionProperty($protocol, 'requestHandlers'))->getValue($protocol);
        foreach ($requestHandlers as $handler) {
            if ($handler instanceof ListToolsHandler) {
                $registry = (new ReflectionProperty(ListToolsHandler::class, 'registry'))->getValue($handler);
                if ($registry instanceof RegistryInterface) {
                    return $registry;
                }
            }
        }
        throw new InvalidArgumentException(
            sprintf(
                'The "tools" capability of service "%s" is disabled or its registry is not accessible, so the command cannot run. Enable tools (e.g. setCapabilities with tools: true) in the service configuration.',
                $serviceName
            )
        );
    }
}
