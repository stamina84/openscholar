<?php

namespace Drupal\os_widgets\Plugin\CpSetting;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cp_settings\CpSettingBase;

/**
 * CP setting for dataverse URLs.
 *
 * @CpSetting(
 *   id = "os_dataverse_setting",
 *   title = @Translation("Dataverse URLs"),
 *   group = {
 *    "id" = "dataverse_urls",
 *    "title" = @Translation("Dataverse"),
 *    "parent" = "cp.settings.global"
 *   }
 * )
 */
class OsDataverse extends CpSettingBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() : array {
    return ['os_widgets.dataverse'];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form, FormStateInterface $formState, ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('os_widgets.dataverse');
    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#default_value' => $config->get('base_url'),
    ];
    $form['listing_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Listing url'),
      '#default_value' => $config->get('listing_base_url'),
    ];
    $form['search_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search base url'),
      '#default_value' => $config->get('search_base_url'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(FormStateInterface $form_state, ConfigFactoryInterface $config_factory) {
    $config = $config_factory->getEditable('os_widgets.dataverse');
    $config->set('base_url', $form_state->getValue('base_url'));
    $config->set('listing_base_url', $form_state->getValue('listing_base_url'));
    $config->set('search_base_url', $form_state->getValue('search_base_url'));
    $config->save(TRUE);
  }

}
