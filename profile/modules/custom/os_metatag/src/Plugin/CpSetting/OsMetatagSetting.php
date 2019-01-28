<?php

namespace Drupal\os_metatag\Plugin\CpSetting;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\cp_settings\CpSettingInterface;

/**
 * CP metatag setting.
 *
 * @CpSetting(
 *   id = "os_metatag_setting",
 *   title = @Translation("OS Metatag"),
 *   group = {
 *    "id" = "seo",
 *    "title" = @Translation("SEO"),
 *    "parent" = "cp.settings"
 *   }
 * )
 */
class OsMetatagSetting extends PluginBase implements CpSettingInterface {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(): array {
    return ['os_metatag.setting'];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form, ConfigFactoryInterface $configFactory) {
    $config = $configFactory->get('os_metatag.setting');
    $form['site_title'] = [
      '#type' => 'textfield',
      '#title' => t('Site Title'),
      '#default_value' => $config->get('site_title'),
    ];
    $form['meta_description'] = [
      '#type' => 'textarea',
      '#title' => t('Meta Description'),
      '#default_value' => $config->get('meta_description'),
    ];
    $form['publisher_url'] = [
      '#type' => 'textfield',
      '#title' => t('Publisher URL'),
      '#default_value' => $config->get('publisher_url'),
    ];
    $form['author_url'] = [
      '#type' => 'textfield',
      '#title' => t('Author URL'),
      '#default_value' => $config->get('author_url'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(
    FormStateInterface $formState,
    ConfigFactoryInterface $configFactory
  ) {
    $config = $configFactory->getEditable('os_metatag.setting');
    $config->set('site_title', $formState->getValue('site_title'));
    $config->set('meta_description', $formState->getValue('meta_description'));
    $config->set('publisher_url', $formState->getValue('publisher_url'));
    $config->set('author_url', $formState->getValue('author_url'));
    $config->save(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowed();
  }

}