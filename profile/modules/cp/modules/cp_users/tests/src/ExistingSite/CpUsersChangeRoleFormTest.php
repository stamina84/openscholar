<?php

namespace Drupal\Tests\cp_users\ExistingSite;

use Drupal\group\Entity\GroupRole;

/**
 * ChangeRoleForm test.
 *
 * @group functional
 * @group cp
 */
class CpUsersChangeRoleFormTest extends CpUsersExistingSiteTestBase {

  /**
   * Cp Roles helper service.
   *
   * @var \Drupal\cp_users\CpRolesHelperInterface
   */
  protected $cpRolesHelper;

  /**
   * Group member.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $member;

  /**
   * Group administrator.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp() {
    parent::setUp();

    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->createRoleForGroup($this->group, [
      'id' => 'cprolechange',
    ]);
    $this->member = $this->createUser();
    $this->group->addMember($this->member);

    $this->drupalLogin($this->groupAdmin);

    $this->cpRolesHelper = $this->container->get('cp_users.cp_roles_helper');

  }

  /**
   * Tests change role functionality.
   *
   * @covers \Drupal\cp_users\Form\ChangeRoleForm
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function test(): void {

    $this->visit("/{$this->group->get('path')->getValue()[0]['alias']}/cp/users/change-role/{$this->member->id()}");

    $this->assertSession()->statusCodeEquals(200);

    // Test Non Configurable roles do not appear.
    $non_configurable_roles = $this->cpRolesHelper->getNonConfigurableGroupRoles($this->group);
    foreach ($non_configurable_roles as $role) {
      $this->assertSession()->elementNotExists('css', '#edit-roles-' . $role);
    }

    $this->assertTrue($this->getSession()->getPage()->find('css', '[value="personal-member"]')->isChecked());

    $this->drupalPostForm(NULL, [
      'roles' => "personal-{$this->group->id()}_cprolechange",
    ], 'Save');

    /** @var \Drupal\group\GroupMembership $group_membership */
    $group_membership = $this->group->getMember($this->member);
    $updated_roles = $group_membership->getRoles();
    $this->assertInstanceOf(GroupRole::class, $updated_roles["personal-{$this->group->id()}_cprolechange"]);
  }

  /**
   * Tests whether the cp items are in sync with group role.
   *
   * @covers ::cp_users_preprocess_menu
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testCpItemsSync(): void {
    // Negative test.
    $this->drupalLogin($this->member);

    $this->visitViaVsite('', $this->group);
    $this->assertSession()->responseNotContains("{$this->groupAlias}/cp/appearance");

    $this->drupalLogout();

    // Do changes.
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite("cp/users/change-role/{$this->member->id()}", $this->group);
    $this->drupalPostForm(NULL, [
      'roles' => 'personal-administrator',
    ], 'Save');

    // Positive test.
    $this->drupalLogin($this->member);

    $this->visitViaVsite('', $this->group);
    $this->assertSession()->responseContains("{$this->groupAlias}/cp/appearance");
  }

}
