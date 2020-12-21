<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages;

use Dhii\Output\Block\TemplateBlockFactoryInterface;
use Dhii\Output\Template\PathTemplateFactoryInterface;
use Ecurring\WooEcurring\Api\SubscriptionPlans;
use Ecurring\WooEcurring\Subscription\SubscriptionCrud;
use Ecurring\WooEcurring\Template\WcSelectTemplate;
use eCurring_WC_Plugin;
use Throwable;

/**
 * Handle request to the product edit pages.
 */
class ProductEditPageController
{
    /**
     * @var PathTemplateFactoryInterface
     */
    protected $pathTemplateFactory;
    /**
     * @var TemplateBlockFactoryInterface
     */
    protected $templateBlockFactory;
    /**
     * @var SubscriptionPlans
     */
    protected $subscriptionPlans;
    /**
     * @var string
     */
    protected $adminTemplatesPath;
    /**
     * @var SubscriptionCrud
     */
    protected $subscriptionCrud;

    /**
     * @param PathTemplateFactoryInterface  $pathTemplateFactory  Service able to create
     *                                                            a template from a path.
     *
     * @param TemplateBlockFactoryInterface $templateBlockFactory Service able to create
     *                                                            TemplateBlock instance.
     *
     * @param SubscriptionPlans             $subscriptionPlans    Service providing subscription
     *                                                            plans data.
     * @param SubscriptionCrud              $subscriptionCrud
     * @param string $adminTemplatesPath                          Path to the directory with
     *                                                            the admin templates.
     */
    public function __construct(
        PathTemplateFactoryInterface $pathTemplateFactory,
        TemplateBlockFactoryInterface $templateBlockFactory,
        SubscriptionPlans $subscriptionPlans,
        SubscriptionCrud $subscriptionCrud,
        string $adminTemplatesPath
    ) {

        $this->pathTemplateFactory = $pathTemplateFactory;
        $this->templateBlockFactory = $templateBlockFactory;
        $this->subscriptionPlans = $subscriptionPlans;
        $this->adminTemplatesPath = $adminTemplatesPath;
        $this->subscriptionCrud = $subscriptionCrud;
    }

    /**
     * Add tabs to the edit product page tabs list.
     *
     * @param array $productDataTabs
     *
     * @return array
     */
    public function addProductDataTabsToTheDataTabsList(array $productDataTabs): array
    {
        $productDataTabs['woo-ecurring-tab'] = [
            'label' => __('eCurring', 'woo-ecurring'),
            'target' => 'woo_ecurring_product_data',
        ];

        return $productDataTabs;
    }

    /**
     * Handle rendering of eCurring tab content on the edit product page.
     *
     * @param int $productId The id of the product to render content for.
     */
    public function renderProductDataFields(int $productId): void
    {
        $wcSelectTemplate = new WcSelectTemplate();
        $product = wc_get_product($productId);

        $context = [
            'id' => '_woo_ecurring_product_data',
            'wrapper_class' => 'show_if_simple',
            'label' => __('Product', 'woo-ecurring'),
            'description' => '',
            'options' => $this->getSubscriptionPlanOptions(),
            'value' => $this->subscriptionCrud->getProductSubscriptionId($product) ?? '',
        ];

        $selectBlock = $this->templateBlockFactory->fromTemplate($wcSelectTemplate, $context);
        $tabContentTemplateFile = $this->getTemplatePath('product-edit-page/ecurring-tab.php');
        $template = $this->pathTemplateFactory->fromPath($tabContentTemplateFile);

        try {
            $tabContent = $template->render(
                [
                    'select' => $selectBlock,
                ]
            );
            echo wp_kses($tabContent, $this->getAllowedHtmlForProductDataFields());
        } catch (Throwable $throwable) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Failed to render template file %1$s, ' .
                    'exception of type %2$s was caught when trying to render: %3$s',
                    $tabContentTemplateFile,
                    get_class($throwable),
                    $throwable->getMessage()
                )
            );
        }
    }

    /**
     * Save product fields on saving product.
     *
     * @param int $productId Id of the product to save fields for.
     */
    public function savePostedProductFields(int $productId): void
    {
        $subscriptionPlan = filter_input(
            INPUT_POST,
            '_woo_ecurring_product_data',
            FILTER_SANITIZE_STRING
        );

        if (is_string($subscriptionPlan) && $subscriptionPlan !== '0') {
            update_post_meta($productId, '_ecurring_subscription_plan', $subscriptionPlan);

            return;
        }

        delete_post_meta($productId, '_ecurring_subscription_plan');
    }

    /**
     * Get list of the available subscription plans to be displayed as HTML select options.
     *
     * @return array Subscription plans array with ids as keys and names as values.
     */
    protected function getSubscriptionPlanOptions(): array
    {
        $subscriptionPlans = [];
        $subscriptionPlans[0] = sprintf(
            '- %1$s -',
            _x(
                'No subscription plan',
                'Option text for subscription plan select on product page',
                'woo-ecurring'
            )
        );

        $subscriptionPlansData = $this->subscriptionPlans->getSubscriptionPlans()->data ?? [];

        $plans = [];

        foreach ($subscriptionPlansData as $plan) {
            $plans[$plan->id] = $plan->attributes->name;
        }

        $subscriptionPlans += $plans;

        return $subscriptionPlans;
    }

    /**
     * Return set of allowed HTML tags for the ecurring tab on the edit product page.
     *
     * @see wp_kses For more details, array structure, etc.
     *
     * @return array
     */
    protected function getAllowedHtmlForProductDataFields(): array
    {
        return [
            'div' => [
                'id' => [],
                'class' => [],
                'style' => [],
            ],
            'p' => [
                'class' => [],
                'style' => [],
            ],
            'label' => [],
            'select' => [
                'style' => [],
                'id' => [],
                'class' => [],
                'name' => [],
            ],
            'option' => [
                'value' => [],
                'selected' => [],
            ],
        ];
    }

    /**
     * Return template full path based on the path relative to the admin templates dir.
     *
     * @param string $template Template path in the admin templates dir.
     *
     * @return string Full template path
     */
    protected function getTemplatePath(string $template): string
    {
        return trailingslashit($this->adminTemplatesPath) . $template;
    }
}