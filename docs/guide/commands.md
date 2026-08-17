# 命令行工具

`webman-mcp` 提供了一系列丰富的命令行（CLI）工具，方便您在终端进行服务管理、脚手架生成以及调用调试。

---

## 常用命令一览

| 命令 | 参数 | 描述 |
| :--- | :--- | :--- |
| `mcp:server` | `service` | 以 STDIO 模式启动指定的 MCP 服务器 |
| `mcp:list` | - | 列出所有定义的 MCP 服务及其配置概览 |
| `mcp:make` | `type` | 生成 MCP 默认配置或模版文件 |
| `mcp:inspector` | `service` | 启动官方 MCP Inspector 可视化调试工具 |
| `mcp:tools` | `service` | 列出指定服务下所有的工具、资源、提示词及 Schema 定义 |
| `mcp:tools:call` | `service`, `tool`, `[json]` | 执行指定服务中的工具并返回结果 |
| `mcp:resources:read` | `service`, `uri` | 读取指定服务中的资源内容 |

---

## 命令详解与示例

### 1. 启动服务：`mcp:server`
以 STDIO 模式运行 MCP 服务。通常是交给客户端（如 Cursor / Claude Desktop）自动唤起，也可以在本地终端手动测试：

```bash
php webman mcp:server mcp
```

### 2. 查看服务列表：`mcp:list`
显示当前项目中配置的所有 MCP 服务及其简要状态（如是否开启 STDIO、HTTP 传输路径等）：

```bash
php webman mcp:list
```

### 3. 生成配置或代码：`mcp:make`
快速生成所需的配置模版或工具模版。
* 生成配置文件（mcp.php）：
  ```bash
  php webman mcp:make config
  ```
* 生成模板业务代码：
  ```bash
  php webman mcp:make template
  ```

### 4. 调试利器：`mcp:inspector`
在本地极速启动官方的 MCP Inspector（可视化调试控制台）。需要本地有 Node.js 环境：

```bash
php webman mcp:inspector mcp
```

### 5. 列出所有元素：`mcp:tools`
在终端直接打印出指定服务当前已注册的所有工具、资源、提示词以及它们的 JSON Schema 定义，无需启动 Inspector：

```bash
php webman mcp:tools conformance
```

### 6. 调用特定工具：`mcp:tools:call`
在终端模拟客户端调用某个工具，可传入 JSON 字符串参数。
* 默认美化文本输出：
  ```bash
  php webman mcp:tools:call conformance test_simple_text
  ```
* 指定参数调用：
  ```bash
  php webman mcp:tools:call conformance test_simple_text '{"param1": "hello"}'
  ```
* 输出纯 JSON 格式数据：
  ```bash
  php webman mcp:tools:call conformance test_simple_text --format=json
  ```
> **注意**：如果工具在执行中依赖客户端的实时交互（如 sampling、elicitation 协议），则无法在命令行脱机运行，必须在 HTTP 或 STDIO 传输的真实会话中运行。

### 7. 读取资源内容：`mcp:resources:read`
直接从终端读取指定的静态资源或通过模板匹配到的动态资源：

```bash
# 读取静态资源
php webman mcp:resources:read conformance test://static-text

# 读取资源模版（带参数）
php webman mcp:resources:read conformance test://template/abc123/data --format=json
```
