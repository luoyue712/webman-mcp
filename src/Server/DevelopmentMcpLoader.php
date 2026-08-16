<?php

namespace Luoyue\WebmanMcp\Server;

use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Registry\Loader\DiscoveryLoader;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;

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
        $discoverer = class_exists(\Webman\Finder\Finder::class)
            ? new WebmanDiscoverer()
            : new Discoverer();

        $this->loader = new DiscoveryLoader(
            $basePath ?? base_path(),
            ['vendor/luoyue/webman-mcp/src/DevMcp', ...$this->path],
            [],
            $discoverer,
        );
    }

    public function load(RegistryInterface $registry): void
    {
        $this->loader->load($registry);
    }
}
