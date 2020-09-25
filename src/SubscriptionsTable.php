<?php

namespace eCurring\WooEcurring;

use eCurring_WC_Helper_Api;
use WP_List_Table;

class SubscriptionsTable extends WP_List_Table
{
    /**
     * @var eCurring_WC_Helper_Api
     */
    protected $apiHelper;

    public function __construct(eCurring_WC_Helper_Api $apiHelper, $args = array())
    {
        parent::__construct($args);

        $this->apiHelper = $apiHelper;
    }

    /**
     * @return void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $subscriptionIds = [];
        $orders = wc_get_orders([]);
        foreach ($orders as $order) {
            $subscriptionId = get_post_meta($order->get_id(), '_ecurring_subscription_id', true);
            if ($subscriptionId && !in_array($subscriptionId, $subscriptionIds)) {
                $subscriptionIds[] = $subscriptionId;
            }
        }

        $subscriptions = [];
        foreach ($subscriptionIds as $subscriptionId) {
            $subscription = $this->apiHelper->apiCall(
                'GET',
                "https://api.ecurring.com/subscriptions/{$subscriptionId}"
            );

            $subscriptions[] = json_decode($subscription);
        }

        $data = $this->tableData($subscriptions);

        $perPage = 5;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args(
            [
                'total_items' => $totalItems,
                'per_page' => $perPage,
            ]
        );

        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data;
    }

    /**
     * @return array|string[]
     */
    public function get_columns()
    {
        return [
            'id' => 'ID',
            'customer' => 'Customer',
            'product' => 'Product',
            'start_date' => 'Start Date',
            'status' => 'Status',
            'actions' => 'Actions',
        ];
    }

    /**
     * @return array
     */
    public function get_hidden_columns()
    {
        return [];
    }

    /**
     * @return array|array[]
     */
    public function get_sortable_columns()
    {
        return [
            'status' => ['status', false]
        ];
    }

    /**
     * @param object $item
     * @param string $column_name
     * @return string|true|void
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'customer':
            case 'product':
            case 'start_date':
            case 'order':
                return $item[$column_name];
            case 'status':
                return ucfirst($item[$column_name]);
            case 'actions':
                return '<a id="ecurring-subscription-pause" href="#">Pause</a> | <a id="ecurring-subscription-switch" href="#">Switch</a> | <a id="ecurring-subscription-cancel" href="#">Cancel</a>';
            default:
                return print_r($item, true);
        }
    }

    /**
     * @param array $subscriptions
     * @return array
     */
    protected function tableData($subscriptions)
    {
        $data = [];

        foreach ($subscriptions as $subscription) {

            $data[] = [
                'id' => $subscription->data->id,
                'customer' => $subscription->data->relationships->customer->data->id,
                'product' => $subscription->data->relationships->{'subscription-plan'}->data->id,
                'start_date' => $subscription->data->attributes->start_date,
                'status' => $subscription->data->attributes->status,
            ];
        }

        return $data;
    }
}
