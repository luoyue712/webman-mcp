<?php

use Luoyue\WebmanMcp\Command\McpStdioCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once dirname(__DIR__) . '/Bootstrap.php';

$application = new Application();
$application->add(new McpStdioCommand());
$application->setAutoExit(false);

$service = $argv[1] ?? 'conformance';

exit($application->run(
    new ArrayInput(['command' => 'mcp:server', 'service' => $service]),
    new ConsoleOutput()
));
