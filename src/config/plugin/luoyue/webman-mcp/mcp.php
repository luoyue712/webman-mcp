<?php

use Luoyue\WebmanMcp\Event\WebmanEvent;
use Luoyue\WebmanMcp\McpServerManager;
use Luoyue\WebmanMcp\Server\DevelopmentMcpLoader;
use Luoyue\WebmanMcp\Server\WebmanDiscoverer;
use Luoyue\WebmanMcp\Server\WebmanSessionStore;
use Mcp\Capability\Registry\Loader\DiscoveryLoader;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\Icon;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server\Builder;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\ProtocolVersionMiddleware;
use support\Log;

return [
    'mcp' => [
        // MCP功能配置
        'configure' => function (Builder $server) {
            // 设置服务信息
            $server->setServerInfo(
                name: 'MCP Server',
                version: '0.0.1',
                description: 'MCP Server',
                icons: [
                    new Icon(
                        src: 'https://manual.workerman.net/favicon.ico',
                        mimeType: 'image/x-icon',
                        sizes: ['32x32']
                    ),
                ],
                websiteUrl: 'https://www.workerman.net/'
            );
            // 设置协议版本
            $server->setProtocolVersion(ProtocolVersion::V2025_06_18);
            // 设置使用说明
            $server->setInstructions('MCP Server');
            // 设置分页大小
            $server->setPaginationLimit(50);
            // 设置需要开启的功能
            $server->setCapabilities(new ServerCapabilities(
                tools: true,
                toolsListChanged: WebmanEvent::installed(),
                resources: true,
                resourcesSubscribe: true,
                resourcesListChanged: WebmanEvent::installed(),
                prompts: true,
                promptsListChanged: WebmanEvent::installed(),
                logging: true,
                completions: true,
                experimental: null,
            ));
            // 记录内部的服务日志，默认使用当前目录的log.php，如果不记录可以删除这行。
            $server->setLogger(Log::channel(McpServerManager::PLUGIN_REWFIX . 'mcp_error_stderr'));
            // session设置(依赖webman/cache)
            //            $server->setSession(new WebmanSessionStore('', 'mcp-', 86400));
            // session设置(内置文件存储，无依赖)
            $server->setSession(new FileSessionStore(runtime_path('/mcp/session'), 86400));
            // 注解扫描配置
            $server->addLoader(new DiscoveryLoader(
                base_path(),
                ['app/mcp'],// 注解扫描路径
                [],// 排除扫描路径
                new WebmanDiscoverer()
            )
            );
            // 添加开发环境工具，仅debug模式下启用
            config('app.debug') && $server->addLoader(new DevelopmentMcpLoader);
        },
        'transport' => [
            'stdio' => [
                'enable' => true,
            ],
            'streamable_http' => [
                // mcp端点
                'endpoint' => '/mcp',
                // PSR-15中间件
                'middleware' => [
                    // cors跨域中间件
                    new CorsMiddleware(allowedOrigins: ['*']),
                    new ProtocolVersionMiddleware(),
                ],
                // 启用后将mcp端点注入到您的路由中
                'router' => [
                    'enable' => true,
                ],
                // 额外的自定义进程配置（与process.php配置相同）使用port代替listen
                'process' => [
                    'enable' => false,
                    'port' => 8080,
                    'count' => 1,
                    'eventloop' => '',
                ],
            ],
        ],
    ],
];
