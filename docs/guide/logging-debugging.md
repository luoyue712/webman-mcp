# 日志与调试

正确配置日志和熟练进行服务调试是保障 MCP 服务器稳定运行的关键。根据 Model Context Protocol 的规范，日志记录和错误排查有着特定的约束。

---

## 服务器日志记录

### STDIO 传输下的日志规范
根据 `2025-11-25` 规范，STDIO 传输模式中：
* **`stdout` (标准输出)** 必须**仅且只能**用于传输 JSON-RPC 协议消息。严禁将任何普通程序日志或错误输出到 `stdout`，否则客户端会因解析失败而导致崩溃或断开连接。
* **`stderr` (标准错误)** 允许写入任意普通日志，客户端（如 Cursor / Claude）会捕获 `stderr` 里的输出并视其为非致命的诊断信息。

因此，根据传输协议的不同，我们有以下日志输出兼容矩阵：

| 日志输出目标 | STDIO 传输模式 | Streamable HTTP 传输模式 |
| :--- | :---: | :---: |
| 写入磁盘文件 (File) | ✅ 支持 | ✅ 支持 |
| 标准错误流 (Stderr) | ✅ 支持 | ✅ 支持 |
| 标准输出流 (Stdout) | ❌ 禁用 | ✅ 支持 |

* **开发环境**：推荐将日志定向到 `stderr`，这样您可以直接在命令行控制台查看到实时输出，而不会干扰 STDIO 协议。
* **生产环境**：推荐定向到磁盘文件 (File)，以便后续收集和回溯。

---

## 配置日志通道

首先，在插件的日志配置目录 `config/plugin/luoyue/webman-mcp/log.php` 中配置您的 Monolog 通道：

```php
<?php

return [
    // 1. 写入文件的日志通道
    'mcp_file_log' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/mcp.log',
                    7, //$maxFiles
                    Monolog\Logger::NOTICE,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ]
    ],
    // 2. 输出到 stderr 的日志通道
    'mcp_error_stderr' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\StreamHandler::class,
                'constructor' => [
                    STDERR, // 指定输出流为 stderr
                    Monolog\Logger::NOTICE, //NOTICE级及以上才输出，减少低级调试噪声
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ]
    ]
];
```

### 在 mcp.php 中启用日志
在 `mcp.php` 的 `configure` 闭包中，通过调用 `$server->setLogger(...)` 将日志通道绑定到 MCP 服务器。
需要注意，通道名称需通过插件前缀拼装：

```php
'configure' => function (\Mcp\Server\Builder $server) {
    // ... 其他初始化设置
    
    // 依据当前的 debug 状态，在控制台 stderr 和文件日志之间智能切换
    $server->setLogger(\support\Log::channel(
        \Luoyue\WebmanMcp\McpServerManager::PLUGIN_REWFIX .
        (config('app.debug', true) ? 'mcp_error_stderr' : 'mcp_file_log')
    ));
}
```

---

## 调试与诊断

### 1. 本地调试（MCP Inspector）
在开发时，您可以通过内置的 Inspector 调试命令轻松连接并测试服务：

```bash
php webman mcp:inspector mcp
```
这将在本地自动调起 Node.js 的 MCP Inspector 工具，提供直观的 Web UI。您可以在页面上看到所有自动发现的 Tool, Resource, Prompt，并可以直接在页面上填写参数发起请求调试。

### 2. 命令行单元调试
如果您不想开启 Web 页面，可以通过命令行直接测试业务方法：

* **测试工具列表**：
  ```bash
  php webman mcp:tools mcp
  ```
* **测试特定工具**：
  ```bash
  php webman mcp:tools:call mcp example_tool '{"param1": "test"}'
  ```
* **测试读取资源**：
  ```bash
  php webman mcp:resources:read mcp app://config/main
  ```
