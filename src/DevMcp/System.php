<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Closure;
use Composer\InstalledVersions;
use function config;
use FastRoute\Dispatcher;
use Luoyue\WebmanMcp\McpHelper;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use ReflectionMethod;
use Throwable;
use Webman\App;
use Webman\Console\Commands\BuildBinCommand;
use Webman\Console\Commands\BuildPharCommand;
use Webman\Event\Event;
use Webman\Route;
use Webman\Route\Route as RouteObject;
use Workerman\Worker;

class System
{
    #[McpTool(
        name: 'system_info',
        title: '获取系统环境信息',
        description: '获取服务器操作系统、PHP版本与二进制路径、Workerman/webman版本、事件循环驱动及协程状态等系统环境信息',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'server_os' => ['type' => 'string'],
                'server_uname' => ['type' => 'string'],
                'php_version' => ['type' => 'string'],
                'php_binary' => ['type' => 'string'],
                'php_sapi_name' => ['type' => 'string'],
                'workerman_version' => ['type' => 'string'],
                'webman_version' => ['type' => 'string'],
                'event_loop_class' => ['type' => 'string'],
                'is_coroutine' => ['type' => 'boolean'],
                'default_temp_dir' => ['type' => 'string'],
            ],
            'required' => ['server_os', 'server_uname', 'php_version', 'php_binary', 'php_sapi_name', 'workerman_version', 'webman_version', 'event_loop_class', 'is_coroutine', 'default_temp_dir'],
        ]
    )]
    public function systemInfo(): array
    {
        $event_loop = Worker::getEventLoop()::class;
        return [
            'server_os' => PHP_OS,
            'server_uname' => php_uname(),
            'php_version' => PHP_VERSION,
            'php_binary' => PHP_BINARY,
            'php_sapi_name' => php_sapi_name(),
            'workerman_version' => InstalledVersions::getPrettyVersion('workerman/workerman'),
            'webman_version' => InstalledVersions::getPrettyVersion('workerman/webman-framework'),
            'event_loop_class' => $event_loop,
            'is_coroutine' => McpHelper::is_coroutine(),
            'default_temp_dir' => sys_get_temp_dir(),
        ];
    }

    #[McpTool(
        name: 'system_dependencies',
        title: '获取项目依赖列表',
        description: '获取当前项目所有已安装的Composer依赖包信息，包括包名、版本、类型、安装路径及源码引用等',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'root' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'version' => ['type' => 'string'],
                        'pretty_version' => ['type' => 'string'],
                        'reference' => ['type' => ['string', 'null']],
                        'type' => ['type' => 'string'],
                        'install_path' => ['type' => 'string'],
                        'aliases' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'dev' => ['type' => 'boolean'],
                    ],
                    'required' => ['name', 'version', 'pretty_version', 'reference', 'type', 'install_path', 'aliases', 'dev'],
                ],
                'versions' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'pretty_version' => ['type' => 'string'],
                            'version' => ['type' => 'string'],
                            'reference' => ['type' => ['string', 'null']],
                            'type' => ['type' => 'string'],
                            'install_path' => ['type' => 'string'],
                            'aliases' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'dev_requirement' => ['type' => 'boolean'],
                            'replaced' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'provided' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required' => ['dev_requirement'],
                    ],
                ],
            ],
            'required' => ['root', 'versions'],
        ]
    )]
    public function listDependence(): array
    {
        return InstalledVersions::getAllRawData()[0];
    }

    /**
     * @return string[]
     */
    #[McpTool(
        name: 'system_extensions',
        title: '获取已加载的PHP扩展',
        description: '获取当前PHP环境已加载的所有扩展及其提供的函数列表，以扩展名为键、函数数组为值',
        outputSchema: [
            'type' => 'object',
        ]
    )]
    public function extensions(): array
    {
        $extension = get_loaded_extensions();
        $funcs = array_map(fn ($item) => get_extension_funcs($item), $extension);
        return array_combine($extension, $funcs);
    }

    #[McpTool(
        name: 'system_php_ini',
        title: '获取PHP配置信息',
        description: '获取PHP配置文件(php.ini)中的所有配置项及其当前值，可按扩展名筛选，便于排查PHP运行环境',
        outputSchema: [
            'type' => 'object',
        ]
    )]
    public function getPhpIni(
        #[Schema(description: 'ini中的key')]
        ?string $extension = null,
    ): array|bool
    {
        return ini_get_all($extension);
    }

    #[McpTool(
        name: 'system_config',
        title: '获取应用配置',
        description: '通过点号分隔的配置路径（如 "database.default"）获取应用程序的配置值',
        outputSchema: [
            'type' => 'object',
        ]
    )]
    public function getConfig(
        #[Schema(description: '配置文件名')]
        string $path,
    ): mixed
    {
        return config($path);
    }

    #[McpTool(
        name: 'system_routes',
        title: '获取路由列表',
        description: '获取应用中所有已注册的路由，包括请求方法、URI、回调处理器、中间件及路由参数等信息',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'routes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => ['string', 'null']],
                            'uri' => ['type' => 'string'],
                            'methods' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'callback' => ['type' => 'string'],
                            'param' => ['type' => ['object', 'array']],
                            'middleware' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
            ],
        ]
    )]
    public function listRoutes(): array
    {
        $callback = function (RouteObject $route) {
            $cb = $route->getCallback();
            $cb = $cb instanceof Closure ? 'Closure' : (is_array($cb) ? json_encode($cb) : var_export($cb, 1));
            return [
                'name' => $route->getName(),
                'uri' => $route->getPath(),
                'methods' => $route->getMethods(),
                'callback' => $cb,
                'param' => $route->param(),
                'middleware' => json_decode(json_encode($route->getMiddleware()), true),
            ];
        };
        return [
            'routes' => array_map($callback, Route::getRoutes()),
        ];
    }

    #[McpTool(
        name: 'system_match_routes',
        title: '匹配URL对应的路由',
        description: '根据URL路径与请求方法匹配路由，返回控制器、插件、动作及路由参数，便于排查请求分发',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'plugin' => ['type' => 'string'],
                'app' => ['type' => 'string'],
                'controller' => ['type' => 'string'],
                'action' => ['type' => 'string'],
                'route' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => ['string', 'null']],
                        'uri' => ['type' => 'string'],
                        'methods' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'callback' => ['type' => 'string'],
                        'param' => ['type' => ['object', 'array']],
                        'args' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'middleware' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                    'required' => ['name', 'uri', 'methods', 'callback', 'param', 'args', 'middleware'],
                ],
            ],
            'required' => ['plugin', 'app', 'controller', 'action', 'route'],
        ]
    )]
    public function matchRoutes(
        #[Schema(description: 'url路径，不包含域名')]
        string $path,
        #[Schema(description: '请求方法')]
        ?string $method = '',
    ): array
    {
        $method = strtoupper($method);
        $getAppByController = new ReflectionMethod(App::class, 'getAppByController');
        $getRealMethod = new ReflectionMethod(App::class, 'getRealMethod');
        $routeInfo = Route::dispatch($method, $path);
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $callback = $routeInfo[1]['callback'];
            /** @var RouteObject $route */
            $route = clone $routeInfo[1]['route'];
            $app = $controller = $action = '';
            $args = !empty($routeInfo[2]) ? $routeInfo[2] : [];
            if ($args) {
                $route->setParams($args);
            }
            $args = array_merge($route->param(), $args);

            if (is_array($callback)) {
                $controller = $callback[0];
                $plugin = App::getPluginByClass($controller);
                $app = $getAppByController->invokeArgs(null, [$controller]);
                $action = $getRealMethod->invokeArgs(null, [$controller, $callback[1]]) ?? '';
            } else {
                $plugin = App::getPluginByPath($path);
            }
            $cb = $callback instanceof Closure ? 'Closure' : (is_array($callback) ? json_encode($callback) : var_export($callback, 1));
            return [
                'plugin' => $plugin,
                'app' => $app,
                'controller' => $controller ?: '',
                'action' => $action,
                'route' => [
                    'name' => $route->getName(),
                    'uri' => $route->getPath(),
                    'methods' => $route->getMethods(),
                    'callback' => $cb,
                    'param' => $route->param(),
                    'args' => $args,
                    'middleware' => json_decode(json_encode($route->getMiddleware()), true),
                ],
            ];
        } else if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new ToolCallException('Method Not Allowed. Supported methods: ' . implode(', ', $routeInfo[1]));
        }
        throw new ToolCallException('Not Found');
    }

    #[McpTool(
        name: 'system_events',
        title: '获取事件列表',
        description: '获取所有已注册的webman事件及其回调函数列表（依赖webman/event组件）',
        outputSchema: [
            'type' => 'object',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'event_name' => ['type' => 'string'],
                    'callback' => ['type' => 'string'],
                ],
                'required' => ['event_name', 'callback'],
            ],
        ]
    )]
    public function listEvents(): array
    {
        if (!InstalledVersions::isInstalled('webman/event')) {
            throw new ToolCallException('请先安装webman/event扩展');
        }
        $callback = function ($item) {
            $event_name = $item[0];
            $callback = $item[1];
            if (is_array($callback) && is_object($callback[0])) {
                $callback[0] = get_class($callback[0]);
            }
            $cb = $callback instanceof Closure ? 'Closure' : (is_array($callback) ? json_encode($callback) : var_export($callback, 1));
            return [
                'event_name' => $event_name,
                'callback' => $cb,
            ];
        };
        return array_map($callback, Event::list());
    }

    #[McpTool(
        name: 'system_env',
        title: '获取环境变量',
        description: '获取应用环境变量，可按名称获取单个变量，不传参则返回全部环境变量',
        outputSchema: [
            'type' => 'object',
            'anyOf' => [
                ['type' => 'object'],
                ['type' => 'string'],
                ['type' => 'boolean'],
            ],
        ]
    )]
    public function getEnv(
        #[Schema(description: '环境变量名')]
        ?string $key = null,
    ): array|false|string
    {
        return getenv($key);
    }

    #[McpTool(
        name: 'system_eval_code',
        title: '执行PHP代码',
        description: '在当前服务进程中动态执行PHP代码并返回输出结果。注意：declare语句会被自动移除，class需用条件判断包裹。此操作有安全风险，请谨慎使用',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'result' => ['type' => 'string'],
            ],
            'required' => ['result'],
        ]
    )]
    public function evalCode(
        #[Schema(description: 'php代码，注意：在代码中必须删除declare语句，如有class代码块请使用`if (!class_exists({className})){}`包裹防止重复加载类。')]
        string $code,
    ): array
    {
        $code = str_replace(['<?php', '?>'], '', $code);
        ob_start();
        try {
            eval($code);
            return [
                'result' => ob_get_contents(),
            ];
        } catch (Throwable $e) {
            throw new ToolCallException($e->getMessage(), previous: $e);
        } finally {
            ob_end_clean();
        }
    }

    #[McpTool(
        name: 'system_build_phar',
        title: '打包项目为PHAR文件',
        description: '将整个项目代码打包为PHAR归档文件，便于分发和部署',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'result' => ['type' => 'string'],
            ],
            'required' => ['result'],
        ]
    )]
    public function buildPhar(): array
    {
        if (!class_exists(BuildPharCommand::class)) {
            throw new ToolCallException('当前环境暂不支持执行此tool');
        }
        return [
            'result' => McpHelper::fetch_console(BuildPharCommand::class),
        ];
    }

    #[McpTool(
        name: 'system_build_bin',
        title: '打包项目为Linux二进制文件',
        description: '将整个项目代码打包为Linux单文件二进制可执行程序，无需PHP环境即可独立运行',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'result' => ['type' => 'string'],
            ],
            'required' => ['result'],
        ]
    )]
    public function buildBin(): array
    {
        if (!class_exists(BuildBinCommand::class)) {
            throw new ToolCallException('当前环境暂不支持执行此tool');
        }
        return [
            'result' => McpHelper::fetch_console(BuildBinCommand::class),
        ];
    }
}
