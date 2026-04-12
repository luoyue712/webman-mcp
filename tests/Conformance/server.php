<?php

use Luoyue\WebmanMcp\McpServerManager;
use Luoyue\WebmanMcp\Runner\McpProcessRunner;
use support\Request;
use Workerman\Protocols\Http;
use Workerman\Worker;

require_once dirname(__DIR__) . '/Bootstrap.php';

$mcpServerManager = new McpServerManager();
McpServerManager::loadConfig();

$name = 'conformance';
$process = McpProcessRunner::create()[$name];
$handler = new $process['handler'];

$worker = new Worker($process['listen']);
$worker->name = $name;
$worker->count = $process['count'] ?? 1;
$worker->eventLoop = $process['eventloop'] ?? '';
$worker->reusePort = $process['reusePort'] ?? false;
$worker->onWorkerStart = fn () => Http::requestClass(Request::class);
$worker->onMessage = [$handler, 'onMessage'];

if (DIRECTORY_SEPARATOR === '\\') {
    Worker::$logFile = 'php://stdout';
}
Worker::runAll();
