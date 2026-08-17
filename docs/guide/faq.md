# 常见问题 (FAQ)

### Q: 为什么在 Windows 系统上以 STDIO 模式运行服务时，定时器或异步客户端不起作用？
**A**: 在 Windows 平台下，PHP 的标准输入输出流（STDIO）无法被设置为非阻塞模式。这意味着当服务器在等待客户端输入时，底层的 Workerman 事件循环将被完全阻塞。
因此，在 Windows 上以 `mcp:server` 命令行模式运行时，任何依赖 Workerman 非阻塞事件循环的功能（例如 `Timer` 定时器、异步 HTTP 客户端、协程等）都将无法正常工作。如果您必须在本地 Windows 环境调试这类功能，建议使用 **Streamable HTTP (SSE) 传输模式**启动主服务：
```bash
php webman start
```

---

### Q: 为什么我修改了工具或资源的代码，AI 客户端却没有立即看到变化？
**A**:
1. **进程缓存**：Webman 是常驻内存的框架。在开发环境下，如果您修改了代码，通常需要重启 Webman 服务（HTTP 模式下）或重新启动命令行（STDIO 模式下）以使新注解生效。
2. **连接缓存**：部分 AI 客户端（如 Cursor / Claude Desktop）在连接建立时只会请求一次工具列表（`tools/list`）。如果您新增或删除了注解，除了重启服务端外，您可能还需要在 AI 客户端中点击 **“Refresh”** 或重新载入窗口以重新拉取服务能力。

---

### Q: 为什么我不应该在代码中添加 `declare(strict_types=1)`？
**A**: 本项目在代码规范上做出了统一规定。为了与整个插件的编码风格以及 PHP-CS-Fixer 的配置（`declare_strict_types => false`）保持一致，**请不要**在任何新写的 PHP 文件顶部添加 `declare(strict_types=1)`。否则，在 CI 流程的代码风格检查（`composer cs:check`）中会被强制报错拦截。

---

### Q: 如何在 Cursor 或 Claude Desktop 中配置我的本地 STDIO 服务？
**A**:
您可以在客户端的配置文件（例如 Claude Desktop 的 `claude_desktop_config.json`）中，添加您的 Webman 服务的 STDIO 启动指令。配置示例如下：

```json
{
  "mcpServers": {
    "webman-mcp": {
      "command": "php",
      "args": [
        "E:/workerman/webman-mcp-demo/webman",
        "mcp:server",
        "mcp"
      ]
    }
  }
}
```
*请注意将路径替换为您本地 Webman 对应项目的真实绝对路径。*
