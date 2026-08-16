<?php

namespace Luoyue\WebmanMcp\Tests\Inspector;

final class StdioMcpAppsTest extends StdioInspectorSnapshotTestCase
{
    public static function provideMethods(): array
    {
        return [
            ...parent::provideMethods(),
            'Read Weather UI Resource' => [
                'method' => 'resources/read',
                'options' => [
                    'uri' => 'ui://weather-app',
                ],
                'testName' => 'read_weather_ui',
            ],
            'Get Weather Tool Call (London)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'get_weather',
                    'toolArgs' => ['city' => 'London'],
                ],
                'testName' => 'get_weather_london',
            ],
            'Get Weather Tool Call (Tokyo)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'get_weather',
                    'toolArgs' => ['city' => 'Tokyo'],
                ],
                'testName' => 'get_weather_tokyo',
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
