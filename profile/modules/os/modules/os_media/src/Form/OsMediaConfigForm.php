<?php

namespace Drupal\os_media\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * API Configuration form.
 */
class OsMediaConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['os_media.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os_media_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('os_media.settings');

    $form['embedly_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Embedly Api Url'),
      '#default_value' => $config->get('embedly_url'),
    ];

    $form['embedly_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Embedly Api Key'),
      '#default_value' => $config->get('embedly_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!UrlHelper::isValid($values['embedly_url'])) {
      $form_state->setErrorByName('embedly_url', $this->t('Please enter a valid url'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $url = $form_state->getValue('embedly_url');
    $parsed_url = parse_url($url);
    if (!isset($parsed_url['scheme'])) {
      $url = "https://{$url}";
    }
    elseif ($parsed_url['scheme'] === 'http') {
      $url = str_replace('http', 'https', $url);
    }
    $this->config('os_media.settings')
      ->set('embedly_url', $url)
      ->set('embedly_key', $form_state->getValue('embedly_key'))
      ->save();
  }

}
