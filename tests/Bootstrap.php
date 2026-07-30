<?php

use Webman\Config;

ini_set('display_errors', 'on');
error_reporting(E_ALL);
defined('BASE_PATH') || define('BASE_PATH', __DIR__);

require_once dirname(__DIR__) . '/vendor/autoload.php';

Config::load(__DIR__ . '/config', ['container'], key: 'plugin.luoyue.webman-mcp');
Config::load(__DIR__ . '/config', ['app', 'mcp']);
