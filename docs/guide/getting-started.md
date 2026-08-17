# 快速开始

`webman-mcp` 是一个为 Webman 框架量身定制的 Model Context Protocol (MCP) 服务器插件，旨在让 PHP 开发者能以最简单、优雅的方式，将应用能力和数据源暴露给各类 AI 客户端（如 Claude Desktop, Cursor 等）。

## 安装

在您的 Webman 项目根目录下，运行以下 Composer 命令安装插件：

```bash
composer require luoyue/webman-mcp
```

### 环境要求

- **PHP** >= 8.1
- **webman-framework** ^2.1

### 可选依赖

根据您需要使用的功能，您可能需要安装以下包：
- `webman/cache`：用于 session 的持久化存储。
- `webman/redis`：使用 Redis 内置开发工具时的必要依赖。
- `webman/event`：如果您需要监听 MCP 的生命周期钩子或捕获列表变更通知。
- **Swoole / Swow / Fiber 协程**：在 SSE (Server-Sent Events) 场景下能显著提升吞吐和连接性能。
- `monolog/monolog`：记录详细的 MCP 服务端错误与访问日志。

---

## 启动方式

根据配置文件的传输方式（STDIO / HTTP），您可以使用不同的方式启动服务：

### 1. STDIO 传输模式
主要用于本地命令行或 Cursor/Claude 桌面版直接调起。使用命令启动（`mcp` 是在配置文件中定义的服务名称）：

```bash
php webman mcp:server mcp
```

### 2. HTTP 传输模式（含 SSE）
直接启动 Webman 主服务即可。插件会自动监听路由或开启独立进程：

```bash
php webman start
```

---

## 快速上手

### 1. 创建模板代码
您可以通过以下命令生成一份脚手架模版，借此快速学习 MCP 服务的结构：

```bash
php webman mcp:make template
```

### 2. 测试与调试
您可以使用我们为您准备的快速调试命令。它将借助 Node.js 的 `npx` 启动官方的 **MCP Inspector** 可视化测试工具：

```bash
php webman mcp:inspector mcp
```
打开浏览器中的调试地址，您就可以直观地查看、测试和调用当前服务中定义的所有工具、资源以及提示词了。
