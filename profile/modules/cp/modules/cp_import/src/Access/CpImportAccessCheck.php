<?php

namespace Drupal\cp_import\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\os_app_access\Access\AppAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom access checker for CpImport.
 */
class CpImportAccessCheck implements AccessInterface, ContainerInjectionInterface {

  /**
   * App access manager.
   *
   * @var \Drupal\os_app_access\Access\AppAccess
   */
  protected $appAccess;

  /**
   * Creates a new CpImportAccessCheck object.
   *
   * @param \Drupal\os_app_access\Access\AppAccess $appAccess
   *   App access instance.
   */
  public function __construct(AppAccess $appAccess) {
    $this->appAccess = $appAccess;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os_app_access.app_access')
    );
  }

  /**
   * Checks whether the CpImport form is accessible to the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user.
   * @param string $app_name
   *   The app id.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $app_name): AccessResultInterface {
    $access_result = $this->appAccess->access($account, $app_name);
    if ($access_result->isNeutral() || $access_result->isAllowed()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
