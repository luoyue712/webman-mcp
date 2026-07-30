<?php

namespace Luoyue\WebmanMcp;

use Generator;
use InvalidArgumentException;
use Luoyue\WebmanMcp\Event\WebmanEvent;
use Luoyue\WebmanMcp\Server\StreamableHttpTransport;
use Mcp\Server;
use Mcp\Server\Builder;
use Mcp\Server\Transport\StdioTransport;
use Mcp\Server\Transport\TransportInterface;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use function request;
use support\Container;
use support\Context;
use WeakMap;
use Webman\Http\Response;
use Workerman\Connection\TcpConnection;

final class McpServerManager
{
    public const PLUGIN_REWFIX = 'plugin.luoyue.webman-mcp.';

    /** @var array<string, mixed> */
    private static array $config;

    /** @var array<string, array{Server, LoggerInterface|null, Builder}> */
    private static array $server = [];

    /** @var WeakMap<TransportInterface<mixed>, int> */
    private static WeakMap $transports;

    public function __construct()
    {
        self::$transports ??= new WeakMap();
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadConfig(): array
    {
        static $isInit;
        if ($isInit) {
            return self::$config;
        }
        self::$config = config(self::PLUGIN_REWFIX . 'mcp', []);
        $isInit = true;
        return self::$config;
    }

    /**
     * @return Generator<string>
     */
    public function getServiceNames(): Generator
    {
        yield from array_keys(self::loadConfig());
    }

    /**
     * @return array<string, mixed>
     */
    public function getServiceConfig(string $serviceName): array
    {
        $config = self::loadConfig()[$serviceName] ?? null;
        if (!$config) {
            throw new InvalidArgumentException("Mcp server [{$serviceName}] not found.");
        }
        return $config;
    }

    /**
     * @return WeakMap<TransportInterface<mixed>, int>
     */
    public function getTransports(): WeakMap
    {
        return self::$transports;
    }

    /**
     * @return array{Server, LoggerInterface|null, Builder}
     */
    private function getServer(string $serviceName): array
    {
        if (isset(self::$server[$serviceName])) {
            return self::$server[$serviceName];
        }
        self::loadConfig();
        $config = $this->getServiceConfig($serviceName);
        $server = Server::builder()
            ->setContainer(Container::instance());
        WebmanEvent::installed() && $server->setEventDispatcher(WebmanEvent::instance());
        isset($config['configure']) && is_callable($config['configure']) && ($config['configure'])($server);

        $reflectionProperty = new ReflectionProperty($server, 'logger');
        self::$server[$serviceName] = [$server->build(), $reflectionProperty->getValue($server), $server];

        return self::$server[$serviceName];
    }

    public function start(string $serviceName): mixed
    {
        [$server, $logger] = $this->getServer($serviceName);

        return isset($_ENV['MCP_STDIO']) ?
            $this->handleStdioMessage($server, $serviceName, $logger) : $this->handleHttpRequest($server, $serviceName, $logger);
    }

    private function handleStdioMessage(Server $server, string $serviceName, ?LoggerInterface $logger = null): int
    {
        Context::set('McpServerRequest', true);
        $transport = new StdioTransport(logger: $logger);
        self::$transports[$transport] = time();

        return $server->run($transport);
    }

    private function handleHttpRequest(Server $server, string $serviceName, ?LoggerInterface $logger = null): Response
    {
        $config = $this->getServiceConfig($serviceName);
        $transportConfig = $config['transport']['streamable_http'] ?? [];
        $middleware = [];
        foreach ($transportConfig['middleware'] ?? [] as $item) {
            if (is_object($item)) {
                $middleware[] = $item;
            } else if (is_string($item) && class_exists($item)) {
                $middleware[] = Container::get($item);
            } else {
                throw new InvalidArgumentException('Invalid middleware item.');
            }
        }
        request()->plugin = 'luoyue.webman-mcp';
        $request = new ServerRequest(
            request()->method(),
            request()->uri(),
            request()->header(),
            request()->rawBody(),
            request()->protocolVersion(),
            $_SERVER
        );
        $request = $request->withAttribute(TcpConnection::class, request()->connection);

        Context::set('McpServerRequest', true);
        $transport = new StreamableHttpTransport(request: $request, logger: $logger, middleware: $middleware);
        self::$transports[$transport] = time();

        /** @var ResponseInterface $response */
        $response = $server->run($transport);

        return response($response->getBody(), $response->getStatusCode(), array_map('current', $response->getHeaders()));
    }
}
