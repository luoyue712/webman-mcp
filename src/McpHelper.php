<?php

namespace Luoyue\WebmanMcp;

use Exception;
use Mcp\Exception\ToolCallException;
use Symfony\Component\Console\Command\Command as Commands;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Webman\Console\Command;
use Webman\Context;
use Workerman\Coroutine;
use Workerman\Events\Fiber;
use Workerman\Events\Swoole;
use Workerman\Events\Swow;
use Workerman\Worker;

final class McpHelper
{
    /**
     * 当前请求是否是mcp请求
     * @return bool true为mcp请求，false为controller请求
     */
    public static function is_mcp_server_request(): bool
    {
        return Context::get('McpServerRequest', false);
    }

    /**
     * mcp执行时自带fiber导致误判，所以需要额外判断.
     */
    public static function is_coroutine(): bool
    {
        return in_array(Worker::$eventLoopClass, [Swoole::class, Swow::class, Fiber::class]) && Coroutine::isCoroutine();
    }

    /**
     * 运行console命令.
     * @param class-string<Commands> $command 命令类名.
     * @param array<string, string> $args 执行参数.
     * @param class-string<Exception> $throw_exception 抛出的异常类.
     * @return string 输出结果
     * @throws Exception
     */
    public static function fetch_console(string $command, array $args = [], string $throw_exception = ToolCallException::class): string
    {
        if (!class_exists($command)) {
            throw new $throw_exception("command {$command} not exists");
        }
        try {
            $application = new Command();
            /** @var Commands $commandInstance */
            $commandInstance = $application->createCommandInstance($command);
            $application->setAutoExit(false);

            $input = new ArrayInput(['command' => $commandInstance->getName(), ...$args]);
            $output = new BufferedOutput();
            $application->setCatchExceptions(false);
            $application->run($input, $output);
            return $output->fetch();
        } catch (Exception $e) {
            throw new $throw_exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}
