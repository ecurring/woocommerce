<?php

namespace eCurring\WooEcurring\Subscription\Metabox;

use DateTime;
use eCurring\WooEcurring\Subscription\Actions;

class Save
{
    /**
     * @var Actions
     */
    private $actions;

    public function __construct(Actions $actions)
    {
        $this->actions = $actions;
    }

    public function save($postId)
    {
        $subscriptionType = $subscriptionType = filter_input(
            INPUT_POST,
            'ecurring_subscription',
            FILTER_SANITIZE_STRING
        );

        $subscriptionId = filter_input(
            INPUT_POST,
            'ecurring_subscription_id',
            FILTER_SANITIZE_STRING
        );

        $pauseSubscription = filter_input(
            INPUT_POST,
            'ecurring_pause_subscription',
            FILTER_SANITIZE_STRING
        );
        $resumeDate = '';
        if ($pauseSubscription === 'specific-date') {
            $resumeDate = filter_input(
                INPUT_POST,
                'ecurring_resume_date',
                FILTER_SANITIZE_STRING
            );

            $resumeDate = (new DateTime($resumeDate))->format('Y-m-d\TH:i:sP');
        }

        if (!$subscriptionType || !in_array(
                $subscriptionType,
                ['pause', 'switch', 'cancel'],
                true
            ) || !$subscriptionId) {
            return;
        }

        switch ($subscriptionType) {
            case 'pause':
                $response = json_decode(
                    $this->actions->pause(
                        $subscriptionId,
                        $resumeDate
                    )
                );
                $this->updateStatus($postId, $response);
                break;
            case 'switch':
                break;
            case 'cancel':
                $response = json_decode($this->actions->cancel($subscriptionId));
                $this->updateStatus($postId, $response);
                break;
        }
    }

    /**
     * @param $postId
     * @param $response
     */
    protected function updateStatus($postId, $response)
    {
        $attributes = get_post_meta(
            $postId,
            '_ecurring_post_subscription_attributes',
            true
        );
        $attributes->status = $response->data->attributes->status;
        update_post_meta($postId, '_ecurring_post_subscription_attributes', $attributes);
    }
}
