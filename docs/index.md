---
layout: home

hero:
  name: "webman-mcp"
  text: "Webman Model Context Protocol (MCP)"
  tagline: 这是一个 Webman 框架与官方 MCP PHP SDK 深度集成的插件，并在 SDK 基础上进行了扩展，可快速创建 MCP 服务器。
  actions:
    - theme: brand
      text: 快速开始
      link: /guide/getting-started
    - theme: alt
      text: GitHub 仓库
      link: https://github.com/luoyue712/webman-mcp

features:
  - title: 一键启动
    details: 安装后即可启动，同时支持配置复杂的功能，方便快捷。
  - title: 多服务器隔离
    details: 一个项目支持多个 MCP 服务器，并按服务器名称隔离配置。
  - title: 深度集成
    details: 与 Webman 框架深度集成，HTTP 支持路由模式和自定义进程模式。
  - title: 多种传输协议
    details: 支持 STDIO、Streamable HTTP 高性能传输，满足不同客户端对接需求。
  - title: 协程与非协程
    details: 支持协程（Swoole/Swow/Fiber）与非协程环境，从而提高了在 SSE 场景下高性能传输。
  - title: 内置开发工具
    details: 内置 18 个 MCP 开发工具（系统、Redis、数据库），极大提升开发与调试效率。
---
