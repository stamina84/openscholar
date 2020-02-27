<?php

namespace Drupal\Tests\vsite\ExistingSite;

/**
 * VsiteRoleHelperTest.
 *
 * @group vsite
 * @group kernel
 */
class VsiteRoleHelperTest extends VsiteExistingSiteTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteRoleHelper = $this->container->get('vsite.role_helper');
    // Setup group with department group type.
    $this->group = $this->createGroup([
      'type' => 'department',
      'field_preset' => 'os_department',
    ]);
    $this->vsiteContextManager->activateVsite($this->group);
  }

  /**
   * Tests owner is having administrator role.
   */
  public function testVsiteGroupOwnerHasAdministratorRole() {
    $group = $this->group;
    // Get the group owner.
    $owner = $group->getOwner();
    // Get the membership details of the owner.
    $membership = $group->getMember($owner);

    // Helper to assign the admin if its coming from the front end.
    $this->vsiteRoleHelper->assignGroupAdminRoleToOwner($group);

    // Current owner roles.
    $owner_roles = $membership->getGroupContent()->get('group_roles')->getValue();

    // Converting 2 dimensional array into single dimensional array.
    $roles = [];
    foreach ($owner_roles as $role) {
      $roles[] = $role['target_id'];
    }
    $this->assertContains($group->getGroupType()->id() . '-administrator', $roles);
  }

}
