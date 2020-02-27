<?php

namespace Drupal\vsite\Helper;

use Drupal\group\Entity\GroupInterface;

/**
 * Class VsiteRoleHelper.
 *
 * @package Drupal\vsite\Helper
 */
class VsiteRoleHelper implements VsiteRoleHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function assignGroupAdminRoleToOwner(GroupInterface $group): void {
    // Get the group owner.
    $owner = $group->getOwner();

    // Get the membership details of the owner.
    $membership = $group->getMember($owner);

    // Get the roles of the owner.
    $roles = $membership->getRoles();
    // Check whether the owner has admin role.
    if (!isset($roles[$group->getGroupType()->id() . '-administrator'])) {
      // No, so add the admin role to the membership
      // Get the group_content entity.
      $group_content = $membership->getGroupContent();
      // Set target group role.
      $group_content->group_roles->appendItem(['target_id' => $group->getGroupType()->id() . '-administrator']);
      // Save updated entity.
      $group_content->save();
    }
  }

}
