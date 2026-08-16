<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\console;

use Luoyue\WebmanMcp\Command\McpResourcesReadCommand;
use Luoyue\WebmanMcp\McpHelper;
use PHPUnit\Framework\TestCase;

class ResourcesReadCommandTest extends TestCase
{
    public function testReadStaticResource(): void
    {
        $data = McpHelper::fetch_console(McpResourcesReadCommand::class, [
            'service' => 'conformance',
            'uri' => 'test://static-text',
        ]);

        self::assertStringContainsString('Reading Resource: test://static-text', $data);
        self::assertStringContainsString('Name: static-text', $data);
        self::assertStringContainsString('This is the content of the static text resource.', $data);
    }

    public function testReadTemplateResource(): void
    {
        $data = McpHelper::fetch_console(McpResourcesReadCommand::class, [
            'service' => 'conformance',
            'uri' => 'test://template/abc123/data',
        ]);

        self::assertStringContainsString('Reading Resource: test://template/abc123/data', $data);
        self::assertStringContainsString('"id":"abc123"', $data);
        self::assertStringContainsString('Data for ID: abc123', $data);
    }

    public function testReadJsonFormat(): void
    {
        $data = McpHelper::fetch_console(McpResourcesReadCommand::class, [
            'service' => 'conformance',
            'uri' => 'test://static-text',
            '--format' => 'json',
        ]);

        self::assertStringContainsString('"uri": "test://static-text"', $data);
        self::assertStringContainsString('This is the content of the static text resource.', $data);
    }

    public function testUnknownResourceFails(): void
    {
        $data = McpHelper::fetch_console(McpResourcesReadCommand::class, [
            'service' => 'conformance',
            'uri' => 'test://does-not-exist',
        ]);

        self::assertStringContainsString('Resource "test://does-not-exist" not found', $data);
    }

    public function testUnknownServiceFails(): void
    {
        $data = McpHelper::fetch_console(McpResourcesReadCommand::class, [
            'service' => 'not_exist',
            'uri' => 'test://static-text',
        ]);

        self::assertStringContainsString('Mcp server [not_exist] not found.', $data);
    }

    public function testUnsupportedFormatFails(): void
    {
        $data = McpHelper::fetch_console(McpResourcesReadCommand::class, [
            'service' => 'conformance',
            'uri' => 'test://static-text',
            '--format' => 'toon',
        ]);

        self::assertStringContainsString('Unsupported format: toon', $data);
    }
}
