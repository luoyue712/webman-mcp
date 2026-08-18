# 内置开发工具

`webman-mcp` 贴心地为您内置了 18 个开发辅助工具（隶属 `DevMcp` 命名空间），用于在开发、调试阶段向 AI 客户端提供直接观测、分析甚至修改系统状态的能力。

::: warning 安全警告
内置工具涵盖了动态执行 PHP 代码、查询数据库以及执行 Redis 命令等高危操作。因此，**它们仅限在开发调试环境下使用，千万不要在生产环境开启。**
:::

---

## 如何开启

默认情况下，这些工具在配置文件中是通过以下判断，仅在 Webman 开启 `app.debug` 时才会加载：

```php
// config/plugin/luoyue/webman-mcp/mcp.php
'configure' => function (Builder $server) {
    // ...
    // 添加开发环境工具，仅在 debug 模式下启用
    config('app.debug') && $server->addLoader(new DevelopmentMcpLoader);
}
```

如果您想在生产环境完全剔除这些内置工具，可以直接注释或删掉该行代码。

---

## 内置工具列表

### 1. 系统与环境工具 (System)

| 工具名称 | 描述 | 参数 |
| :--- | :--- | :--- |
| `system_info` | 获取当前服务器操作系统、PHP版本等基础环境信息 | 无 |
| `system_config` | 获取 Webman 指定应用配置项的值（如 database, app 等） | `key` (string) |
| `system_env` | 获取当前环境变量值，默认脱敏敏感 key | `key` (string, 可选) |
| `system_php_ini` | 获取 PHP 配置信息（ini 设置） | `key` (string, 可选) |
| `system_dependencies` | 读取项目 `composer.json` 中的依赖包及版本 | 无 |
| `system_extensions` | 获取 PHP 当前加载的扩展列表和可用函数 | 无 |
| `system_routes` | 获取 Webman 注册的所有 HTTP 路由列表 | 无 |
| `system_match_routes` | 输入特定 URL，返回匹配该 URL 的 Webman 路由详情 | `url` (string), `method` (string) |
| `system_events` | 获取 Webman 当前监听的所有事件列表 | 无 |
| `system_eval_code` | **[高危]** 执行任意传入的 PHP 代码片段并返回输出 | `code` (string) |
| `system_build_phar` | 将当前项目打包成可独立执行的 `.phar` 文件 | 无 |
| `system_build_bin` | 将当前项目编译并打包为 Linux 独立二进制可执行文件 | 无 |

### 2. 数据库辅助工具 (Database)
当您的 Webman 项目安装了 `webman/database` 且配置了连接后起效。

| 工具名称 | 描述 | 参数 |
| :--- | :--- | :--- |
| `database_connections` | 获取当前定义的所有数据库连接名称及配置概览 | 无 |
| `database_execute_sql` | **[高危]** 在指定的数据库连接上执行原始 SQL 语句（支持参数绑定） | `sql` (string), `bindings` (array, 可选), `connection` (string, 可选) |

### 3. Redis 辅助工具 (Redis)
当您的 Webman 项目安装了 `webman/redis` 且配置了连接后起效。

| 工具名称 | 描述 | 参数 |
| :--- | :--- | :--- |
| `redis_connections` | 获取当前定义的所有 Redis 连接名称及配置概览 | 无 |
| `redis_execute_raw` | **[高危]** 在指定的 Redis 连接上执行任意原始 Redis 命令 | `command` (string), `args` (array, 可选), `connection` (string, 可选) |
| `redis_execute_lua` | **[高危]** 在 Redis 实例中执行指定的 Lua 脚本 | `script` (string), `keys` (array, 可选), `args` (array, 可选), `connection` (string, 可选) |
| `redis_execute_lua_sha` | **[高危]** 通过 SHA1 校验值在 Redis 实例中执行预缓存的 Lua 脚本 | `sha` (string), `keys` (array, 可选), `args` (array, 可选), `connection` (string, 可选) |
