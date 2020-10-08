<?php

namespace Ecurring\WooEcurring\Template;

use Dhii\Output\Template\TemplateInterface;
use function wc_get_template_html;

class WcBasedAdminSettingsTemplate implements TemplateInterface {

	protected const VIEW_DIR_PATH = WOOECUR_PLUGIN_DIR . 'view/';

	/**
	 * @inheritDoc
	 */
	public function render($context = null) {
		return wc_get_template_html(
			'mollie-subscriptions-settings.php',
			$context,
			'',
			self::VIEW_DIR_PATH
		);
	}
}
