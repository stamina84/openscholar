<?php

namespace Drupal\os_widgets\Plugin\CpSetting;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cp_settings\CpSettingBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vsite.context_manager'),
      $container->get('entity_type.manager')
    );
  }

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VsiteContextManagerInterface $vsite_context_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vsite_context_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

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

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): AccessResultInterface {
    /** @var \Drupal\Core\Access\AccessResultInterface $access_result */
    $access_result = parent::access($account);

    if ($access_result->isForbidden()) {
      return $access_result;
    }

    if ($account->id() == 1) {
      return AccessResult::allowed();
    }

    $roles = $account->getRoles(TRUE);
    $entityStorage = $this->entityTypeManager->getStorage('user_role');

    foreach ($roles as $role) {
      /** @var \Drupal\user\Entity\Role $roleEntity */
      $roleEntity = $entityStorage->load($role);
      if ($roleEntity->isAdmin()) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}
