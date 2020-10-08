<?php

namespace Ecurring\WooEcurring\AdminPages;

use Dhii\Output\RendererInterface;

/**
 * Handle admin pages requests.
 */
class AdminController {

	private const PLUGIN_SETTINGS_TAB_SLUG = 'mollie_subscriptions';
	/**
	 * @var RendererInterface
	 */
	protected $adminSettingsPageRenderer;

	/**
	 * @param RendererInterface $adminSettingsPageRenderer To render admin settings page content.
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
		add_action('woocommerce_settings_tabs' . self::PLUGIN_SETTINGS_TAB_SLUG, [$this, 'renderPluginSettingsPage']);
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
