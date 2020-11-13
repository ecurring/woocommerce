<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EventListener;

interface EventListenerInterface
{

    /**
     * Initialize listener
     */
    public function init(): void;
}
