<?php

use Composer\InstalledVersions;
use Luoyue\WebmanMcp\Event\WebmanEvent;
use Luoyue\WebmanMcp\Server\DevelopmentMcpLoader;
use Luoyue\WebmanMcp\Server\WebmanSessionStore;
use Luoyue\WebmanMcp\Tests\Conformance\Elements;
use Mcp\Schema\Content\AudioContent;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\Extension\Apps\McpApps;
use Mcp\Schema\Extension\Apps\ToolVisibility;
use Mcp\Schema\Extension\Apps\UiResourceContentMeta;
use Mcp\Schema\Extension\Apps\UiResourceCsp;
use Mcp\Schema\Extension\Apps\UiResourcePermissions;
use Mcp\Schema\Extension\Apps\UiToolMeta;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server\Builder;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Workerman\Events\Ev;
use Workerman\Events\Fiber;
use Workerman\Events\Swoole;
use Workerman\Events\Swow;

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
                ->addTool(static fn () => 'This is a simple text response for testing.', name: 'test_simple_text', description: 'Tests simple text content response')
                ->addTool(static fn () => new ImageContent(Elements::TEST_IMAGE_BASE64, 'image/png'), name: 'test_image_content', description: 'Tests image content response')
                ->addTool(static fn () => new AudioContent(Elements::TEST_AUDIO_BASE64, 'audio/wav'), name: 'test_audio_content', description: 'Tests audio content response')
                ->addTool(static fn () => EmbeddedResource::fromText('test://embedded-resource', 'This is an embedded resource content.'), name: 'test_embedded_resource', description: 'Tests embedded resource content response')
                ->addTool([Elements::class, 'toolMultipleTypes'], name: 'test_multiple_content_types', description: 'Tests response with multiple content types')
                ->addTool([Elements::class, 'toolWithLogging'], name: 'test_tool_with_logging', description: 'Tests tool that emits log messages')
                ->addTool([Elements::class, 'toolWithProgress'], name: 'test_tool_with_progress', description: 'Tests tool that reports progress notifications')
                ->addTool([Elements::class, 'toolWithSampling'], name: 'test_sampling', description: 'Tests server-initiated sampling')
                ->addTool(static fn () => CallToolResult::error([new TextContent('This tool intentionally returns an error for testing')]), name: 'test_error_handling', description: 'Tests error response handling')
                ->addTool([Elements::class, 'toolWithElicitation'], name: 'test_elicitation', description: 'Tests server-initiated elicitation')
                ->addTool([Elements::class, 'toolWithElicitationDefaults'], name: 'test_elicitation_sep1034_defaults', description: 'Tests elicitation with default values')
                ->addTool([Elements::class, 'toolWithElicitationEnums'], name: 'test_elicitation_sep1330_enums', description: 'Tests elicitation with enum schemas')
                // Resources
                ->addResource(static fn () => 'This is the content of the static text resource.', 'test://static-text', 'static-text', 'A static text resource for testing')
                ->addResource(static fn () => fopen('data://image/png;base64,' . Elements::TEST_IMAGE_BASE64, 'r'), 'test://static-binary', 'static-binary', 'A static binary resource (image) for testing')
                ->addResourceTemplate([Elements::class, 'resourceTemplate'], 'test://template/{id}/data', 'template', 'A resource template with parameter substitution', 'application/json')
                ->addResource(static fn () => 'Watched resource content', 'test://watched-resource', 'watched-resource', 'A resource that can be watched')
                // Prompts
                ->addPrompt(static fn () => [['role' => 'user', 'content' => 'This is a simple prompt for testing.']], name: 'test_simple_prompt', description: 'A simple prompt without arguments')
                ->addPrompt([Elements::class, 'promptWithArguments'], name: 'test_prompt_with_arguments', description: 'A prompt with required arguments')
                ->addPrompt([Elements::class, 'promptWithEmbeddedResource'], name: 'test_prompt_with_embedded_resource', description: 'A prompt that includes an embedded resource')
                ->addPrompt([Elements::class, 'promptWithImage'], name: 'test_prompt_with_image', description: 'A prompt that includes image content');
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
                // PSR-15中间件，如果使用空数组则没有中间件，如果为null则使用sdk默认中间件
                'middleware' => [
                    new DnsRebindingProtectionMiddleware(),
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
    // 供 tests/Inspector 套件（inspector CLI 模式）通过 mcp:server 命令启动的 STDIO 测试服务
    // 参考默认配置（注解扫描 + 显式注册）与 src/DevMcp 的注解类写法
    'inspector' => [
        'configure' => function (Builder $server) {
            $server->setServerInfo('inspector-test-server', '1.0.0');
            $server->setProtocolVersion(ProtocolVersion::V2025_06_18);
            $server->enableExtension(new McpApps());
            // 注解扫描：注册 tests/Fixtures/Inspector 下的工具/资源/提示（复用 DevMcp 加载器，兼容 webman < 2.2）
            $server->addLoader(new DevelopmentMcpLoader(['Fixtures/Inspector']));
            // mcp-apps（UI meta 为运行时对象，无法注解化，显式注册）
            $server
                ->addResource(
                    static fn () => new TextResourceContents(
                        uri: 'ui://weather-app',
                        mimeType: 'text/html;profile=mcp-app',
                        text: file_get_contents(dirname(__DIR__) . '/Inspector/weather-app.html'),
                        meta: ['ui' => new UiResourceContentMeta(
                            csp: new UiResourceCsp(connectDomains: ['https://api.weather.example.com']),
                            permissions: new UiResourcePermissions(geolocation: true),
                            prefersBorder: true,
                        )],
                    ),
                    'ui://weather-app',
                    'weather-app',
                    description: 'Interactive weather dashboard',
                    mimeType: 'text/html;profile=mcp-app',
                    meta: ['ui' => McpApps::resourceMarker()]
                )
                ->addTool(
                    function (string $city): string {
                        $weather = [
                            'london' => ['temp' => '15°C', 'condition' => 'Cloudy', 'humidity' => '78%'],
                            'paris' => ['temp' => '18°C', 'condition' => 'Sunny', 'humidity' => '55%'],
                            'tokyo' => ['temp' => '22°C', 'condition' => 'Partly Cloudy', 'humidity' => '65%'],
                            'new york' => ['temp' => '12°C', 'condition' => 'Rainy', 'humidity' => '85%'],
                            'lagos' => ['temp' => '30°C', 'condition' => 'Sunny', 'humidity' => '82%'],
                            'stockholm' => ['temp' => '4°C', 'condition' => 'Cloudy', 'humidity' => '70%'],
                            'berlin' => ['temp' => '9°C', 'condition' => 'Partly Cloudy', 'humidity' => '68%'],
                            'sydney' => ['temp' => '26°C', 'condition' => 'Sunny', 'humidity' => '60%'],
                            'buenos aires' => ['temp' => '24°C', 'condition' => 'Rainy', 'humidity' => '80%'],
                        ];
                        $key = strtolower($city);
                        $data = $weather[$key] ?? ['temp' => '20°C', 'condition' => 'Clear', 'humidity' => '60%'];

                        return sprintf(
                            'Weather in %s: %s, %s, Humidity: %s',
                            $city,
                            $data['temp'],
                            $data['condition'],
                            $data['humidity'],
                        );
                    },
                    name: 'get_weather',
                    description: 'Get current weather for a city',
                    meta: ['ui' => new UiToolMeta(
                        resourceUri: 'ui://weather-app',
                        visibility: [ToolVisibility::Model, ToolVisibility::App],
                    )]
                );
        },
        'transport' => [
            'stdio' => [
                'enable' => true,
            ],
            'streamable_http' => [
                'endpoint' => '/mcp',
                'router' => [
                    'enable' => false,
                ],
                'middleware' => [],
                'process' => [
                    'enable' => false,
                ],
            ],
        ],
    ],
];
