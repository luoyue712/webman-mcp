# AGENTS.md

## 这是什么

`webman-mcp` 是一个 Composer **库 / webman 插件**（`luoyue/webman-mcp`），封装官方 MCP PHP SDK（`mcp/sdk ^0.6`）。它**不是独立应用**：`src/` 中的代码只在 webman 应用内运行，依赖 webman 全局函数/类（`config()`、`base_path()`、`runtime_path()`、`request()`、`response()`、`support\Container`、`support\Context`）。PHP >= 8.1。

## 目录结构

- `src/` — 插件源码，PSR-4 `Luoyue\WebmanMcp\`
- `src/config/plugin/luoyue/webman-mcp/` — 默认配置，由 `src/Install.php` 复制到应用目录 `config/plugin/luoyue/webman-mcp/`
- `src/Runner/` — 接线层：`McpRouterRunner`（路由）、`McpProcessRunner`（workerman 进程）、`McpAutoLoadRunner`（bootstrap，仅 Windows）、`McpCommandRunner`（控制台命令）
- `src/Command/` — 控制台命令：mcp:server / mcp:list / mcp:make / mcp:inspector
- `src/DevMcp/` — 内置开发工具（System/Redis/Database）；**PHPStan 排除**
- `tests/` — PSR-4 `Luoyue\WebmanMcp\Tests\`；`tests/Bootstrap.php` 从 `tests/config/` 加载 webman `Config`（配置前缀 `plugin.luoyue.webman-mcp`）
- `tests/phpunit/` — 单元测试套件 `unit`；`tests/Conformance/` — conformance 服务器与预期失败基线

## 命令

- 单元测试：`vendor/bin/phpunit --testsuite=unit`（没有 `composer test` 脚本）
- 风格：`composer cs:check` / `composer cs:fix` — fixer 只扫描 `./src`，**不扫描 tests**
- 静态分析：`composer phpstan`（level 6；扫描 `src/` 和 `tests/`，排除 `src/DevMcp/`；少量 ignoreErrors 固定在 `phpstan.dist.neon`）
- CI（`.github/workflows/pipeline.yml`，触发：pull_request）的 qa job 顺序：`composer validate --strict` → `vendor/bin/php-cs-fixer fix --dry-run` → `vendor/bin/phpstan analyse`
- CI 另有 unit job：PHP 8.1–8.5 × lowest/highest 依赖矩阵；conformance job：PHP 8.1 × 扩展 pcntl/event/ev/swoole/swow/fiber 矩阵（扩展矩阵仅 conformance job，非 unit job）

## 架构 / 入口

- `McpServerManager::start($name)` 是唯一分发点（src/McpServerManager.php:105），依据 `$_ENV['MCP_STDIO']` 分支 STDIO 与 HTTP 传输。
- STDIO 模式：`php webman mcp:server <service>` → `McpStdioCommand` 设置 `$_ENV['MCP_STDIO']=true`（src/Command/McpStdioCommand.php:24）。
- HTTP 路由模式：`McpRouterRunner` 对 `transport.streamable_http.router.enable=true` 的服务注册 `Route::any($endpoint, ...)`。
- HTTP 进程模式：`McpProcessRunner::create()` 从 `transport.streamable_http.process` 构建 workerman 进程配置（由插件 `process.php` 返回）；每个端口的端点必须唯一。
- 配置从 `config('plugin.luoyue.webman-mcp.mcp')` 读取，由 `McpServerManager::loadConfig()` 每进程缓存一次（static `$isInit` 标志）。
- 常量 `McpServerManager::PLUGIN_REWFIX` 故意拼错（"REWFIX"）；用它而不要用字面量。
- 工具/提示/资源注解通过 `DiscoveryLoader` + `WebmanDiscoverer` 扫描 `app/mcp` 发现。

## 注意事项

- **不要**加 `declare(strict_types=1)` — cs-fixer 配置强制 `declare_strict_types => false`。
- `transport.streamable_http.middleware` 接受 PSR-15 中间件对象或类名字符串（类字符串通过 `Container` 解析）。
- Windows 上 STDIO 传输无法非阻塞，因此 `mcp:server` 模式下定时器/协程/http-client 不可用（README 有说明）。
- Conformance：CI 先 `php tests/Conformance/server.php start -d`（服务 `conformance` 来自 `tests/config/mcp.php`，端口 8000，端点 `/mcp`），再运行 `modelcontextprotocol/conformance`，预期失败固定在 `tests/Conformance/conformance-baseline.yml`。
- README、提交信息、配置注释均为中文。
