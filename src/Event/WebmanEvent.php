<?php

namespace Luoyue\WebmanMcp\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Webman\Event\Event;

final class WebmanEvent implements EventDispatcherInterface
{
    public static function instance(): self
    {
        return new self();
    }

    public static function installed(): bool
    {
        return class_exists(Event::class);
    }

    public function dispatch(object $event): void
    {
        $eventMode = config('plugin.luoyue.webman-mcp.app.event_mode', 'dispatch');
        if ($eventMode === 'dispatch') {
            Event::dispatch(get_class($event), $event);
        } else {
            Event::emit(get_class($event), $event);
        }
    }
}