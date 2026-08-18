<?php

namespace Luoyue\WebmanMcp\Tests\Inspector;

final class StdioExplicitRegistrationTest extends StdioInspectorSnapshotTestCase
{
    public static function provideMethods(): array
    {
        return [
            ...parent::provideMethods(),
            'Echo Tool Call' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'echo_text',
                    'toolArgs' => ['text' => 'Hello World'],
                ],
                'testName' => 'echo_text',
            ],
            'Echo Tool with Special Characters' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'echo_text',
                    'toolArgs' => ['text' => 'Test with emoji 🎉 and symbols @#$%'],
                ],
                'testName' => 'echo_text_special_chars',
            ],
            'Read App Version Resource' => [
                'method' => 'resources/read',
                'options' => [
                    'uri' => 'app://version',
                ],
                'testName' => 'read_app_version',
            ],
            'Read Item Details (123)' => [
                'method' => 'resources/read',
                'options' => [
                    'uri' => 'item://123/details',
                ],
                'testName' => 'read_item_123_details',
            ],
            'Read Item Details (ABC)' => [
                'method' => 'resources/read',
                'options' => [
                    'uri' => 'item://ABC/details',
                ],
                'testName' => 'read_item_ABC_details',
            ],
            'Personalized Greeting Prompt (Alice)' => [
                'method' => 'prompts/get',
                'options' => [
                    'promptName' => 'personalized_greeting',
                    'promptArgs' => ['userName' => 'Alice'],
                ],
                'testName' => 'personalized_greeting_alice',
            ],
            'Personalized Greeting Prompt (Bob)' => [
                'method' => 'prompts/get',
                'options' => [
                    'promptName' => 'personalized_greeting',
                    'promptArgs' => ['userName' => 'Bob'],
                ],
                'testName' => 'personalized_greeting_bob',
            ],
        ];
    }

    protected function getServerScript(): string
    {
        return __DIR__ . '/stdio_server.php';
    }

    protected function getServiceName(): string
    {
        return 'inspector';
    }
}
