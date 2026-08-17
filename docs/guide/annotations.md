# 注解与开发

在 `webman-mcp` 中，您不需要编写复杂的路由或配置文件来手动注册每个功能，只需使用 PHP 8 原生注解（Attributes）即可快速将方法注册为 MCP 元素。

默认的注解扫描器 `WebmanDiscoverer` 会查找类或方法上的注解。

---

## 核心注解

### 1. `McpTool`
标记一个公共非静态方法为 **MCP 工具（Tool）**，允许客户端/AI 发现并调用该方法。

* **属性**：
  * `name` (string)：工具唯一名称。
  * `description` (string)：工具功能描述，这是给 AI 读的，非常重要。
  
* **示例**：
  ```php
  #[McpTool(name: 'calculate_sum', description: '计算两个数字的和')]
  public function sum(int $a, int $b): int
  {
      return $a + $b;
  }
  ```

### 2. `McpResource`
标记一个公共非静态方法为 **MCP 资源处理器（Resource）**，用于返回只读数据（例如特定日志、配置信息或静态数据）。

* **属性**：
  * `uri` (string)：资源的唯一 URI。
  * `name` (string)：资源名称。
  * `description` (string)：资源描述。
  * `mimeType` (string, 可选)：返回内容的 MIME 类型。

* **示例**：
  ```php
  #[McpResource(uri: 'app://config/main', name: '系统核心配置', description: '获取应用的核心配置项')]
  public function mainConfig(): array
  {
      return config('app');
  }
  ```

### 3. `McpResourceTemplate`
标记一个公共非静态方法为 **MCP 资源模板（Resource Template）**。与静态资源不同，资源模板的 URI 允许包含占位符（例如 `{id}`），用来匹配和处理一类动态请求。

* **属性**：
  * `uriTemplate` (string)：包含占位符的 URI 模板。
  * `name` (string)：资源模板的名称。
  * `description` (string)：描述。
  * `mimeType` (string, 可选)：返回内容的 MIME 类型。

* **示例**：
  ```php
  #[McpResourceTemplate(uriTemplate: 'app://users/{userId}/profile', name: '用户个人资料', description: '根据用户ID获取详细的用户画像')]
  public function userProfile(string $userId): array
  {
      return [
          'userId' => $userId,
          'name' => '用户_' . $userId,
          'role' => 'editor',
      ];
  }
  ```

### 4. `McpPrompt`
标记一个公共非静态方法为 **MCP 提示词模版（Prompt）**。提示词是预先设定好的对话或上下文引导，供用户或客户端快速启动特定风格/任务的对话。

* **属性**：
  * `name` (string)：提示词模版唯一名称。
  * `description` (string)：功能或用途描述。

* **示例**：
  ```php
  #[McpPrompt(name: 'code_reviewer', description: '要求 AI 扮演代码审查专家')]
  public function codeReviewerPrompt(): string
  {
      return "请作为一名资深 PHP 架构师，严格审查以下提交的代码，指出其中可能存在的安全漏洞和性能隐患。";
  }
  ```

---

## 辅助注解

### 1. `Schema`
用于定义方法参数的验证规则。由于 AI 输入的都是 JSON/字符串，您可以使用 `Schema` 注解声明参数所对应的 JSON Schema 结构，以确保传入的参数合法，并能被自动映射为对应的方法参数类型。

* **示例**：
  ```php
  use Mcp\Schema\Annotations\Schema;

  #[McpTool(name: 'send_email', description: '发送邮件给指定用户')]
  #[Schema([
      'type' => 'object',
      'properties' => [
          'email' => ['type' => 'string', 'format' => 'email', 'description' => '收件人邮箱地址'],
          'content' => ['type' => 'string', 'description' => '邮件正文内容']
      ],
      'required' => ['email', 'content']
  ])]
  public function sendEmail(string $email, string $content): bool
  {
      // 发送逻辑...
      return true;
  }
  ```

### 2. `CompletionProvider`
用于为方法参数提供**自动完成（Auto-complete）**功能。当客户端输入时，可以向服务器发起请求以补全该参数的可选值。

* **属性**：
  * `ref` (string)：引用或提供者的名称。

---

## 最佳实践与兼容设计

### 1. 类级别注解与 `__invoke`
除了在类的方法上声明注解，您还可以将类本身设计为单功能类。此时，只需要在类级别声明注解，并实现 `__invoke` 方法即可：

```php
#[McpTool(name: 'generate_uuid', description: '生成一个标准的 UUID v4')]
class UuidGenerator
{
    public function __invoke(): string
    {
        return \support\Util::uuid();
    }
}
```

### 2. 在 Webman Controller 中复用 MCP 工具
如果您希望同一个方法既能作为 Webman 的 HTTP Controller 控制器响应网页请求，又能作为 MCP Tool 响应 AI 的调用，可以采用以下写法：

```php
use Mcp\Server\RequestContext;
use Luoyue\WebmanMcp\McpHelper;
use Workerman\Protocols\Http\Response;

class McpController
{
    /**
     * @param RequestContext|null $context MCP请求上下文（设置为可选参数以兼容 Webman Controller）
     */
    #[McpTool(name: 'example_tool')]
    public function exampleTool(?RequestContext $context): Response|array
    {
        $result = [
            'status' => 'ok',
            'params' => request()->all(),
        ];
        
        // 只有在 MCP 调用时向客户端打印日志，Webman 浏览器调用会安全忽略此行
        $context?->getClientLogger()->info('example_tool', $result);
        
        // 使用 McpHelper::is_mcp_server_request() 区分来源：
        // 如果是 MCP 客户端请求，返回原始数组；若是浏览器请求，包装为 Webman Response 对象
        return McpHelper::is_mcp_server_request() ? $result : response($result);
    }
}
```
通过以上方式，您可以轻松共享业务逻辑，实现控制器与 MCP 工具的完美共存。
