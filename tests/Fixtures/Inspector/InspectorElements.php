<?php

namespace Luoyue\WebmanMcp\Tests\Fixtures\Inspector;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;

/**
 * 供 tests/Inspector 套件（inspector CLI 模式）使用的 MCP 元素集合。
 *
 * 通过 DiscoveryLoader 注解扫描注册，对应官方 php-sdk examples/server 中
 * cached-discovery、custom-dependencies、discovery-calculator、env-variables、
 * explicit-registration 的注册内容；mcp-apps 因携带运行时 meta 对象，
 * 在 tests/config/mcp.php 中通过 Builder 显式注册。
 */
final class InspectorElements
{
    /**
     * 模拟 discovery-calculator 的配置状态。
     *
     * @var array{precision: int, allow_negative: bool}
     */
    private static array $calculatorConfig = [
        'precision' => 2,
        'allow_negative' => true,
    ];

    /**
     * 模拟 custom-dependencies 的内存任务仓库。
     *
     * @var array{nextId: int, tasks: array<int, array{id: int, userId: string, description: string, completed: bool, createdAt: string}>}
     */
    private static array $taskRepository = [
        'nextId' => 4,
        'tasks' => [
            1 => ['id' => 1, 'userId' => 'user1', 'description' => 'Buy groceries', 'completed' => false, 'createdAt' => '2025-01-01T00:00:00+00:00'],
            2 => ['id' => 2, 'userId' => 'user1', 'description' => 'Write MCP example', 'completed' => false, 'createdAt' => '2025-01-01T00:00:00+00:00'],
            3 => ['id' => 3, 'userId' => 'user2', 'description' => 'Review PR', 'completed' => false, 'createdAt' => '2025-01-01T00:00:00+00:00'],
        ],
    ];

    // ---- 对应官方 examples/server/cached-discovery ----

    #[McpTool(name: 'add_numbers', description: 'Adds two numbers')]
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    #[McpTool(name: 'multiply_numbers', description: 'Multiplies two numbers')]
    public function multiply(int $a, int $b): int
    {
        return $a * $b;
    }

    #[McpTool(name: 'divide_numbers', description: 'Divides two numbers')]
    public function divide(int $a, int $b): float
    {
        if (0 === $b) {
            throw new ToolCallException('Division by zero is not allowed.');
        }

        return $a / $b;
    }

    #[McpTool(name: 'power', description: 'Raises a base number to an exponent power')]
    public function power(int $base, int $exponent): int
    {
        return (int) $base ** $exponent;
    }

    // ---- 对应官方 examples/server/discovery-calculator ----

    #[McpTool(name: 'calculate', description: 'Performs a calculation based on the operation')]
    public function calculate(float $a, float $b, string $operation): float
    {
        $op = strtolower($operation);
        $result = match ($op) {
            'add' => $a + $b,
            'subtract' => $a - $b,
            'multiply' => $a * $b,
            'divide' => 0 == $b ? throw new ToolCallException('Division by zero is not allowed.') : $a / $b,
            default => throw new ToolCallException("Unknown operation '{$operation}'. Supported: add, subtract, multiply, divide."),
        };
        if (!self::$calculatorConfig['allow_negative'] && $result < 0) {
            throw new ToolCallException('Negative results are disabled.');
        }

        return round($result, self::$calculatorConfig['precision']);
    }

    /**
     * @param mixed $value the new value (int for precision, bool for allow_negative)
     */
    #[McpTool(name: 'update_setting', description: 'Updates a specific configuration setting')]
    public function updateSetting(string $setting, mixed $value): array
    {
        if (!array_key_exists($setting, self::$calculatorConfig)) {
            return ['success' => false, 'error' => "Unknown setting '{$setting}'."];
        }
        if (is_string($value)) {
            if (in_array(strtolower($value), ['true', '1', 'yes', 'on'])) {
                $value = true;
            } elseif (in_array(strtolower($value), ['false', '0', 'no', 'off'])) {
                $value = false;
            } elseif (is_numeric($value)) {
                $value = (int) $value;
            }
        }
        if ('precision' === $setting) {
            if (!is_int($value) || $value < 0 || $value > 10) {
                return ['success' => false, 'error' => 'Invalid precision value. Must be integer between 0 and 10.'];
            }
            self::$calculatorConfig['precision'] = $value;

            return ['success' => true, 'message' => "Precision updated to {$value}."];
        }
        if (!is_bool($value)) {
            return ['success' => false, 'error' => 'Invalid allow_negative value. Must be boolean (true/false).'];
        }
        self::$calculatorConfig['allow_negative'] = $value;

        return ['success' => true, 'message' => 'Allow negative results set to ' . ($value ? 'true' : 'false') . '.'];
    }

