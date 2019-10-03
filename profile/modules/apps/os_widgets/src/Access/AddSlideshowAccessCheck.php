<?php

namespace Drupal\os_widgets\Access;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

/**
 * Checks access for add slideshow to given slideshow block content.
 */
class AddSlideshowAccessCheck implements AccessInterface {

  use StringTranslationTrait;

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;
  protected $account;
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $account, EntityTypeManagerInterface $entity_type_manager, VsiteContextManagerInterface $vsite_context_manager) {
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
    $this->vsiteContextManager = $vsite_context_manager;
  }

  /**
   * Checks access for access form.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   Given block content.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(AccountInterface $account, BlockContentInterface $block_content) {
    if ($block_content->bundle() != 'slideshow') {
      return AccessResult::forbidden($this->t('Given block content is not a slideshow.')->render());
    }
    $group_contents = $this->entityTypeManager->getStorage('group_content')->loadByEntity($block_content);
    $group_content = array_shift($group_contents);
    if (empty($group_content)) {
      return AccessResult::forbidden($this->t('Given block content has no group.')->render());
    }
    $block_content_group = $group_content->getGroup();
    $group = $this->vsiteContextManager->getActiveVsite();
    if ($group && $group->id() != $block_content_group->id()) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
