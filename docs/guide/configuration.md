# 配置详解

插件的所有核心配置都可以在 `config/plugin/luoyue/webman-mcp/mcp.php` 中找到。该文件定义了服务器的基本信息、能力支持、Session 管理、注解扫描路径以及传输层行为。

## 配置结构总览

以下是一个完整的配置示例结构：

```php
return [
    'mcp' => [ // 这里的 'mcp' 为服务/服务器名称
        'configure' => function (\Mcp\Server\Builder $server) {
            // ... 服务器基本信息和能力注册
        },
        'transport' => [
            'stdio' => [
                'enable' => true,
            ],
            'streamable_http' => [
                'endpoint' => '/mcp',
                'middleware' => [ ... ],
                'router' => [
                    'enable' => true,
                ],
                'process' => [
                    'enable' => false,
                    'port' => 8080,
                    'count' => 1,
                ],
            ],
        ],
    ],
];
```

---

## 核心配置项

### 1. `configure` 闭包
该闭包会接收一个 `\Mcp\Server\Builder $server` 实例，用于通过链式或方法调用来初始化 MCP 服务器的特性：

* **服务描述与信息 (`setServerInfo`)**  
  定义服务器的名称、版本、介绍、Logo 图标以及官网链接等。
* **协议版本 (`setProtocolVersion`)**  
  指定支持的 MCP 规范版本，例如 `ProtocolVersion::V2025_06_18`。
* **自定义使用说明 (`setInstructions`)**  
  可以传入一段给客户端的系统提示词或说明文档（Instructions）。
* **功能能力开启 (`setCapabilities`)**  
  配置需要开启的能力，如：
  * `tools`：是否启用工具。
  * `toolsListChanged`：工具列表变更时的事件通知（默认为 `WebmanEvent::installed()`，当检测到对应事件类时自动触发通知）。
  * `resources` & `resourcesSubscribe`：资源及订阅能力。
  * `prompts`：提示词能力。
  * `logging`：日志收集服务。
  * `completions`：参数自动补全。
* **日志处理器 (`setLogger`)**  
  用于记录 MCP 服务内部的异常或调试日志。您可以使用自带的 `mcp_error_stderr` 通道（开发环境建议输出到标准错误流），或者 `mcp_file_log`（将日志写入 runtime 目录的文件中）。
  
  ::: tip 提示
  使用自带的日志通道时，名称需要加上 `\Luoyue\WebmanMcp\McpServerManager::PLUGIN_REWFIX` 拼写拼装前缀：
  ```php
  $server->setLogger(\support\Log::channel(
      \Luoyue\WebmanMcp\McpServerManager::PLUGIN_REWFIX . 'mcp_error_stderr'
  ));
  ```
  :::
* **Session 存储机制 (`setSession`)**  
  * `FileSessionStore`（默认）：内置文件存储，无外部依赖，默认保存在 `runtime/mcp/session`。
  * `WebmanSessionStore`：利用 Webman 的 `webman/cache` 组件进行持久化存储。
* **注解扫描 (`addLoader`)**  
  向服务注册注解加载器 `DiscoveryLoader`。可以定义需要扫描的业务路径，例如 `['app/mcp']`，插件会扫描这些目录中包含相关 MCP 注解的类和方法。
* **开发环境工具 (`DevelopmentMcpLoader`)**  
  如果开启了 `app.debug`，默认会额外挂载开发工具类，让您可以直接控制或观测容器、Redis 以及数据库等。

---

## 2. 传输协议配置 (`transport`)

### STDIO 传输 (`transport.stdio`)
* `enable` (bool)：是否启用 STDIO 传输。当您通过命令行 `php webman mcp:server mcp` 启动时，必须启用此选项。

### Streamable HTTP 传输 (`transport.streamable_http`)
用于运行在 Web 端口（通过 SSE）的场景，它支持两种分发模式：

#### 路由模式 (`router`)
* `enable` (bool)：如果为 `true`，插件将在路由初始化时，自动往 Webman 路由中挂载对应 `endpoint` 的路由。
* **特点**：无需占用额外端口，直接与 Webman 主应用共用同一端口，十分轻量。

#### 自定义进程模式 (`process`)
* `enable` (bool)：是否启用独立自定义 Workerman 进程监听。
* `port` (int)：独立进程所监听的端口，例如 `8080`。每个服务的端口和 endpoint 组合必须是唯一的。
* `count` (int)：工作进程数，配合高并发或协程环境使用。
* **特点**：独立于 Webman 的主路由流程，对连接和并发有更好的控制力，且更容易通过 Workerman 配置独立的进程池。

---

## 3. 中间件配置 (`middleware`)

`transport.streamable_http.middleware` 接受一个数组。数组项可以是 **PSR-15 中间件对象** 或者是 **中间件的类名字符串**。如果传入类名，它会通过 Webman 容器 `support\Container` 自动解析，以方便依赖注入。
例如，用于解决跨域的 `CorsMiddleware`：
```php
'middleware' => [
    new \Mcp\Server\Transport\Http\Middleware\CorsMiddleware(allowedOrigins: ['*']),
],
```

---

## 4. 全局应用配置 (`app.php`)

在 `config/plugin/luoyue/webman-mcp/app.php` 中：
* `enable` (bool)：控制该插件是否全局启用。
* `event_mode` (string)：事件分发模式，取值为 `'dispatch'` 或 `'emit'`，符合 Webman 事件组件 `webman/event` 的用法。当 MCP 数据发生变更（如资源更新、工具更新）需要通知客户端时会触发事件。
