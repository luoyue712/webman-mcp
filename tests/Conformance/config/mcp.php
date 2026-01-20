<?php

use Luoyue\WebmanMcp\Event\WebmanEvent;
use Luoyue\WebmanMcp\Tests\Conformance\Elements;
use Mcp\Schema\Content\AudioContent;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\Icon;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server\Builder;

return [
    'conformance' => [
        // MCP功能配置
        'configure' => function (Builder $server) {
            // 设置服务信息
            $server->setServerInfo('mcp-conformance-test-server', '1.0.0');
            // 设置协议版本
            $server->setProtocolVersion(ProtocolVersion::V2025_06_18);
            // 设置需要开启的功能
            $server->setCapabilities(new ServerCapabilities(
                tools: true,
                toolsListChanged: WebmanEvent::installed(),
                resources: true,
                resourcesSubscribe: false,
                resourcesListChanged: WebmanEvent::installed(),
                prompts: true,
                promptsListChanged: WebmanEvent::installed(),
                logging: true,
                completions: true,
                experimental: null,
            ));
            $server->addTool(fn () => 'This is a simple text response for testing.', 'test_simple_text', 'Tests simple text content response')
                ->addTool(fn () => new ImageContent(Elements::TEST_IMAGE_BASE64, 'image/png'), 'test_image_content', 'Tests image content response')
                ->addTool(fn () => new AudioContent(Elements::TEST_AUDIO_BASE64, 'audio/wav'), 'test_audio_content', 'Tests audio content response')
                ->addTool(fn () => EmbeddedResource::fromText('test://embedded-resource', 'This is an embedded resource content.'), 'test_embedded_resource', 'Tests embedded resource content response')
                ->addTool([Elements::class, 'toolMultipleTypes'], 'test_multiple_content_types', 'Tests response with multiple content types')
                ->addTool([Elements::class, 'toolWithLogging'], 'test_tool_with_logging', 'Tests tool that emits log messages')
                ->addTool([Elements::class, 'toolWithProgress'], 'test_tool_with_progress', 'Tests tool that reports progress notifications')
                ->addTool([Elements::class, 'toolWithSampling'], 'test_sampling', 'Tests server-initiated sampling')
                ->addTool(fn () => CallToolResult::error([new TextContent('This tool intentionally returns an error for testing')]), 'test_error_handling', 'Tests error response handling')
                // Resources
                ->addResource(fn () => 'This is the content of the static text resource.', 'test://static-text', 'static-text', 'A static text resource for testing')
                ->addResource(fn () => fopen('data://image/png;base64,' . Elements::TEST_IMAGE_BASE64, 'r'), 'test://static-binary', 'static-binary', 'A static binary resource (image) for testing')
                ->addResourceTemplate([Elements::class, 'resourceTemplate'], 'test://template/{id}/data', 'template', 'A resource template with parameter substitution', 'application/json')
                // TODO: Handler for resources/subscribe and resources/unsubscribe
                ->addResource(fn () => 'Watched resource content', 'test://watched-resource', 'watched-resource', 'A resource that can be watched')
                // Prompts
                ->addPrompt(fn () => [['role' => 'user', 'content' => 'This is a simple prompt for testing.']], 'test_simple_prompt', 'A simple prompt without arguments')
                ->addPrompt([Elements::class, 'promptWithArguments'], 'test_prompt_with_arguments', 'A prompt with required arguments')
                ->addPrompt([Elements::class, 'promptWithEmbeddedResource'], 'test_prompt_with_embedded_resource', 'A prompt that includes an embedded resource')
                ->addPrompt([Elements::class, 'promptWithImage'], 'test_prompt_with_image', 'A prompt that includes image content');
        },
        // 服务日志，对应插件下的log配置文件，为空则不记录日志
        'logger' => null,
        // 服务注册配置
        'discover' => [
            // 注解扫描路径
            'scan_dirs' => [
                'app/mcp',
            ],
            // 排除扫描路径
            'exclude_dirs' => [
            ],
            // 缓存扫描结果，cache.php中的缓存配置名称，对于webman常驻内存框架无提升并且无法及时清理缓存，建议关闭。
            'cache' => null,
        ],
        // session设置
        'session' => [
            'store' => null, // 对应cache.php中的缓存配置名称, null为使用默认的内存缓存（多进程模式下不适用）
            'prefix' => 'mcp-',
            'ttl' => 86400,
        ],
        'transport' => [
            'stdio' => [
                'enable' => true,
            ],
            'streamable_http' => [
                // mcp端点
                'endpoint' => '/mcp',
                // 额外响应头，可配置CORS跨域
                'headers' => [

                ],
                // 启用后将mcp端点注入到您的路由中
                'router' => [
                    'enable' => true,
                ],
                // 额外的自定义进程配置（与process.php配置相同）使用port代替listen
                'process' => [
                    'enable' => true,
                    'port' => 8000,
                    'count' => 1,
                    'eventloop' => '',
                ],
            ],
        ],
    ],
];
