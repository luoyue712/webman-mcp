<?php

$host = getenv('MCP_TEST_REDIS_HOST') ?: '127.0.0.1';
$port = (int) (getenv('MCP_TEST_REDIS_PORT') ?: '6379');
$password = getenv('MCP_TEST_REDIS_PASSWORD') ?: '';
$database = (int) (getenv('MCP_TEST_REDIS_DATABASE') ?: '0');

return [
    'default' => [
        'host' => $host,
        'port' => $port,
        'password' => $password,
        'database' => $database,
        'pool' => [
            'max_connections' => 5,
            'min_connections' => 1,
            'wait_timeout' => 3,
            'idle_timeout' => 60,
            'heartbeat_interval' => 50,
        ],
    ],
];
