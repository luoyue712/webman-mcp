<?php

use Webman\Config;

ini_set('display_errors', 'on');
error_reporting(E_ALL);
defined('BASE_PATH') || define('BASE_PATH', __DIR__);

require_once dirname(__DIR__) . '/vendor/autoload.php';

Config::load(__DIR__ . '/config', ['container'], key: 'plugin.luoyue.webman-mcp');
Config::load(__DIR__ . '/config', ['app', 'mcp', 'database', 'redis']);

/**
 * 清理 Webman\Finder 在测试期间产生的运行时缓存目录（仓库根 runtime/）。
 */
register_shutdown_function(static function (): void {
    $runtime = runtime_path();
    if (!is_dir($runtime)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($runtime, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($runtime);
});
