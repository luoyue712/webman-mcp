<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\console;

use Luoyue\WebmanMcp\Command\McpToolsCommand;
use Luoyue\WebmanMcp\McpHelper;
use PHPUnit\Framework\TestCase;

class ToolsCommandTest extends TestCase
{
    public function testToolsCommandListsElements(): void
    {
        $data = McpHelper::fetch_console(McpToolsCommand::class, ['service' => 'conformance']);

        self::assertStringContainsString('MCP service: conformance', $data);
        self::assertStringContainsString('tools: 12, resources: 3, resource_templates: 1, prompts: 4', $data);
        self::assertStringContainsString('test_simple_text', $data);
        self::assertStringContainsString('test://static-text', $data);
        self::assertStringContainsString('test://template/{id}/data', $data);
        self::assertStringContainsString('test_prompt_with_arguments', $data);
        self::assertStringContainsString('arg1, arg2', $data);
    }

    public function testToolsCommandUnknownServiceFails(): void
    {
        $data = McpHelper::fetch_console(McpToolsCommand::class, ['service' => 'not_exist']);

        self::assertStringContainsString('Mcp server [not_exist] not found.', $data);
    }
}
