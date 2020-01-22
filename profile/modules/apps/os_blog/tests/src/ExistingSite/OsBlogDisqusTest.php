<?php

namespace Drupal\Tests\os_blog\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class OsBlogDisqusTest.
 *
 * @group functional
 * @group os
 *
 * @package Drupal\Tests\os_blog\ExistingSite
 */
class OsBlogDisqusTest extends OsExistingSiteTestBase {

  /**
   * Group administrator.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->groupMember = $this->createUser();
    $this->addGroupEnhancedMember($this->groupMember, $this->group);
  }

  /**
   * Test Settings form.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBlogSettingsForm() {
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite('cp/settings/apps-settings/blog_setting', $this->group);
    $this->assertSession()->statusCodeEquals(200);
    // Dummy disqus domain id.
    $edit = [
      'edit-comment-type-disqus-comments' => 'disqus_comments',
      'edit-disqus-shortname' => 'testing-disqus',
    ];
    $this->submitForm($edit, 'edit-submit');
    $this->assertSession()->fieldValueEquals('edit-disqus-shortname', 'testing-disqus');
  }

  /**
   * Testing Blog comment settings after disabling Blog app.
   */
  public function testBlogAppAccess() {
    $this->drupalLogin($this->groupAdmin);
    $this->visitViaVsite('cp/settings/app-access', $this->group);
    $this->getSession()->getPage()->find('css', 'input[type=checkbox][name="enabled[blog][disable]"]')->check();
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->visitViaVsite('cp/settings/apps-settings/blog_setting', $this->group);
    $this->assertSession()->statusCodeEquals(403);
    // Checking again after enabling blog app.
    $this->visitViaVsite('cp/settings/app-access', $this->group);
    $this->getSession()->getPage()->find('css', 'input[type=checkbox][name="disabled[blog][enable]"]')->check();
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->visitViaVsite('cp/settings/apps-settings/blog_setting', $this->group);
    $this->assertSession()->statusCodeEquals(200);
  }

}