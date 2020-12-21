<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages;

use Brain\Nonces\NonceInterface;
use Dhii\Output\Template\PathTemplateFactoryInterface;
use Dhii\Output\Template\TemplateInterface;
use Ecurring\WooEcurring\AdminPages\Form\FormFieldsCollectionBuilderInterface;
use Ecurring\WooEcurring\AdminPages\Form\NonceFieldBuilderInterface;
use Ecurring\WooEcurring\Api\ApiClientInterface;
use Ecurring\WooEcurring\Settings\SettingsCrudInterface;
use Ecurring\WooEcurring\Template\SimpleBlock;
use Ecurring\WooEcurring\Template\WcSelect;
use eCurring_WC_Plugin;
use Throwable;

/**
 * Handle admin pages requests.
 */
class AdminController
{

    private const PLUGIN_SETTINGS_TAB_SLUG = 'mollie_subscriptions';
    /**
     * @var TemplateInterface
     */
    protected $adminSettingsPageRenderer;

    /**
     * @var FormFieldsCollectionBuilderInterface
     */
    protected $formFieldsCollectionBuilder;

    /**
     * @var string
     */
    protected $fieldsCollectionName;
    /**
     * @var SettingsCrudInterface
     */
    protected $settingsCrud;
    /**
     * @var NonceInterface
     */
    protected $nonce;
    /**
     * @var NonceFieldBuilderInterface
     */
    protected $nonceFieldBuilder;
    /**
     * @var ApiClientInterface
     */
    protected $apiClient;
    /**
     * @var PathTemplateFactoryInterface
     */
    protected $pathTemplateFactory;
    /**
     * @var string
     */
    protected $adminTemplatesPath;

    /**
     * @param TemplateInterface                    $adminSettingsPageRenderer To render admin settings page content.
     * @param FormFieldsCollectionBuilderInterface $formBuilder
     * @param SettingsCrudInterface                $settingsCrud
     * @param string                               $fieldsCollectionName
     * @param NonceInterface                       $nonce
     * @param NonceFieldBuilderInterface           $nonceFieldBuilder
     * @param ApiClientInterface                   $apiClient
     * @param PathTemplateFactoryInterface         $pathTemplateFactory
     */
    public function __construct(
        TemplateInterface $adminSettingsPageRenderer,
        FormFieldsCollectionBuilderInterface $formBuilder,
        SettingsCrudInterface $settingsCrud,
        string $fieldsCollectionName,
        NonceInterface $nonce,
        NonceFieldBuilderInterface $nonceFieldBuilder,
        ApiClientInterface $apiClient,
        PathTemplateFactoryInterface $pathTemplateFactory
    ) {

        $this->adminSettingsPageRenderer = $adminSettingsPageRenderer;
        $this->formFieldsCollectionBuilder = $formBuilder;
        $this->fieldsCollectionName = $fieldsCollectionName;
        $this->settingsCrud = $settingsCrud;
        $this->nonce = $nonce;
        $this->nonceFieldBuilder = $nonceFieldBuilder;
        $this->apiClient = $apiClient;
        $this->pathTemplateFactory = $pathTemplateFactory;
        $this->adminTemplatesPath = plugin_dir_path(WOOECUR_PLUGIN_FILE) . 'views/admin';
    }

    /**
     * Initialize hooks listeners.
     */
    public function init(): void
    {
        add_action('woocommerce_settings_tabs_array', [$this, 'registerPluginSettingsTab'], 100);
        add_action(
            'woocommerce_settings_tabs_' . self::PLUGIN_SETTINGS_TAB_SLUG,
            [$this, 'renderPluginSettingsPage']
        );
        add_action('admin_init', [$this, 'saveSettings'], 11);
        add_action('woocommerce_product_data_panels', [$this, 'renderProductDataFields']);
    }

    /**
     * Add plugin tab to the WC settings tabs.
     *
     * @param array $tabs Registered WC settings tabs.
     *
     * @return array WC settings tabs with this plugin tab added.
     */
    public function registerPluginSettingsTab(array $tabs): array
    {
        $tabs[self::PLUGIN_SETTINGS_TAB_SLUG] = _x(
            'Mollie Subscriptions',
            'Plugin settings tab name',
            'woo-ecurring'
        );

        return $tabs;
    }

    /**
     * Render admin settings page content.
     */
    public function renderPluginSettingsPage(): void
    {
        try {
            $form = $this->formFieldsCollectionBuilder->buildFieldsCollection();
            $formView = $this->formFieldsCollectionBuilder->buildFormFieldsCollectionView();
            $nonceField = $this->nonceFieldBuilder->buildNonceField($this->nonce);
            $nonceFieldView = $this->nonceFieldBuilder->buildNonceFieldView();
            $context = [
                'view' => $formView,
                'form' => $form,
                'nonceField' => $nonceField,
                'nonceFieldView' => $nonceFieldView,
            ];
            echo $this->adminSettingsPageRenderer->render($context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } catch (Throwable $exception) {
            eCurring_WC_Plugin::debug(
                sprintf(
                    'Failed to render plugin settings form.' .
                    'Exception was caught with code %1$d and message: %2$s',
                    $exception->getCode(),
                    $exception->getMessage()
                )
            );

            echo esc_html_x(
                'Failed to render plugin settings form. Please, check logs for more details.',
                'Error message if cannot render plugin settings page',
                'woo-ecurring'
            );
        }
    }

    /**
     * Save plugin settings on form submit.
     */
    public function saveSettings(): void
    {
        $formData = filter_input(
            INPUT_POST,
            $this->fieldsCollectionName,
            FILTER_SANITIZE_STRING,
            FILTER_REQUIRE_ARRAY
        );

        if ($formData === null) {
            return;
        }

        if (! $this->isNonceValid()) {
            wp_nonce_ays($this->nonce->action());
        }

        $fieldsCollection = $this->formFieldsCollectionBuilder->buildFieldsCollection();

        foreach ($fieldsCollection->elements() as $element) {
            $this->settingsCrud->updateOption($element->name(), $formData[$element->name()] ?? null);
        }

        $this->settingsCrud->persist();

        $this->redirectToSettingsPage();
    }

    /**
     * Handle rendering of eCurring tab content on the edit product page.
     */
    public function renderProductDataFields(): void
    {
        global $post;

        if (!isset($post, $post->ID)) {
            return;
        }

        $subscriptionPlans = [];
        $subscriptionPlans[0] = sprintf(
            '- %1$s -',
            _x('No subscription plan', 'Option text for subscription plan select on product page', 'woo-ecurring')
        );

        $subscriptionPlans += $this->apiClient->getAvailableSubscriptionPlans();
        $selectedPlan = get_post_meta($post->ID, '_ecurring_subscription_plan', true);
        $wcSelectTemplate = new WcSelect();

        $context = [
            'id' => '_woo_ecurring_product_data',
            'wrapper_class' => 'show_if_simple',
            'label' => __('Product', 'woo-ecurring'),
            'description' => '',
            'options' => $subscriptionPlans,
            'value' => $selectedPlan,
        ];

        $selectBlock = new SimpleBlock($context, $wcSelectTemplate);
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

    /**
     * Do redirect to the plugin settings page.
     */
    protected function redirectToSettingsPage(): void
    {
        wp_safe_redirect(
            admin_url('admin.php?page=wc-settings&tab=' . self::PLUGIN_SETTINGS_TAB_SLUG)
        );
        die;
    }

    /**
     * Validate nonce from posted form.
     *
     * @return bool
     *
     * @todo: move this to the separate class.
     */
    protected function isNonceValid(): bool
    {
        return $this->nonce->validate();
    }
}
