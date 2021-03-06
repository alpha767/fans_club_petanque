<?php

namespace MailPoet\Form\Templates;

if (!defined('ABSPATH')) exit;


use MailPoet\Entities\FormEntity;
use MailPoet\Form\Templates\Templates\DefaultForm;
use MailPoet\Form\Templates\Templates\DemoForm;
use MailPoet\Form\Templates\Templates\InitialForm;
use MailPoet\Settings\SettingsController;
use MailPoet\UnexpectedValueException;

class TemplateRepository {
  const INITIAL_FORM_TEMPLATE = 'initial_form';
  const DEFAULT_FORM_TEMPLATE = 'default_form';

  private $templates = [
    'initial_form' => InitialForm::class,
    'default_form' => DefaultForm::class,
    'demo_form' => DemoForm::class,
  ];

  /** @var SettingsController */
  private $settings;

  public function __construct(SettingsController $settings) {
    $this->settings = $settings;
  }

  public function getFormEntityForTemplate(string $templateId): FormEntity {
    if (!isset($this->templates[$templateId])) {
      throw UnexpectedValueException::create()
        ->withErrors(["Template with id $templateId doesn't exist."]);
    }
    /** @var Template $template */
    $template = new $this->templates[$templateId]();
    $formEntity = new FormEntity($template->getName());
    $formEntity->setBody($template->getBody());
    $settings = $formEntity->getSettings();
    $settings['success_message'] = $this->getDefaultSuccessMessage();
    $formEntity->setSettings($settings);
    $formEntity->setStyles($template->getStyles());
    return $formEntity;
  }

  /**
   * @param string[] $templateIds
   * @return FormEntity[] associative array with template ids as keys
   */
  public function getFormsForTemplates(array $templateIds): array {
    $result = [];
    foreach ($templateIds as $templateId) {
      $result[$templateId] = $this->getFormEntityForTemplate($templateId);
    }
    return $result;
  }

  private function getDefaultSuccessMessage() {
    if ($this->settings->get('signup_confirmation.enabled')) {
      return __('Check your inbox or spam folder to confirm your subscription.', 'mailpoet');
    }
    return __('You’ve been successfully subscribed to our newsletter!', 'mailpoet');
  }
}
