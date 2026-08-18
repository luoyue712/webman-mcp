<?php

namespace Luoyue\WebmanMcp\Tests\phpunit\runner;

use Luoyue\WebmanMcp\Command\McpInspectorCommand;
use Luoyue\WebmanMcp\Command\McpListCommand;
use Luoyue\WebmanMcp\Command\McpMakeCommand;
use Luoyue\WebmanMcp\Command\McpResourcesReadCommand;
use Luoyue\WebmanMcp\Command\McpStdioCommand;
use Luoyue\WebmanMcp\Command\McpToolsCallCommand;
use Luoyue\WebmanMcp\Command\McpToolsCommand;
use Luoyue\WebmanMcp\Runner\McpCommandRunner;
use PHPUnit\Framework\TestCase;

class McpCommandRunnerTest extends TestCase
{
    public function testCreate(): void
    {
        $commands = McpCommandRunner::create();
        $this->assertContains(McpStdioCommand::class, $commands);
        $this->assertContains(McpListCommand::class, $commands);
        $this->assertContains(McpMakeCommand::class, $commands);
        $this->assertContains(McpInspectorCommand::class, $commands);
        $this->assertContains(McpToolsCommand::class, $commands);
        $this->assertContains(McpToolsCallCommand::class, $commands);
        $this->assertContains(McpResourcesReadCommand::class, $commands);
    }
}
