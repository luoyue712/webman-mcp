<?php

namespace Luoyue\WebmanMcp\Tests\Fixtures\DevMcp;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;

class Hello
{
    #[McpTool(
        name: 'devmc_fixture_hello',
        title: 'Fixture Hello Tool',
        description: 'A fixture tool for testing the development loader.'
    )]
    public function hello(string $name): string
    {
        return 'Hello ' . $name;
    }

    #[McpResource(
        uri: 'fixture://hello/static',
        name: 'fixture_hello_resource',
        title: 'Fixture Hello Resource',
        description: 'A fixture resource for testing the development loader.'
    )]
    public function helloResource(): string
    {
        return 'resource content';
    }

    /**
     * @return array<never>
     */
    #[McpPrompt(
        name: 'fixture_hello_prompt',
        title: 'Fixture Hello Prompt',
        description: 'A fixture prompt for testing the development loader.'
    )]
    public function helloPrompt(string $question): array
    {
        return [];
    }

    #[McpResourceTemplate(
        uriTemplate: 'fixture://hello/{user}',
        name: 'fixture_hello_template',
        title: 'Fixture Hello Template',
        description: 'A fixture resource template for testing the development loader.'
    )]
    public function helloTemplate(): string
    {
        return 'template content';
    }
}
