<?php

namespace Luoyue\WebmanMcp\Runner;

use Luoyue\WebmanMcp\Command\McpInspectorCommand;
use Luoyue\WebmanMcp\Command\McpListCommand;
use Luoyue\WebmanMcp\Command\McpMakeCommand;
use Luoyue\WebmanMcp\Command\McpResourcesReadCommand;
use Luoyue\WebmanMcp\Command\McpStdioCommand;
use Luoyue\WebmanMcp\Command\McpToolsCallCommand;
use Luoyue\WebmanMcp\Command\McpToolsCommand;
use Symfony\Component\Console\Command\Command;

final class McpCommandRunner implements McpRunnerInterface
{
    public const COMMAND = [
        McpStdioCommand::class,
        McpListCommand::class,
        McpMakeCommand::class,
        McpInspectorCommand::class,
        McpToolsCommand::class,
        McpToolsCallCommand::class,
        McpResourcesReadCommand::class,
    ];

    /**
     * @return array<class-string<Command>>
     */
    public static function create(): array
    {
        return self::COMMAND;
    }
}
