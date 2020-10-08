<?php

namespace Ecurring\WooEcurring\AdminPages;

use ChriCo\Fields\Element\ElementInterface;
use ChriCo\Fields\ElementFactory;
use Dhii\Output\Template\TemplateInterface;
use eCurring_WC_Plugin;
use Exception;

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
	 * @param TemplateInterface $adminSettingsPageRenderer To render admin settings page content.
	 * @param ElementFactory    $elementFactory
	 * @param array             $adminSettingsFormConfiguration
	 */
	public function __construct(RendererInterface $adminSettingsPageRenderer)
	{
		$this->adminSettingsPageRenderer = $adminSettingsPageRenderer;
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
		echo $this->adminSettingsPageRenderer->render();
	}


}
