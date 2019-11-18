<?php

namespace Drupal\cp_users\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\CalculatedGroupPermissionsItem;
use Drupal\group\Access\CalculatedGroupPermissionsItemInterface;
use Drupal\group\Access\GroupPermissionCalculatorBase;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

/**
 * Makes sure that custom role permissions are included in the access cache.
 */
class CustomRolePermissionCalculator extends GroupPermissionCalculatorBase {

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Creates a new CustomRolePermissionCalculator object.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   */
  public function __construct(VsiteContextManagerInterface $vsite_context_manager) {
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateMemberPermissions(AccountInterface $account) {
    /** @var \Drupal\group\Access\RefinableCalculatedGroupPermissionsInterface $calculated_permissions */
    $calculated_permissions = parent::calculateMemberPermissions($account);
    /** @var \Drupal\group\Entity\GroupInterface|null $active_vsite */
    $active_vsite = $this->vsiteContextManager->getActiveVsite();

    $calculated_permissions->addCacheContexts(['vsite']);

    if ($active_vsite) {
      /** @var \Drupal\group\GroupMembership|false $membership */
      $membership = $active_vsite->getMember($account);

      if ($membership) {
        $permission_sets = [];

        foreach ($membership->getRoles() as $role) {
          $permission_sets[] = $role->getPermissions();
          $calculated_permissions->addCacheableDependency($role);
        }

        $permissions = $permission_sets ? array_merge(...$permission_sets) : [];
        $item = new CalculatedGroupPermissionsItem(
          CalculatedGroupPermissionsItemInterface::SCOPE_GROUP,
          $active_vsite->id(),
          $permissions
        );

        $calculated_permissions->addItem($item);
        $calculated_permissions->addCacheableDependency($membership);
      }
    }

    return $calculated_permissions;
  }

}
