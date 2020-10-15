<?php

namespace Ecurring\WooEcurring\Subscription\Metabox;

use Ecurring\WooEcurring\Subscription\Actions;

class Metabox
{
    /**
     * @var Display
     */
    private $display;

    /**
     * @var Save
     */
    private $save;

    public function __construct(Display $display, Save $save)
    {
        $this->display = $display;
        $this->save = $save;
    }

    public function init()
    {
        $display = $this->display;
        $save = $this->save;

        add_action(
            'add_meta_boxes',
            function () use ($display) {
                add_meta_box(
                    'ecurring_subscription_details',
                    'Details',
                    function ($post) use($display) {
                        $display->details($post);
                    },
                    'esubscriptions'
                );
                add_meta_box(
                    'ecurring_subscription_options',
                    'Options',
                    function ($post) use($display) {
                        $display->options($post);
                    },
                    'esubscriptions'
                );
            }
        );

        add_action(
            'post_updated',
            function ($postId) use($save) {
                $save->save($postId);
            }
        );
    }
}
