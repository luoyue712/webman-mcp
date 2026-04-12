<?php

use Luoyue\WebmanMcp\Event\WebmanEvent;
use Luoyue\WebmanMcp\Server\WebmanSessionStore;
use Luoyue\WebmanMcp\Tests\Conformance\Elements;
use Mcp\Schema\Content\AudioContent;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server\Builder;
use Workerman\Events\Fiber;
use Workerman\Events\Swoole;
use Workerman\Events\Swow;
use Workerman\Events\Ev;
use Composer\InstalledVersions;

function event_loop(): string
{
    if (extension_loaded('swoole')) {
        return Swoole::class;
    }
    if (extension_loaded('swow')) {
        return Swow::class;
    }
    if (extension_loaded('ev')) {
        return Ev::class;
    }
    if (InstalledVersions::isInstalled('revolt/event-loop')) {
        return Fiber::class;
    }
    return '';
}

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
                resourcesSubscribe: true,
                resourcesListChanged: WebmanEvent::installed(),
                prompts: true,
                promptsListChanged: WebmanEvent::installed(),
                logging: true,
                completions: true,
                experimental: null,
            ));
            $server->setSession(new WebmanSessionStore('', 'mcp-', 86400));
            $server
                // Tools
                ->addTool(static fn() => 'This is a simple text response for testing.', 'test_simple_text', 'Tests simple text content response')
                ->addTool(static fn() => new ImageContent(Elements::TEST_IMAGE_BASE64, 'image/png'), 'test_image_content', 'Tests image content response')
                ->addTool(static fn() => new AudioContent(Elements::TEST_AUDIO_BASE64, 'audio/wav'), 'test_audio_content', 'Tests audio content response')
                ->addTool(static fn() => EmbeddedResource::fromText('test://embedded-resource', 'This is an embedded resource content.'), 'test_embedded_resource', 'Tests embedded resource content response')
                ->addTool([Elements::class, 'toolMultipleTypes'], 'test_multiple_content_types', 'Tests response with multiple content types')
                ->addTool([Elements::class, 'toolWithLogging'], 'test_tool_with_logging', 'Tests tool that emits log messages')
                ->addTool([Elements::class, 'toolWithProgress'], 'test_tool_with_progress', 'Tests tool that reports progress notifications')
                ->addTool([Elements::class, 'toolWithSampling'], 'test_sampling', 'Tests server-initiated sampling')
                ->addTool(static fn() => CallToolResult::error([new TextContent('This tool intentionally returns an error for testing')]), 'test_error_handling', 'Tests error response handling')
                ->addTool([Elements::class, 'toolWithElicitation'], 'test_elicitation', 'Tests server-initiated elicitation')
                ->addTool([Elements::class, 'toolWithElicitationDefaults'], 'test_elicitation_sep1034_defaults', 'Tests elicitation with default values')
                ->addTool([Elements::class, 'toolWithElicitationEnums'], 'test_elicitation_sep1330_enums', 'Tests elicitation with enum schemas')
                // Resources
                ->addResource(static fn() => 'This is the content of the static text resource.', 'test://static-text', 'static-text', 'A static text resource for testing')
                ->addResource(static fn() => fopen('data://image/png;base64,' . Elements::TEST_IMAGE_BASE64, 'r'), 'test://static-binary', 'static-binary', 'A static binary resource (image) for testing')
                ->addResourceTemplate([Elements::class, 'resourceTemplate'], 'test://template/{id}/data', 'template', 'A resource template with parameter substitution', 'application/json')
                ->addResource(static fn() => 'Watched resource content', 'test://watched-resource', 'watched-resource', 'A resource that can be watched')
                // Prompts
                ->addPrompt(static fn() => [['role' => 'user', 'content' => 'This is a simple prompt for testing.']], 'test_simple_prompt', 'A simple prompt without arguments')
                ->addPrompt([Elements::class, 'promptWithArguments'], 'test_prompt_with_arguments', 'A prompt with required arguments')
                ->addPrompt([Elements::class, 'promptWithEmbeddedResource'], 'test_prompt_with_embedded_resource', 'A prompt that includes an embedded resource')
                ->addPrompt([Elements::class, 'promptWithImage'], 'test_prompt_with_image', 'A prompt that includes image content');
        },
        'transport' => [
            'stdio' => [
                'enable' => true,
            ],
            'streamable_http' => [
                // mcp端点
                'endpoint' => '/mcp',
                // 启用后将mcp端点注入到您的路由中
                'router' => [
                    'enable' => true,
                ],
                // 额外的自定义进程配置（与process.php配置相同）使用port代替listen
                'process' => [
                    'enable' => true,
                    'port' => 8000,
                    'count' => cpu_count() * 4,
                    'eventloop' => event_loop(),
                    'reusePort' => true,
                ],
            ],
        ],
    ],
];
