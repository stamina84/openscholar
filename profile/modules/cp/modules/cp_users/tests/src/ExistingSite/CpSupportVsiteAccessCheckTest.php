<?php

namespace Drupal\Tests\cp_users\ExistingSite;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Test CpUsersSupportAccessCheck.
 *
 * @group kernel
 * @group cp
 *
 * @coversDefaultClass \Drupal\cp_users\Access\CpUsersSupportAccessCheck
 */
class CpSupportVsiteAccessCheckTest extends OsExistingSiteTestBase {

  /**
   * @covers ::access
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function test(): void {
    /** @var \Drupal\cp_users\Access\CpUsersSupportAccessCheck $support_vsite_access_check_service */
    $support_vsite_access_check_service = $this->container->get('cp_users.support_vsite_access_check');
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');

    // Negative tests.
    $account = $this->createUser();
    $this->assertInstanceOf(AccessResultForbidden::class, $support_vsite_access_check_service->access($account));

    // Create with support user.
    $vsite_context_manager->activateVsite($this->group);
    $support_account = $this->createUser([
      'support vsite',
    ]);
    $this->assertInstanceOf(AccessResultAllowed::class, $support_vsite_access_check_service->access($support_account));

    // Negative test for group admin.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->assertInstanceOf(AccessResultNeutral::class, $support_vsite_access_check_service->access($group_admin));
  }

}
