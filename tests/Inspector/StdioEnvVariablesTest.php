<?php

namespace Luoyue\WebmanMcp\Tests\Inspector;

final class StdioEnvVariablesTest extends StdioInspectorSnapshotTestCase
{
    public static function provideMethods(): array
    {
        return [
            ...parent::provideMethods(),
            'Process Data (Default Mode)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'process_data_by_mode',
                    'toolArgs' => ['input' => 'test data'],
                ],
                'testName' => 'process_data_default',
            ],
            'Process Data (Debug Mode)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'process_data_by_mode',
                    'toolArgs' => ['input' => 'debug test'],
                    'envVars' => ['APP_MODE' => 'debug'],
                ],
                'testName' => 'process_data_debug',
            ],
            'Process Data (Production Mode)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'process_data_by_mode',
                    'toolArgs' => ['input' => 'production data'],
                    'envVars' => ['APP_MODE' => 'production'],
                ],
                'testName' => 'process_data_production',
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
