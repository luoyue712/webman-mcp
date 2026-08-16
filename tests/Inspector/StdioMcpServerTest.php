<?php

namespace Luoyue\WebmanMcp\Tests\Inspector;

final class StdioMcpServerTest extends StdioInspectorSnapshotTestCase
{
    public static function provideMethods(): array
    {
        return [
            ...parent::provideMethods(),
            'Read Static Text Resource' => [
                'method' => 'resources/read',
                'options' => [
                    'uri' => 'test://static-text',
                ],
                'testName' => 'read_static_text',
            ],
            'Read Watched Resource' => [
                'method' => 'resources/read',
                'options' => [
                    'uri' => 'test://watched-resource',
                ],
                'testName' => 'read_watched_resource',
            ],
            'Get Simple Prompt' => [
                'method' => 'prompts/get',
                'options' => [
                    'promptName' => 'test_simple_prompt',
                ],
                'testName' => 'get_simple_prompt',
            ],
            'Call Simple Text Tool' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'test_simple_text',
                ],
                'testName' => 'call_simple_text',
            ],
            'Call Error Handling Tool' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'test_error_handling',
                ],
                'testName' => 'call_error_handling',
            ],
        ];
    }

    protected function getServerScript(): string
    {
        return __DIR__ . '/stdio_server.php';
    }
}
