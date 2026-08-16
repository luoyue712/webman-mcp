<?php

namespace Luoyue\WebmanMcp\Tests\Inspector;

final class StdioCachedDiscoveryTest extends StdioInspectorSnapshotTestCase
{
    public static function provideMethods(): array
    {
        return [
            ...parent::provideMethods(),
            'Add Numbers Tool' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'add_numbers',
                    'toolArgs' => ['a' => 5, 'b' => 3],
                ],
                'testName' => 'add_numbers',
            ],
            'Add Numbers (Negative)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'add_numbers',
                    'toolArgs' => ['a' => -10, 'b' => 7],
                ],
                'testName' => 'add_numbers_negative',
            ],
            'Multiply Numbers Tool' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'multiply_numbers',
                    'toolArgs' => ['a' => 4, 'b' => 6],
                ],
                'testName' => 'multiply_numbers',
            ],
            'Multiply Numbers (Zero)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'multiply_numbers',
                    'toolArgs' => ['a' => 15, 'b' => 0],
                ],
                'testName' => 'multiply_numbers_zero',
            ],
            'Divide Numbers Tool' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'divide_numbers',
                    'toolArgs' => ['a' => 20, 'b' => 4],
                ],
                'testName' => 'divide_numbers',
            ],
            'Divide Numbers (Decimal Result)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'divide_numbers',
                    'toolArgs' => ['a' => 7, 'b' => 2],
                ],
                'testName' => 'divide_numbers_decimal',
            ],
            'Power Tool' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'power',
                    'toolArgs' => ['base' => 2, 'exponent' => 8],
                ],
                'testName' => 'power',
            ],
            'Power Tool (Zero Exponent)' => [
                'method' => 'tools/call',
                'options' => [
                    'toolName' => 'power',
                    'toolArgs' => ['base' => 5, 'exponent' => 0],
                ],
                'testName' => 'power_zero_exponent',
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
