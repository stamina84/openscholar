<?php

namespace Drupal\Tests\os_blog\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class OsBlogDisqusTest.
 *
 * @group functional
 * @group analytics
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

    $group = $this->createGroup([
      'type' => 'personal',
      'path' => [
        'alias' => '/test-alias',
      ],
    ]);
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $group);
    $this->adminUser = $this->createUser([], '', TRUE);
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
  }

  /**
   * Test Setting form route.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBlogSettingsPath() {

    $this->drupalLogin($this->groupAdmin);
    $this->drupalGet('test-alias/cp/settings/global-settings/blog_setting');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test Settings form.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testBlogSettingsForm() {

    $this->drupalLogin($this->groupAdmin);
    $this->drupalGet('test-alias/cp/settings/global-settings/blog_setting');
    // Dummy disqus domain id.
    $edit = [
      'edit-comment-type-disqus-comments' => 'disqus_comments',
      'edit-disqus-shortname' => 'testing-disqus',
    ];
    $this->submitForm($edit, 'edit-submit');
    $this->assertSession()->fieldValueEquals('edit-disqus-shortname', 'testing-disqus');
  }

}
