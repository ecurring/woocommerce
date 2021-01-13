<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages;

use Dhii\Output\Template\PhpTemplate\FilePathTemplateFactory;
use Ecurring\WooEcurring\Subscription\Repository;
use eCurring_WC_Plugin;

class OrderEditPageController
{
    /**
     * @var Repository
     */
    protected $repository;
    /**
     * @var FilePathTemplateFactory
     */
    protected $templateFactory;
    /**
     * @var string
     */
    protected $adminTemplatesPath;

    /**
     * OrderEditPageController constructor.
     *
     * @param Repository $repository
     * @param FilePathTemplateFactory $templateFactory
     * @param string $adminTemplatesPath
     */
    public function __construct(
        Repository $repository,
        FilePathTemplateFactory $templateFactory,
        string $adminTemplatesPath
    ) {

        $this->repository = $repository;
        $this->templateFactory = $templateFactory;
        $this->adminTemplatesPath = $adminTemplatesPath;
    }

    public function registerEditOrderPageMetaBox(): void
    {
        add_meta_box(
            'woo_ecurring_orders_metabox',
            __('eCurring details', 'woo-ecurring'),
            [$this, 'renderMetaBox'],
            'shop_order',
            'side',
            'core'
        );
    }

    public function renderMetaBox($post): void
    {
        $postId = (int) $post->ID;
        $subscriptionId = $this->repository->findSubscriptionIdByOrderId($postId);
        $subscription = $this->repository->getSubscriptionById($subscriptionId);

        $metaBoxTemplate = $this->templateFactory->fromPath(
            trailingslashit($this->adminTemplatesPath) . 'order-edit-page/ecurring-meta-box.php'
        );

        if ($subscription === null) {
            _e('No eCurring subscription found for this order.', 'woo-ecurring');

            return;
        }

        $transactionId = get_post_meta($postId, '_ecurring_transaction_id', true);

        if (! $transactionId) {
            _e('No known transaction yet.', 'woo-ecurring');
            return;
        }

        $apiHelper = eCurring_WC_Plugin::getApiHelper();
        $transactionData = json_decode(
            $apiHelper->apiCall('GET', 'https://api.ecurring.com/transactions/' . $transactionId),
            true
        );

        if(! isset($transactionData['data'])){
            _e('Failed to get transaction data', 'woo-ecurring');

            return;
        }

        $transactionAmount = $transactionData['data']['attributes']['amount'];

        echo $metaBoxTemplate->render([
            'subscription_id' => $subscription->getId(),
            'customer_id' => $subscription->getCustomerId(),
            'transaction_id' => $transactionId,
            'transaction_status' => $transactionData['data']['attributes']['status'] ?? '',
            'transaction_amount' => $transactionAmount ? wc_price($transactionAmount) : '',
            'transaction_method' => $transactionData['data']['attributes']['payment_method'] ?? '',
        ]);
    }
}
