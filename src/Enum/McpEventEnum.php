<?php

namespace Luoyue\WebmanMcp\Enum;

use Mcp\Event\PromptListChangedEvent;
use Mcp\Event\ResourceListChangedEvent;
use Mcp\Event\ResourceTemplateListChangedEvent;
use Mcp\Event\ToolListChangedEvent;

enum McpEventEnum: string
{
    /** prompt列表更改事件 */
    case PromptListChangedEvent = PromptListChangedEvent::class;

    /** resource列表更改事件 */
    case ResourceListChangedEvent = ResourceListChangedEvent::class;

    /** resourceTemplate列表更改事件 */
    case ResourceTemplateListChangedEvent = ResourceTemplateListChangedEvent::class;

    /** tool列表更改事件 */
    case ToolListChangedEvent = ToolListChangedEvent::class;
}
