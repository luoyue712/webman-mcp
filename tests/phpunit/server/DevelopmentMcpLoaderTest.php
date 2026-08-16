<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\server;

use Luoyue\WebmanMcp\Server\DevelopmentMcpLoader;
use Luoyue\WebmanMcp\Tests\Fixtures\DevMcp\Hello;
use Mcp\Capability\Registry;
use Mcp\Schema\Tool;
use PHPUnit\Framework\TestCase;

class DevelopmentMcpLoaderTest extends TestCase
{
    /**
     * @param string[] $path
     */
    private function createLoader(array $path = []): DevelopmentMcpLoader
    {
        return new DevelopmentMcpLoader($path, dirname(__DIR__, 2));
    }

    public function testLoadRegistersDevMcpComponents(): void
    {
        $loader = $this->createLoader(['Fixtures/DevMcp']);
        $registry = new Registry();
        $loader->load($registry);

        self::assertTrue($registry->hasTool('devmc_fixture_hello'));
        self::assertTrue($registry->hasPrompt('fixture_hello_prompt'));
        self::assertTrue($registry->hasResource('fixture://hello/static'));
        self::assertTrue($registry->hasResourceTemplate('fixture://hello/{user}'));
    }

    public function testRegisteredHandlersPointToFixtureClass(): void
    {
        $loader = $this->createLoader(['Fixtures/DevMcp']);
        $registry = new Registry();
        $loader->load($registry);

        $tool = $registry->getTool('devmc_fixture_hello');
        self::assertSame('devmc_fixture_hello', $tool->tool->name);
        self::assertSame([Hello::class, 'hello'], $tool->handler);

        $prompt = $registry->getPrompt('fixture_hello_prompt');
        self::assertSame('fixture_hello_prompt', $prompt->prompt->name);
        self::assertSame([Hello::class, 'helloPrompt'], $prompt->handler);

        $resource = $registry->getResource('fixture://hello/static');
        self::assertSame('fixture://hello/static', $resource->resource->uri);
        self::assertSame([Hello::class, 'helloResource'], $resource->handler);

        $template = $registry->getResourceTemplate('fixture://hello/{user}');
        self::assertSame('fixture://hello/{user}', $template->resourceTemplate->uriTemplate);
        self::assertSame([Hello::class, 'helloTemplate'], $template->handler);
    }

    public function testLoadWithoutPathDoesNotCrash(): void
    {
        $loader = $this->createLoader();
        $registry = new Registry();
        $loader->load($registry);

        self::assertFalse($registry->hasTools());
        self::assertFalse($registry->hasPrompts());
        self::assertFalse($registry->hasResources());
        self::assertFalse($registry->hasResourceTemplates());
    }

    public function testLoadKeepsManuallyRegisteredTool(): void
    {
        $loader = $this->createLoader(['Fixtures/DevMcp']);
        $registry = new Registry();

        $manuallyRegistered = $registry->registerTool(
            new Tool('devmc_fixture_hello', 'manual', ['type' => 'object', 'properties' => [], 'required' => []], '手动注册', null),
            static fn (): string => 'manual'
        );

        $loader->load($registry);

        self::assertTrue($registry->hasTool('devmc_fixture_hello'));
        self::assertSame($manuallyRegistered, $registry->getTool('devmc_fixture_hello'));
        self::assertSame('manual', $registry->getTool('devmc_fixture_hello')->tool->title);
    }
}
