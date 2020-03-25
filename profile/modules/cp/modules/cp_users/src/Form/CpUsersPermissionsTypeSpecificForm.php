<?php

namespace Drupal\cp_users\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\cp_users\CpRolesHelperInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\vsite\Plugin\AppManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides the cp_users permission administration form.
 *
 * It is different from \Drupal\group\Form\GroupPermissionsTypeSpecificForm
 * because it hides some special group roles from the settings.
 *
 * @see \Drupal\group\Form\GroupPermissionsTypeSpecificForm
 */
final class CpUsersPermissionsTypeSpecificForm extends CpUsersPermissionsForm {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The specific group role for this form.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Activated vsite.
   *
   * @var \Drupal\group\Entity\GroupInterface|null
   */
  protected $activeVsite;

  /**
   * Vsite app manager.
   *
   * @var \Drupal\vsite\Plugin\AppManager
   */
  protected $vsiteAppManager;

  /**
   * Creates a new CpUsersPermissionsTypeSpecificForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\cp_users\CpRolesHelperInterface $cp_roles_helper
   *   CpRoles helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   * @param \Drupal\vsite\Plugin\AppManager $vsiteAppManager
   *   Vsite app manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GroupPermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, CpRolesHelperInterface $cp_roles_helper, EntityTypeManagerInterface $entity_type_manager, VsiteContextManagerInterface $vsite_context_manager, AppManager $vsiteAppManager) {
    parent::__construct($config_factory, $permission_handler, $module_handler, $cp_roles_helper, $vsiteAppManager, $vsite_context_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->vsiteContextManager = $vsite_context_manager;
    $this->activeVsite = $vsite_context_manager->getActiveVsite();
    $this->vsiteAppManager = $vsiteAppManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('group.permissions'),
      $container->get('module_handler'),
      $container->get('cp_users.cp_roles_helper'),
      $container->get('entity_type.manager'),
      $container->get('vsite.context_manager'),
      $container->get('vsite.app.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroupType() {
    return $this->groupType;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInfo() {
    $list = [
      'role_info' => [
        '#theme' => 'item_list',
        '#items' => [
          ['#markup' => $this->t('<strong>Basic member:</strong> The default role for anyone in the group. Behaves like the "Authenticated user" role does globally.')],
        ],
      ],
    ];

    if ($this->currentUser()->hasPermission('manage default group roles')) {
      $message = $this->t('<strong>Use this <a href="@setting_link">setting</a> to edit permissions of default roles.</strong>', [
        '@setting_link' => Url::fromRoute('entity.group_type.permissions_form', [
          'group_type' => $this->groupType->id(),
        ])->toString(),
      ]);
      $list['edit_default_roles_info'] = [
        '#markup' => new FormattableMarkup("<p>$message</p>", []),
      ];
    }

    return array_merge($list, parent::getInfo());
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroupRoles() {
    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');

    $query = $group_role_storage
      ->getQuery()
      ->condition('id', $this->cpRolesHelper->getNonConfigurableGroupRoles($this->activeVsite), 'NOT IN')
      ->condition('group_type', $this->groupType->id(), '=')
      ->condition('permissions_ui', 1, '=');

    $results = $query->execute();
    if ($this->activeVsite) {
      /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
      $group_type = $this->activeVsite->getGroupType();
      $cpRolesHelper = $this->cpRolesHelper;
      $current_user = $this->currentUser();
      $output = array_filter($results, static function ($role) use ($cpRolesHelper, $current_user, $group_type) {
        return $cpRolesHelper->accountHasAccessToRestrictedRole($current_user, $group_type, $role);
      });

      return $group_role_storage->loadMultiple(array_values($output));

    }
    else {
      return $group_role_storage->loadMultiple(array_values($results));
    }

  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load group type from vsite context.
    $this->groupType = $this->vsiteContextManager->getActiveVsite()->getGroupType();
    $form = parent::buildForm($form, $form_state);

    // Prevent permission edit for default roles.
    /** @var string[] $default_roles */
    $default_roles = $this->cpRolesHelper->getDefaultGroupRoles($this->activeVsite);
    /** @var string[] $restricted_permissions */
    $restricted_permissions = $this->cpRolesHelper->getRestrictedPermissions($this->getGroupType());

    foreach ($this->getPermissions() as $provider => $sections) {
      $provider_attributes = $this->getProviderKeyTitle($provider);
      $provider_key = $provider_attributes['provider_key'];

      foreach ($sections as $permissions) {
        foreach (array_diff(array_keys($permissions), $restricted_permissions) as $permission) {
          foreach ($default_roles as $default_role) {
            if (isset($form[$provider_key]['permissions'][$permission])) {
              $form[$provider_key]['permissions'][$permission][$default_role]['#disabled'] = TRUE;
            }
          }
        }
      }
    }

    return $form;
  }

}
