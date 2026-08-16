<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\console;

use Luoyue\WebmanMcp\Command\McpToolsCallCommand;
use Luoyue\WebmanMcp\McpHelper;
use PHPUnit\Framework\TestCase;

class ToolsCallCommandTest extends TestCase
{
    public function testExecuteTool(): void
    {
        $data = McpHelper::fetch_console(McpToolsCallCommand::class, [
            'service' => 'conformance',
            'tool-name' => 'test_simple_text',
        ]);

        self::assertStringContainsString('Executing Tool: test_simple_text', $data);
        self::assertStringContainsString('This is a simple text response for testing.', $data);
    }

    public function testExecuteToolJsonFormat(): void
    {
        $data = McpHelper::fetch_console(McpToolsCallCommand::class, [
            'service' => 'conformance',
            'tool-name' => 'test_simple_text',
            '--format' => 'json',
        ]);

        self::assertStringContainsString('"This is a simple text response for testing."', $data);
    }

    public function testUnknownToolFails(): void
    {
        $data = McpHelper::fetch_console(McpToolsCallCommand::class, [
            'service' => 'conformance',
            'tool-name' => 'not_exist',
        ]);

        self::assertStringContainsString('Tool "not_exist" not found', $data);
    }

    public function testInvalidJsonFails(): void
    {
        $data = McpHelper::fetch_console(McpToolsCallCommand::class, [
            'service' => 'conformance',
            'tool-name' => 'test_simple_text',
            'json-input' => '{oops',
        ]);

        self::assertStringContainsString('Invalid JSON', $data);
    }

    public function testValidationErrorFails(): void
    {
        $data = McpHelper::fetch_console(McpToolsCallCommand::class, [
            'service' => 'conformance',
            'tool-name' => 'test_sampling',
        ]);

        self::assertStringContainsString('Invalid parameters for tool "test_sampling"', $data);
        self::assertStringContainsString('prompt', $data);
    }

    public function testUnknownServiceFails(): void
    {
        $data = McpHelper::fetch_console(McpToolsCallCommand::class, [
            'service' => 'not_exist',
            'tool-name' => 'test_simple_text',
        ]);

        self::assertStringContainsString('Mcp server [not_exist] not found.', $data);
    }

    public function testJsonErrorOutputIsJson(): void
    {
        $data = McpHelper::fetch_console(McpToolsCallCommand::class, [
            'service' => 'conformance',
            'tool-name' => 'not_exist',
            '--format' => 'json',
        ]);

        self::assertStringContainsString('"error"', $data);
        self::assertStringContainsString('Tool \\"not_exist\\" not found', $data);
    }

    public function testJsonArrayInputRejected(): void
    {
        $data = McpHelper::fetch_console(McpToolsCallCommand::class, [
            'service' => 'conformance',
            'tool-name' => 'test_simple_text',
            'json-input' => '[1,2,3]',
        ]);

        self::assertStringContainsString('JSON input must be an object, not a JSON array', $data);
    }
}
