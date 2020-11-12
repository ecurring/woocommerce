<?php

namespace Ecurring\WooEcurring\EventListener;

interface EventListenerInterface
{

    /**
     * Initialize listener
     */
    public function init(): void;
}
