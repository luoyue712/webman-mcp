<?php

namespace Luoyue\WebmanMcp\Tests\Inspector;

abstract class StdioInspectorSnapshotTestCase extends InspectorSnapshotTestCase
{
    abstract protected function getServerScript(): string;

    protected function getServiceName(): string
    {
        return 'conformance';
    }

    protected function getServerConnectionArgs(): array
    {
        return ['php', $this->getServerScript(), $this->getServiceName()];
    }

    protected function getTransport(): string
    {
        return 'stdio';
    }

    protected function getSnapshotFilePath(string $method, ?string $testName = null): string
    {
        $className = substr(static::class, strrpos(static::class, '\\') + 1);
        $suffix = $testName ? '-' . preg_replace('/[^a-zA-Z0-9_]/', '_', $testName) : '';

        return __DIR__ . '/snapshots/' . $className . '-' . str_replace('/', '_', $method) . $suffix . '.json';
    }
}
