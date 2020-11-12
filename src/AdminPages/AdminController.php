<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\AdminPages;

use Brain\Nonces\NonceInterface;
use Dhii\Output\Template\TemplateInterface;
use Ecurring\WooEcurring\AdminPages\Form\FormFieldsCollectionBuilderInterface;
use Ecurring\WooEcurring\AdminPages\Form\NonceFieldBuilderInterface;
use Ecurring\WooEcurring\Settings\SettingsCrudInterface;
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
     * @param TemplateInterface                    $adminSettingsPageRenderer To render admin settings page content.
     * @param FormFieldsCollectionBuilderInterface $formBuilder
     * @param SettingsCrudInterface                $settingsCrud
     * @param string                               $fieldsCollectionName
     * @param NonceInterface                       $nonce
     * @param NonceFieldBuilderInterface           $nonceFieldBuilder
     */
    public function __construct(
        TemplateInterface $adminSettingsPageRenderer,
        FormFieldsCollectionBuilderInterface $formBuilder,
        SettingsCrudInterface $settingsCrud,
        string $fieldsCollectionName,
        NonceInterface $nonce,
        NonceFieldBuilderInterface $nonceFieldBuilder
    ) {

        $this->adminSettingsPageRenderer = $adminSettingsPageRenderer;
        $this->formFieldsCollectionBuilder = $formBuilder;
        $this->fieldsCollectionName = $fieldsCollectionName;
        $this->settingsCrud = $settingsCrud;
        $this->nonce = $nonce;
        $this->nonceFieldBuilder = $nonceFieldBuilder;
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
            echo $this->adminSettingsPageRenderer->render($context);
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
