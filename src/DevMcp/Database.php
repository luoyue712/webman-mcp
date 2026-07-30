<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Composer\InstalledVersions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use support\Db;
use Throwable;

class Database
{
    #[McpTool(
        name: 'database_connections',
        title: '获取数据库连接配置信息',
        description: '获取所有数据库连接的配置信息，包括驱动、数据库名、表前缀、连接池等',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'default' => ['type' => 'string'],
                'connections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'connection_name' => ['type' => 'string'],
                            'driver' => ['type' => 'string'],
                            'database' => ['type' => 'string'],
                            'prefix' => ['type' => 'string'],
                            'schema' => ['type' => ['string', 'null']],
                            'pool' => [
                                'type' => ['object', 'null'],
                                'properties' => [
                                    'max_connections' => ['type' => 'integer'],
                                    'min_connections' => ['type' => 'integer'],
                                    'wait_timeout' => ['type' => 'integer'],
                                    'idle_timeout' => ['type' => 'integer'],
                                    'heartbeat_interval' => ['type' => 'integer'],
                                ],
                            ],
                        ],
                        'required' => ['connection_name', 'driver', 'database', 'prefix', 'schema', 'pool'],
                    ],
                ],
            ],
            'required' => ['default', 'connections'],
        ]
    )]
    public function databaseConnections(): array
    {
        $this->checkInstallDatabase();
        $connections = config('database.connections', []);
        return [
            'default' => config('database.default'),
            'connections' => array_map(function ($key, $connection) {
                return [
                    'connection_name' => $key,
                    'driver' => $connection['driver'] ?? null,
                    'database' => $connection['database'] ?? null,
                    'prefix' => $connection['prefix'] ?? null,
                    'schema' => $connection['schema'] ?? null,
                    'pool' => $connection['pool'] ?? null,
                ];
            }, array_keys($connections), array_values($connections)),
        ];
    }

    #[McpTool(
        name: 'database_execute_sql',
        title: '执行原始SQL语句',
        description: '在指定数据库连接上执行原始SQL语句，支持参数绑定防止SQL注入',
        outputSchema: [
            'type' => 'object',
            'properties' => [
                'result' => ['type' => 'array', 'items' => ['type' => 'object']],
            ],
            'required' => ['result'],
        ]
    )]
    public function databaseExecuteSql(
        #[Schema(description: 'sql脚本')]
        string $sql,
        #[Schema(description: 'sql参数绑定')]
        array $bindings,
        #[Schema(description: 'database连接名称')]
        ?string $connection = null,
    ): array
    {
        $this->checkInstallDatabase();
        try {
            return [
                'result' => array_map(fn ($item) => (array) $item, Db::connection($connection)->select($sql, $bindings)),
            ];
        } catch (Throwable $e) {
            throw new ToolCallException('执行sql失败: ' . $e->getMessage());
        }
    }

    protected function checkInstallDatabase(): void
    {
        !InstalledVersions::isInstalled('webman/database') && throw new ToolCallException('未安装数据库组件');
    }
}
