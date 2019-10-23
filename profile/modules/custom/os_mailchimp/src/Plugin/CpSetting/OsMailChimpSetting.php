<?php

namespace Drupal\os_mailchimp\Plugin\CpSetting;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cp_settings\CpSettingBase;

/**
 * CP mailchimp setting.
 *
 * @CpSetting(
 *   id = "os_mailchimp_setting",
 *   title = @Translation("OS Mailchimp"),
 *   group = {
 *    "id" = "mailchimp",
 *    "title" = @Translation("Mailchimp"),
 *    "parent" = "cp.settings.global"
 *   }
 * )
 */
class OsMailChimpSetting extends CpSettingBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(): array {
    return ['mailchimp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form, FormStateInterface $formState, ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('mailchimp.settings');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('MailChimp API key'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#tree' => TRUE,
    ];

    $form['advanced']['mailchimp_code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mailchimp embedded Form'),
      '#rows' => 20,
      '#description' => $this->t('Enter your embedded form code that is copied from Mailchimp.'),
      '#default_value' => $config->get('mailchimp_code'),
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(FormStateInterface $formState, ConfigFactoryInterface $configFactory) {
    $config = $configFactory->getEditable('mailchimp.settings');
    $config->set('api_key', $formState->getValue('api_key'));
    $advanced = $formState->getValue('advanced');
    $mailchimp_code = $advanced['mailchimp_code'];
    $config->set('mailchimp_code', $mailchimp_code);
    $config->save(TRUE);

    $cache = \Drupal::cache('mailchimp');
    $cache->invalidate('lists');
    Cache::invalidateTags([
      'mailchimp',
    ]);
  }

}
