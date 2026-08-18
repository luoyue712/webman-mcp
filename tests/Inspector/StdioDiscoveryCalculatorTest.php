<?php

namespace Luoyue\WebmanMcp\Tests\Inspector;

final class StdioDiscoveryCalculatorTest extends StdioInspectorSnapshotTestCase
{
    public static function provideMethods(): array
    {
        return [
            ...parent::provideMethods(),
            'Calculate Sum' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'calculate',
                    'toolArgs' => ['a' => 12.5, 'b' => 7.3, 'operation' => 'add'],
                ],
                'testName' => 'calculate_sum',
            ],
            'Update Setting' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'update_setting',
                    'toolArgs' => ['setting' => 'precision', 'value' => 3],
                ],
                'testName' => 'update_setting',
            ],
            'Read Config' => [
                'method' => 'resources/read',
                'options' => [
                    'uri' => 'config://calculator/settings',
                ],
                'testName' => 'read_config',
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