    /**
     * @return array{precision: int, allow_negative: bool}
     */
    #[McpResource(
        uri: 'config://calculator/settings',
        name: 'calculator_config',
        description: 'Current settings for the calculator tool (precision, allow_negative).',
        mimeType: 'application/json',
    )]
    public function getConfiguration(): array
    {
        return self::$calculatorConfig;
    }

    // ---- 对应官方 examples/server/custom-dependencies ----

    /**
     * @return array{id: int, userId: string, description: string, completed: bool, createdAt: string}
     */
    #[McpTool(name: 'add_task', description: 'Adds a new task for a given user')]
    public function addTask(string $userId, string $description): array
    {
        $task = [
            'id' => self::$taskRepository['nextId']++,
            'userId' => $userId,
            'description' => $description,
            'completed' => false,
            'createdAt' => date('c'),
        ];
        self::$taskRepository['tasks'][$task['id']] = $task;

        return $task;
    }

    /**
     * @return array<int, array{id: int, userId: string, description: string, completed: bool, createdAt: string}>
     */
    #[McpTool(name: 'list_user_tasks', description: 'Lists pending tasks for a specific user')]
    public function listUserTasks(string $userId): array
    {
        return array_values(array_filter(self::$taskRepository['tasks'], static fn ($task) => $task['userId'] === $userId && !$task['completed']));
    }

    /**
     * @return array{success: bool, message: string}
     */
    #[McpTool(name: 'complete_task', description: 'Marks a task as complete')]
    public function completeTask(int $taskId): array
    {
        if (isset(self::$taskRepository['tasks'][$taskId])) {
            self::$taskRepository['tasks'][$taskId]['completed'] = true;

            return ['success' => true, 'message' => "Task {$taskId} completed."];
        }

        return ['success' => false, 'message' => "Task {$taskId} not found."];
    }

    /**
     * @return array<string, int>
     */
    #[McpResource(
        uri: 'stats://system/overview',
        name: 'system_stats',
        description: 'Provides current system statistics',
        mimeType: 'application/json',
    )]
    public function getSystemStatistics(): array
    {
        $allTasks = array_values(self::$taskRepository['tasks']);
        $completed = count(array_filter($allTasks, static fn ($task) => $task['completed']));
        $pending = count($allTasks) - $completed;

        return [
            'total_tasks' => count($allTasks),
            'completed_tasks' => $completed,
            'pending_tasks' => $pending,
            'server_uptime_seconds' => time() - (int) ($_SERVER['REQUEST_TIME_FLOAT'] ?? time()),
        ];
    }

    // ---- 对应官方 examples/server/env-variables ----

    /**
     * @return array<string, string|int>
     */
    #[McpTool(
        name: 'process_data_by_mode',
        description: 'Processes input data with behavior depending on the APP_MODE environment variable',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'mode' => [
                    'type' => 'string',
                    'description' => 'The processing mode used',
                ],
                'processed_input' => [
                    'type' => 'string',
                    'description' => 'The processed input data',
                ],
                'original_input' => [
                    'type' => 'string',
                    'description' => 'The original input data (only in default mode)',
                ],
                'message' => [
                    'type' => 'string',
                    'description' => 'A descriptive message about the processing',
                ],
            ],
            'required' => ['mode', 'message'],
        ],
    )]
    public function processData(string $input): array
    {
        $appMode = getenv('APP_MODE');
        if ('debug' === $appMode) {
            return [
                'mode' => 'debug',
                'processed_input' => strtoupper($input),
                'message' => 'Processed in DEBUG mode.',
            ];
        }
        if ('production' === $appMode) {
            return [
                'mode' => 'production',
                'processed_input_length' => strlen($input),
                'message' => 'Processed in PRODUCTION mode (summary only).',
            ];
        }

        return [
            'mode' => $appMode ?: 'default',
            'original_input' => $input,
            'message' => 'Processed in default mode (APP_MODE not recognized or not set).',
        ];
    }

    // ---- 对应官方 examples/server/explicit-registration ----

    #[McpTool(name: 'echo_text', description: 'Echoes the input text back')]
    public function echoText(string $text): string
    {
        return 'Echo: ' . $text;
    }

    #[McpResource(
        uri: 'app://version',
        name: 'application_version',
        description: 'The application version',
        mimeType: 'text/plain',
    )]
    public function getAppVersion(): string
    {
        return '1.0-manual';
    }

    /**
     * @return array<string, string>
     */
    #[McpResourceTemplate(
        uriTemplate: 'item://{itemId}/details',
        name: 'get_item_details',
        description: 'Item details resource template',
        mimeType: 'application/json',
    )]
    public function getItemDetails(string $itemId): array
    {
        return ['id' => $itemId, 'name' => "Item {$itemId}", 'description' => "Details for item {$itemId} from manual template."];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    #[McpPrompt(
        name: 'personalized_greeting',
        description: 'A prompt that returns a personalized greeting',
    )]
    public function greetingPrompt(string $userName): array
    {
        return [
            ['role' => 'user', 'content' => "Craft a personalized greeting for {$userName}."],
        ];
    }
}
