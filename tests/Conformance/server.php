<?php

use Luoyue\WebmanMcp\McpServerManager;
use Luoyue\WebmanMcp\Runner\McpProcessRunner;
use support\Request;
use Webman\Config;
use Workerman\Protocols\Http;
use Workerman\Worker;

ini_set('display_errors', 'on');
error_reporting(E_ALL);
const BASE_PATH = __DIR__;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

Config::load(__DIR__ . '/config', ['container'], key: 'plugin.luoyue.webman-mcp');
Config::load(__DIR__ . '/config', ['app', 'mcp']);

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
