<?php

declare(strict_types=1);

namespace Ekok\EventDispatcher\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Subscribe
{
    /** @var array|null */
    public $listens = null;

    public function __construct(string ...$eventNames) {
        $this->listens = $eventNames ?: null;
    }
}
