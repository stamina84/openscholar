<?php

namespace Drupal\os_pages\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks access to book outline on non-book pages.
 */
class OutlineAccessCheck implements AccessInterface {

  /**
   * The vsite manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(VsiteContextManagerInterface $vsite_context_manager, RequestStack $request_stack) {
    $this->vsiteContextManager = $vsite_context_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * Check access to book-outline for non book pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this outline .
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    $node = $this->requestStack->getCurrentRequest()->attributes->get('node');
    $active_vsite = $this->vsiteContextManager->getActiveVsite();

    if (!$active_vsite) {
      return AccessResult::forbidden();
    }

    if (!empty($node->book) && isset($node->book['bid'])) {
      if ($node->book['bid'] !== 0 && $active_vsite->hasPermission('administer book outlines', $account)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
