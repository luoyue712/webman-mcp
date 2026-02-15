<?php

use Composer\InstalledVersions;
use Luoyue\WebmanMcp\McpServerManager;
use Luoyue\WebmanMcp\Runner\McpProcessRunner;
use support\Request;
use Webman\Config;
use Workerman\Events\Fiber;
use Workerman\Events\Swoole;
use Workerman\Events\Swow;
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

$process = McpProcessRunner::create()['conformance'];
$handler = new $process['handler'];

$worker = new Worker($process['listen']);
$worker->name = 'conformance';
$worker->count = cpu_count() * 4;
$worker->eventLoop = event_loop();
$worker->onWorkerStart = fn () => Http::requestClass(Request::class);
$worker->onMessage = [$handler, 'onMessage'];

if (DIRECTORY_SEPARATOR === '\\') {
    Worker::$logFile = 'php://stdout';
}
Worker::runAll();

function event_loop(): string
{
    if (extension_loaded('swow')) {
        return Swow::class;
    }
    if (extension_loaded('swoole')) {
        return Swoole::class;
    }
    if (InstalledVersions::isInstalled('revolt/event-loop')) {
        return Fiber::class;
    }

    return '';
}
