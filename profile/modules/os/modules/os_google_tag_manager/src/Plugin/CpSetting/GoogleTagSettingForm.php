<?php

namespace Drupal\os_google_tag_manager\Plugin\CpSetting;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\cp_settings\CpSettingBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * CP setting.
 *
 * @CpSetting(
 *   id = "google_tag_manager",
 *   title = @Translation("Google Tag Manager Setting Form"),
 *   group = {
 *    "id" = "tag_manager",
 *    "title" = @Translation("Google Tag Manager"),
 *    "parent" = "cp.settings.global"
 *   }
 * )
 */
class GoogleTagSettingForm extends CpSettingBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() : array {
    return [
      'os_gtm.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form, $formState, ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('os_gtm.settings');

    $form['container_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container ID'),
      '#description' => $this->t('The ID assigned by Google Tag Manager (GTM) for this website container. To get a container ID, <a href="https://tagmanager.google.com/">sign up for GTM</a> and create a container for your website.'),
      '#default_value' => $config->get('container_id'),
      '#attributes' => ['placeholder' => ['GTM-xxxxxx']],
      '#size' => 12,
      '#maxlength' => 15,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('container_id')) {
      return;
    }
    parent::validateForm($form, $form_state);
    // Trim the text values.
    $container_id = trim($form_state->getValue('container_id'));

    // Replace all types of dashes (n-dash, m-dash, minus) with a normal dash.
    $container_id = str_replace(['–', '—', '−'], '-', $container_id);

    if (!preg_match('/^GTM-\w{4,}$/', $container_id)) {
      $form_state->setError($form['container_id'], $this->t('A valid container ID is case sensitive and formatted like GTM-xxxxxx.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(FormStateInterface $formState, ConfigFactoryInterface $configFactory) {
    $config = $configFactory->getEditable('os_gtm.settings');
    $config
      ->set('container_id', $formState->getValue('container_id'))
      ->save();
  }

}
