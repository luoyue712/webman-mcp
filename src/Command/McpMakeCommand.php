<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use Mcp\Schema\Enum\ProtocolVersion;
use support\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('mcp:make', 'Create MCP service or template')]
final class McpMakeCommand extends Command
{
    public function configure(): void
    {
        $this->addArgument('type', InputArgument::OPTIONAL, 'Type name');
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output,
        ?string $type = null,
    ): int {
        $type ??= $input->getArgument('type');
        $style = new SymfonyStyle($input, $output);
        if (!\in_array($type, ['config', 'template'], true)) {
            $style->error('Please specify a type name');
            return Command::INVALID;
        }
        return match ($type) {
            'config' => $this->makeConfig($style),
            'template' => $this->makeTemplate($style),
        };
    }

    private function makeConfig(SymfonyStyle $style): int
    {
        $style->title('MCP Service Configuration Generator');
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        $servers = iterator_to_array($mcpServerManager->getServiceNames());

        $questions = [
            'service' => [
                'question' => 'Please enter service name',
                'regex' => '/^[a-z_\x80-\xff][a-z0-9_\x80-\xff]*$/i',
                'validator' => function ($answer) use ($servers) {
                    return !in_array($answer, $servers);
                },
            ],
            'version' => [
                'question' => 'Please enter version',
                'default' => '1.0.0',
                'regex' => '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9A-Za-z-][0-9A-Za-z-]*)(?:\.(?:0|[1-9A-Za-z-][0-9A-Za-z-]*))*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/',
            ],
            'protocol_version' => [
                'question' => 'Please choice protocol version',
                'choice' => ProtocolVersion::class,
                'default' => ProtocolVersion::V2025_06_18->value,
            ],
            'description' => [
                'question' => 'Please enter description',
                'default' => 'MCP Service description',
                'regex' => '/^.*$/',
            ],
            'instructions' => [
                'question' => 'Please enter instructions',
                'default' => 'MCP Service instructions',
                'regex' => '/^.*$/',
            ],
            'pagination_limit' => [
                'question' => 'Please enter pagination limit',
                'default' => 50,
                'regex' => '/^[1-9][0-9]*$/',
            ],
            'session_store' => [
                'question' => 'Please choice session store type',
                'choice' => ['webman', 'file'],
                'default' => 'webman',
            ],
            'session_ttl' => [
                'question' => 'Please enter session TTL (seconds)',
                'default' => 86400,
                'regex' => '/^[1-9][0-9]*$/',
            ],
            'endpoint' => [
                'question' => 'Please enter API endpoint',
                'default' => '/mcp',
                'regex' => '/^\/[a-z0-9\/_-]*$/i',
            ],
            'process_port' => [
                'question' => 'Please enter process port',
                'default' => 8080,
                'regex' => '/^[1-9][0-9]*$/',
            ],
        ];

        $questions = QuestionHelper::handleQuestions($questions, $style);

        $questions['protocol_version'] = '\\' . $questions['protocol_version'];

        $sessionStore = match ($questions['session_store']) {
            'webman' => sprintf('new \Luoyue\WebmanMcp\Server\WebmanSessionStore(\'%s\', \'mcp-\', %s)', '', $questions['session_ttl']),
            'file' => sprintf('new \Luoyue\WebmanMcp\Server\FileSessionStore(runtime_path(\'/mcp/session\'), %s)', $questions['session_ttl']),
            default => throw new \InvalidArgumentException('Invalid session store type'),
        };

        $template = <<<EOF
            '{$questions['service']}' => [
                'configure' => function (\Mcp\Server\Builder \$server) {
                    \$server->setServerInfo('{$questions['service']}', '{$questions['version']}', '{$questions['description']}');
                    \$server->setProtocolVersion({$questions['protocol_version']});
                    \$server->setInstructions('{$questions['instructions']}');
                    \$server->setPaginationLimit({$questions['pagination_limit']});
                    \$server->setCapabilities(new \Mcp\Schema\ServerCapabilities(
                        tools: true,
                        resources: true,
                        prompts: true,
                        logging: false,
                        completions: true,
                        experimental: null,
                    ));
                    \$server->setSession({$sessionStore});
                },
                'transport' => [
                    'stdio' => [
                        'enable' => true,
                    ],
                    'streamable_http' => [
                        'endpoint' => '{$questions['endpoint']}',
                        'router' => [
                            'enable' => true,
                        ],
                        'process' => [
                            'enable' => false,
                            'port' => {$questions['process_port']},
                            'count' => 1,
                            'eventloop' => '',
                        ],
                    ],
                ],
            ]
        EOF;

        $file = config_path('plugin/luoyue/webman-mcp/mcp.php');
        $content = file_get_contents($file);
        $returnPos = strrpos($content, '];');
        if ($returnPos === false) {
            $style->error('Invalid configuration file format: missing closing bracket');
            return Command::FAILURE;
        }
        $before = rtrim(substr($content, 0, $returnPos));
        if (!str_ends_with($before, '[')) {
            $before .= str_ends_with($before, ',') ? '' : ',';
        }
        $after = substr($content, $returnPos);
        $newContent = $before . "\n" . $template . "\n" . $after;
        if (file_put_contents($file, $newContent) === false) {
            $style->error("Failed to write configuration to file: {$file}");
            return Command::FAILURE;
        }

        $style->success("Service '{$questions['service']}' successfully added to configuration file.");
        return Command::SUCCESS;
    }

