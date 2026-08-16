<?php

$host = getenv('MCP_TEST_DB_HOST') ?: '127.0.0.1';
$port = getenv('MCP_TEST_DB_PORT') ?: '3306';
$database = getenv('MCP_TEST_DB_DATABASE') ?: 'webman_mcp_test';
$username = getenv('MCP_TEST_DB_USERNAME') ?: 'root';
$password = getenv('MCP_TEST_DB_PASSWORD') ?: 'root';

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
                'heartbeat_interval' => 50,
            ],
        ],
    ],
];
