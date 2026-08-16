<?php

namespace Luoyue\WebmanMcp\Server;

use Luoyue\WebmanMcp\McpServerManager;
use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Registry\Loader\DiscoveryLoader;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use support\Log;

class DevelopmentMcpLoader implements LoaderInterface
{
    private DiscoveryLoader $loader;

    /**
     * @param string[] $path
     */
    public function __construct(
        private readonly array $path = [],
        ?string $basePath = null,
    ) {
        $logger = Log::channel(McpServerManager::PLUGIN_REWFIX . 'mcp_error_stderr');

        $discoverer = class_exists(\Webman\Finder\Finder::class)
            ? new WebmanDiscoverer($logger)
            : new Discoverer($logger);

        $this->loader = new DiscoveryLoader(
            $basePath ?? base_path(),
            ['vendor/luoyue/webman-mcp/src/DevMcp', ...$this->path],
            [],
            $discoverer,
            logger: $logger,
        );
    }

    public function load(RegistryInterface $registry): void
    {
        $this->loader->load($registry);
    }
}
