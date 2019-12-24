<?php

namespace Drupal\os_blog\Plugin\CpSetting;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cp_settings\CpSettingBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

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
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new CpSettingBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VsiteContextManagerInterface $vsite_context_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vsite_context_manager);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('vsite.context_manager'),
    $container->get('config.factory')
    );
  }

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
    $blog_config = $this->configFactory->get('os_blog.settings');

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
    $blog_config = $this->configFactory->getEditable('os_blog.settings');
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
