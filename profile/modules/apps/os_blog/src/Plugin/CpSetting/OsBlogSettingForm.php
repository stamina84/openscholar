<?php

namespace Drupal\os_blog\Plugin\CpSetting;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cp_settings\CpSettingBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * CP setting.
 *
 * @CpSetting(
 *   id = "os_blog",
 *   title = @Translation("OS Blog Setting Form"),
 *   group = {
 *    "id" = "blog_setting",
 *    "title" = @Translation("Blog Comments"),
 *    "parent" = "cp.settings.app"
 *   }
 * )
 */
class OsBlogSettingForm extends CpSettingBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(): array {
    return [
      'os_blog.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form, FormStateInterface $formState, ConfigFactoryInterface $configFactory) {
    $blog_config = $configFactory->get('os_blog.settings');

    $form['comment_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose which comment type you`d like to use'),
      '#options' => [
        'no_comments' => $this->t('No comments'),
        'disqus_comments' => $this->t('Disqus comments'),
      ],
      '#default_value' => $blog_config->get('comment_type'),
    ];

    $description = $this->t('Enter the website you used to register your Disqus account.');
    $form['disqus_shortname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disqus shortname'),
      '#description' => $description,
      '#default_value' => $blog_config->get('disqus_shortname'),
      '#states' => [
        'visible' => [
          ':input[name="comment_type"]' => [
            'value' => 'disqus_comments',
          ],
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(FormStateInterface $formState, ConfigFactoryInterface $configFactory) {
    $blog_config = $configFactory->getEditable('os_blog.settings');
    $blog_config
      ->set('comment_type', $formState->getValue('comment_type'))
      ->set('disqus_shortname', $formState->getValue('disqus_shortname'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $app_access_checker = \Drupal::service('os_app_access.app_access');
    $parent_access_result = parent::access($account);
    $app_access_result = $app_access_checker->access($account, 'blog');

    return $app_access_result->orIf($parent_access_result);
  }

}
