<?php

use Mcp\Event\ErrorEvent;
use Mcp\Event\NotificationEvent;
use Mcp\Event\PromptListChangedEvent;
use Mcp\Event\RequestEvent;
use Mcp\Event\ResourceListChangedEvent;
use Mcp\Event\ResourceTemplateListChangedEvent;
use Mcp\Event\ResponseEvent;
use Mcp\Event\ToolListChangedEvent;

// 使用webman/event插件处理MCP事件
return [
    // 以下为可修改请求/响应数据的事件。
    RequestEvent::class => [
        'function' => function (RequestEvent $event) {
            // 请求事件处理逻辑
        },
    ],
    ResponseEvent::class => [
        'function' => function (ResponseEvent $event) {
            // 响应事件处理逻辑
        },
    ],
    NotificationEvent::class => [
        'function' => function (NotificationEvent $event) {
            // 通知事件处理逻辑
        },
    ],
    ErrorEvent::class => [
        'function' => function (ErrorEvent $event) {
            // 错误事件处理逻辑
        },
    ],
    // 以下事件为通知事件，无法修改数据。
    ToolListChangedEvent::class => [
        'function' => function () {
            // tool列表更改处理逻辑
        },
    ],
    ResourceListChangedEvent::class => [
        'function' => function () {
            // resource列表更改处理逻辑
        },
    ],
    ResourceTemplateListChangedEvent::class => [
        'function' => function () {
            // resourceTemplate列表更改处理逻辑
        },
    ],
    PromptListChangedEvent::class => [
        'function' => function () {
            // prompt列表更改处理逻辑
        },
    ],
];
