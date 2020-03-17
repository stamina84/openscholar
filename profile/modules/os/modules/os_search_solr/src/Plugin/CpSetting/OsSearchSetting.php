<?php

namespace Drupal\os_search_solr\Plugin\CpSetting;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\cp_settings\CpSettingBase;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Drupal\group\Access\ChainGroupPermissionCalculatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OpenScholar: Solr Search setting.
 *
 * @CpSetting(
 *   id = "solr_search",
 *   title = @Translation("OpenScholar Solr Search Setting"),
 *   group = {
 *    "id" = "solr_search",
 *    "title" = @Translation("Cache / Reindex"),
 *    "parent" = "cp.settings.global"
 *   }
 * )
 */
class OsSearchSetting extends CpSettingBase {

  /**
   * The group permissions hash generator service.
   *
   * @var \Drupal\group\Access\ChainGroupPermissionCalculatorInterface
   */
  protected $permissionCalculator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vsite.context_manager'),
      $container->get('group_permission.chain_calculator')
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
   * @param \Drupal\group\Access\ChainGroupPermissionCalculatorInterface $permission_calculator
   *   Group Permission Calculator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VsiteContextManagerInterface $vsite_context_manager, ChainGroupPermissionCalculatorInterface $permission_calculator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vsite_context_manager);
    $this->permissionCalculator = $permission_calculator;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames(): array {
    // This is not yet implemented.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$form, FormStateInterface $formState, ConfigFactoryInterface $configFactory): void {
    $form['label'] = [
      '#markup' => $this->t('Re-index this site'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(FormStateInterface $formState, ConfigFactoryInterface $configFactory): void {
    // Not yet implemented.
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
    $calculated_permissions = $this->permissionCalculator->calculatePermissions($account);

    foreach ($calculated_permissions->getItems() as $item) {
      if (in_array('manage vsite solr search', $item->getPermissions(), TRUE)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}
