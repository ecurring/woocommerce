<?php

namespace Ecurring\WooEcurring\AdminPages;

use ChriCo\Fields\ViewFactory;
use Dhii\Output\Template\TemplateInterface;
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
	 * @var FormBuilder
	 */
	protected $formBuilder;

	/**
	 * @param TemplateInterface $adminSettingsPageRenderer To render admin settings page content.
	 * @param FormBuilder       $formBuilder
	 */
	public function __construct(
		TemplateInterface $adminSettingsPageRenderer,
		FormBuilder $formBuilder
	) {
		$this->adminSettingsPageRenderer = $adminSettingsPageRenderer;
		$this->formBuilder = $formBuilder;
	}

	/**
	 * Initialize hooks listeners.
	 */
	public function init()
	{
		add_action('woocommerce_settings_tabs_array', [$this, 'registerPluginSettingsTab'], 100);
		add_action('woocommerce_settings_tabs_' . self::PLUGIN_SETTINGS_TAB_SLUG, [$this, 'renderPluginSettingsPage']);
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
			$form = $this->formBuilder->buildForm();
			$formView = $this->formBuilder->buildFormView($form);
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
}
