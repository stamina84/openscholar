<?php

namespace Drupal\os;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\user\EntityOwnerInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Drupal\vsite_privacy\Plugin\VsitePrivacyLevelManager;

/**
 * Provides access check helpers for entity CRUD global paths.
 */
final class AccessHelper implements AccessHelperInterface {

  /**
   * List of block content types to restrict edit/delete access.
   */
  const RESTRICTED_BLOCKS = ['views'];

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Vsite privacy manager.
   *
   * @var \Drupal\vsite_privacy\Plugin\VsitePrivacyLevelManager
   */
  protected $vsitePrivacyManager;

  /**
   * Creates a new AccessHelper object.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\vsite_privacy\Plugin\VsitePrivacyLevelManager $vsite_privacy_manager
   *   Vsite privacy manager.
   */
  public function __construct(VsiteContextManagerInterface $vsite_context_manager, EntityTypeManagerInterface $entity_type_manager, VsitePrivacyLevelManager $vsite_privacy_manager) {
    $this->vsiteContextManager = $vsite_context_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->vsitePrivacyManager = $vsite_privacy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, string $plugin_id): AccessResultInterface {
    /** @var \Drupal\group\Entity\GroupInterface|null $vsite */
    $vsite = $this->vsiteContextManager->getActiveVsite();

    // Let the access stack handle this case.
    if (!$vsite) {
      return AccessResult::neutral();
    }

    // Only act if there are group content types for this node type.
    $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
    if (empty($group_content_types)) {
      return AccessResult::neutral();
    }

    // Pass the judgement here.
    if ($vsite->hasPermission("create $plugin_id entity", $account)) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    $is_an_entity_owner_type = ($entity instanceof EntityOwnerInterface);
    $plugin_id = "group_entity:{$entity->getEntityTypeId()}";

    if ($entity->getEntityTypeId() === 'node') {
      $plugin_id = "group_node:{$entity->bundle()}";
    }

    // Only act if there are group content types for this plugin.
    $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
    if (empty($group_content_types) || $entity->id() === NULL) {
      return AccessResult::neutral();
    }

    // Load all the group content for this node.
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $entity->id(),
      ]);

    // If the entity does not belong to any group, we have nothing to say.
    if (empty($group_contents)) {
      return AccessResult::neutral();
    }

    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = [];
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group = $group_content->getGroup();
      $groups[$group->id()] = $group;
    }

    switch ($operation) {
      case 'update':
      case 'delete':
        foreach ($groups as $group) {
          if ($group->hasPermission("$operation any $plugin_id entity", $account)) {
            return AccessResult::allowed();
          }

          if ($is_an_entity_owner_type) {
            /** @var \Drupal\user\EntityOwnerInterface $entity_owner */
            $entity_owner = $entity;

            if ($group->hasPermission("$operation own $plugin_id entity", $account) &&
              ($account->id() === $entity_owner->getOwner()->id())) {
              return AccessResult::allowed();
            }
          }
        }

        break;
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function checkBlocksAccess($bundle, $operation, AccountInterface $account) : AccessResultInterface {
    /** @var \Drupal\group\Entity\GroupInterface|null $vsite */
    $active_vsite = $this->vsiteContextManager->getActiveVsite();

    // Restrict widgets based on privacy level.
    if ($active_vsite) {
      $privacy_level = $active_vsite->get('field_privacy_level')->value;

      // Check for plugin access (same is used for vsite access).
      if (!$this->vsitePrivacyManager->checkAccessForPlugin($account, $privacy_level)) {
        return AccessResult::forbidden();
      }
    }

    // View permissions is already being checked by checkAccessForPlugin.
    // Restrict listed blocks edit/delete.
    if (in_array($bundle, self::RESTRICTED_BLOCKS) && !$this->isAdmin($account) && $operation != 'view') {
      return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

  /**
   * Checks if the user has administrative role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return bool
   *   If user is admin or not.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function isAdmin(AccountInterface $account) {
    if ($account->id() == 1) {
      return TRUE;
    }
    $roles = $account->getRoles(TRUE);
    foreach ($roles as $role) {
      /** @var \Drupal\user\Entity\Role $roleEntity */
      $roleEntity = $this->entityTypeManager->getStorage('user_role')->load($role);
      if ($roleEntity->isAdmin()) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
