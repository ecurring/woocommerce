<?php

namespace Ecurring\WooEcurring\EventListener;

/**
 * Settings form submission event handler.
 */
class SettingsFormSubmitEventListener implements EventListenerInterface {

	/**
	 * @var string
	 */
	protected $formSubmitEventName;

	/**
	 * @param string $formSubmitEventName Action name to listen to.
	 */
	public function __construct(string $formSubmitEventName) {
		$this->formSubmitEventName = $formSubmitEventName;
	}

	/**
	 * @inheritDoc
	 */
	public function init(): void {
		add_action($this->formSubmitEventName, [$this, 'onSettingsFormSubmission']);
	}

	public function onSettingsFormSubmission()
	{
		//validate, sanitize data and save form
	}
}
