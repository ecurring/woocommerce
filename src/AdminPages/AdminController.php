<?php

namespace Ecurring\WooEcurring\AdminPages;

use ChriCo\Fields\ViewFactory;
use Dhii\Output\Template\TemplateInterface;
use Ecurring\WooEcurring\Settings\SettingsCrudInterface;
use eCurring_WC_Plugin;
use Throwable;

/**
 * Handle admin pages requests.
 */
class AdminController {

	private const PLUGIN_SETTINGS_TAB_SLUG = 'mollie_subscriptions';
	/**
	 * @var TemplateInterface
	 */
	protected $adminSettingsPageRenderer;

	/**
	 * @var ViewFactory
	 */
	protected $viewFactory;

	/**
	 * @var FormFieldsCollectionBuilder
	 */
	protected $formBuilder;

	/**
	 * @var string
	 */
	protected $fieldsCollectionName;
	/**
	 * @var SettingsCrudInterface
	 */
	protected $settingsCrud;

	/**
	 * @param TemplateInterface           $adminSettingsPageRenderer To render admin settings page content.
	 * @param FormFieldsCollectionBuilder $formBuilder
	 * @param SettingsCrudInterface       $settingsCrud
	 * @param string                      $fieldsCollectionName
	 */
	public function __construct(
		TemplateInterface $adminSettingsPageRenderer,
		FormFieldsCollectionBuilder $formBuilder,
		SettingsCrudInterface $settingsCrud,
		string $fieldsCollectionName
	) {
		$this->adminSettingsPageRenderer = $adminSettingsPageRenderer;
		$this->formBuilder = $formBuilder;
		$this->fieldsCollectionName = $fieldsCollectionName;
		$this->settingsCrud = $settingsCrud;
	}

	/**
	 * Initialize hooks listeners.
	 */
	public function init()
	{
		add_action('woocommerce_settings_tabs_array', [$this, 'registerPluginSettingsTab'], 100);
		add_action('woocommerce_settings_tabs_' . self::PLUGIN_SETTINGS_TAB_SLUG, [$this, 'renderPluginSettingsPage']);
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
		$tabs[self::PLUGIN_SETTINGS_TAB_SLUG] = _x('Mollie Subscriptions', 'Plugin settings tab name', 'woo-ecurring');

		return $tabs;
	}

	/**
	 * Render admin settings page content.
	 */
	public function renderPluginSettingsPage(): void
	{
		try {
			$form = $this->formBuilder->buildFieldsCollection();
			$formView = $this->formBuilder->buildFormFieldsCollectionView($form);
			$context = ['view' => $formView, 'form' => $form];
			echo $this->adminSettingsPageRenderer->render($context);
		}catch ( Throwable $exception) {
			eCurring_WC_Plugin::debug(
				sprintf(
					'Failed to render plugin settings form. Exception was caught with code %1$d and message: %2$s',
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
	public function saveSettings()
	{
		$formData = $_POST[$this->fieldsCollectionName] ?? null;

		if($formData === null){
			return;
		}

		$fieldsCollection = $this->formBuilder->buildFieldsCollection();

		foreach ($fieldsCollection->elements() as $element)
		{
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
		wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=' . self::PLUGIN_SETTINGS_TAB_SLUG));
		die;
	}
}