    private function makeTemplate(SymfonyStyle $style): int
    {
        $style->title('MCP Service Template Generator');
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        $servers = iterator_to_array($mcpServerManager->getServiceNames());

        $questions = [
            'service' => [
                'question' => 'Please choice service name',
                'choice' => $servers,
            ],
            'file_name' => [
                'question' => 'Please enter file name',
                'default' => 'example',
                'regex' => '/^[a-z_][a-z0-9_.-]*(\/[a-z0-9_.-]+)*$/i',
            ],
            'generator_type' => [
                'question' => 'Please enter generator type',
                'multi_select' => true,
                'choice' => ['mcp-tool', 'mcp-resource', 'mcp-resource-template', 'mcp-prompt'],
            ],
        ];
        $questions = QuestionHelper::handleQuestions($questions, $style);

        $config = $mcpServerManager->getServiceConfig($questions['service']);
        $questions += QuestionHelper::handleQuestions([
            'save_dir' => [
                'question' => 'Please enter save dir',
                'choice' => $config['transport']['scan_dirs'] ?? ['app/mcp'],
                'default' => $config['transport']['scan_dirs'][0] ?? 'app/mcp',
            ],
        ], $style);

        $templates = [
            'mcp-tool' => <<<'MCP_TOOL'
                    #[McpTool(name: 'example_tool')]
                    public function exampleTool(): array
                    {
                        return [
                            'status' => 'ok',
                            'result' => 'hello world',
                        ];
                    }
                MCP_TOOL,
            'mcp-resource' => <<<'MCP_RESOURCE'
                    #[McpResource(uri: 'config://app')]
                    public function exampleResource(): array
                    {
                        return [
                            'app_name' => 'demo',
                            'php_version' => '8.1',
                        ];
                    }
                MCP_RESOURCE,
            'mcp-resource-template' => <<<'MCP_RESOURCE_TEMPLATE'
                    #[McpResourceTemplate(uriTemplate: 'user://{userId}/profile')]
                    public function exampleResourceTemplate(
                        #[CompletionProvider(values: ['101', '102', '103'])]
                        string $userId,
                    ): array
                    {
                        return match ($userId) {
                            '101' => ['name' => 'Alice', 'email' => 'alice@example.com'],
                            '102' => ['name' => 'Bob', 'email' => 'bob@example.com'],
                            '103' => ['name' => 'Charlie', 'email' => 'charlie@example.com'],
                            default => throw new ResourceReadException("User not found: {$userId}"),
                        };
                    }
                MCP_RESOURCE_TEMPLATE,
            'mcp-prompt' => <<<'MCP_PROMPT'
                    #[McpPrompt(name: 'example_prompt')]
                    public function examplePrompt(
                        string $userId,
                        string $tone = 'professional',
                    ): array {
                        return [
                            ['role' => 'user', 'content' => "Write a short, {$tone} biography for user {$userId}."],
                        ];
                    }
                MCP_PROMPT,
        ];

        $useClass = [
            'mcp-tool' => 'use Mcp\Capability\Attribute\McpTool;',
            'mcp-resource' => 'use Mcp\Capability\Attribute\McpResource;',
            'mcp-resource-template' => 'use Mcp\Capability\Attribute\McpResourceTemplate;',
            'mcp-prompt' => 'use Mcp\Capability\Attribute\McpPrompt;',
        ];

        $example = implode(PHP_EOL, array_intersect_key($templates, array_flip($questions['generator_type'])));
        $imports = implode(PHP_EOL, array_intersect_key($useClass, array_flip($questions['generator_type'])));
        $namespace = str_replace('/', '\\', $questions['save_dir']);

        $code = <<<CODE
        <?php

        namespace {$namespace};

        {$imports}

        class {$questions['file_name']}
        {
        {$example}
        }

        CODE;

        $path = base_path($questions['save_dir']) . DIRECTORY_SEPARATOR . $questions['file_name'] . '.php';
        file_put_contents($path, $code);
        return Command::SUCCESS;
    }
}
