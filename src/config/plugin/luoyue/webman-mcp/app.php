<?php

use Luoyue\WebmanMcp\Enum\McpClientRegisterEnum;

return [
    'enable' => true,
    // 自动注册MCP服务到ide中
    'auto_register_client' => McpClientRegisterEnum::CURSOR_IDE,
    // 事件分发模式: dispatch或emit,参考：https://www.workerman.net/doc/webman/components/event.html#%E5%8F%91%E5%B8%83%E4%BA%8B%E4%BB%B6
    'event_mode' => 'dispatch',
];