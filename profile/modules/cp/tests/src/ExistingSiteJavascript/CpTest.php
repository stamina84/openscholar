<?php

namespace Drupal\Tests\cp\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Control panel toolbar tests.
 *
 * @group functional-javascript
 * @group cp
 */
class CpTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Tests visibility of vsite actions by group admins.
   *
   * @covers ::cp_toolbar_alter
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testVisibility(): void {
    // Setup.
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);

    $this->drupalLogin($group_admin);

    // Tests.
    $this->visitViaVsite('', $this->group);

    $this->getSession()->getPage()->findLink('Control Panel')->click();
    $this->assertSession()->pageTextContains('Site Content');

    $vsite_content_link = $this->getSession()->getPage()->findLink('Site Content');
    $vsite_content_link->mouseOver();
    $this->assertSession()->pageTextContains('Add');

    $vsite_content_add_link = $this->getSession()->getPage()->findLink('Add');
    $vsite_content_add_link->mouseOver();
    $this->assertSession()->pageTextContains('Blog');
  }

  /**
   * Tests visibility of vsite content by anonymous.
   */
  public function testVisibilityCpContentAnonymous(): void {
    $web_assert = $this->assertSession();
    // Tests.
    $this->visitViaVsite('cp/content', $this->group);
    // Go to edit path.
    $page = $this->getCurrentPage();
    $login_link = $page->findLink('Admin Login');
    $this->assertNotNull($login_link);
    $web_assert->statusCodeEquals(403);
  }

  /**
   * Tests whether the cp items are in sync with group member role.
   *
   * @covers ::cp_group_content_update
   */
  public function testGroupMemberRoleSync(): void {
    // Setup.
    $test_user = $this->createUser();
    $this->group->addMember($test_user);

    // Negative test.
    $this->drupalLogin($test_user);

    $this->visitViaVsite('', $this->group);
    $this->assertSession()->responseNotContains("{$this->groupAlias}/cp/appearance");

    $this->drupalLogout();

    // Do changes.
    /** @var \Drupal\group\GroupMembership $group_membership */
    $group_membership = $this->group->getMember($test_user);
    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = $group_membership->getGroupContent();
    $group_content->set('group_roles', [
      'target_id' => 'personal-administrator',
    ])->save();

    // Positive test.
    $this->drupalLogin($test_user);

    $this->visitViaVsite('', $this->group);
    $this->assertSession()->responseContains("{$this->groupAlias}/cp/appearance");
  }

}
