<?php

namespace Drupal\cp_users;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Helper methods for cp_users.
 */
final class CpUsersHelper implements CpUsersHelperInterface {

  public const CP_USERS_NEW_USER = 'cp_users_new_user';
  public const CP_USERS_ADD_TO_GROUP = 'cp_users_add_to_group';
  public const CP_USERS_DELETE_FROM_GROUP = 'cp_users_delete_from_group';

  /**
   * {@inheritdoc}
   */
  public function isVsiteOwner(GroupInterface $vsite, AccountInterface $account): bool {
    return ($vsite->getOwnerId() === $account->id());
  }

}
