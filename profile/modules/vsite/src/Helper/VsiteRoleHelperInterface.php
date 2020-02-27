<?php

namespace Drupal\vsite\Helper;

use Drupal\group\Entity\GroupInterface;

/**
 * Contract for VsiteRoleHelperInterface.
 */
interface VsiteRoleHelperInterface {

  /**
   * Assigning admin role to group owner.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The vsite for which admin role will be assigned.
   */
  public function assignGroupAdminRoleToOwner(GroupInterface $group): void;

}
