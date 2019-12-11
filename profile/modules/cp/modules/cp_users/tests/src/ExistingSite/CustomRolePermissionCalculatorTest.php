<?php

namespace Drupal\Tests\cp_users\ExistingSite;

use Drupal\group\Access\CalculatedGroupPermissionsItemInterface;

/**
 * Tests custom role permission calculator.
 *
 * @coversDefaultClass \Drupal\cp_users\Access\CustomRolePermissionCalculator
 * @group kernel
 * @group cp
 */
class CustomRolePermissionCalculatorTest extends CpUsersExistingSiteTestBase {

  /**
   * Permission calculator.
   *
   * @var \Drupal\group\Access\ChainGroupPermissionCalculatorInterface
   */
  protected $permissionCalculator;

  /**
   * Test group member.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $groupMember;

  /**
   * Test group role.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected $groupRole;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->permissionCalculator = $this->container->get('group_permission.chain_calculator');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');

    $this->groupRole = $this->createRoleForGroup($this->group);
    $this->groupRole->grantPermissions([
      'update any group_node:faq entity',
    ])->save();

    $this->groupMember = $this->createUser();
    $this->group->addMember($this->groupMember, [
      'group_roles' => [
        $this->groupRole->id(),
      ],
    ]);
  }

  /**
   * Tests the behavior when vsite not active.
   *
   * @covers ::calculateMemberPermissions
   */
  public function testInactiveVsite(): void {
    $calculated_permissions = $this->permissionCalculator->calculateMemberPermissions($this->groupMember);
    $this->assertContains('vsite', $calculated_permissions->getCacheContexts());
  }

  /**
   * Tests the behavior when vsite is active.
   *
   * @covers ::calculateMemberPermissions
   */
  public function testActiveVsite(): void {
    $this->vsiteContextManager->activateVsite($this->group);

    $calculated_permissions = $this->permissionCalculator->calculateMemberPermissions($this->groupMember);

    $this->assertContains('vsite', $calculated_permissions->getCacheContexts());

    $item = $calculated_permissions->getItem(CalculatedGroupPermissionsItemInterface::SCOPE_GROUP, $this->group->id());
    $membership = $this->group->getMember($this->groupMember);

    $this->assertNotFalse($item);
    $this->assertContains("config:group.role.{$this->groupRole->id()}", $calculated_permissions->getCacheTags());
    $this->assertContains("group_content:{$membership->getGroupContent()->id()}", $calculated_permissions->getCacheTags());
    $this->assertContains('update any group_node:faq entity', $item->getPermissions());
  }

}
