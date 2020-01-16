<?php

namespace Drupal\Tests\vsite\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Class VsiteContentRevisionsTest.
 *
 * @package Drupal\Tests\vsite\ExistingSite
 * @group functional-javascript
 * @group vsite
 */
class VsiteContentRevisionsTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Group admin.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->testUser = $this->createUser([], NULL, TRUE);
  }

  /**
   * Test that revert revisions is not breaking with any fault.
   */
  public function testContentRevisions() {
    // Setup.
    $blog = $this->createNode([
      'type' => 'blog',
      'title' => $this->randomMachineName(),
    ]);
    $this->group->addContent($blog, 'group_node:blog');

    $blog->set('title', $this->randomMachineName());
    $blog->setNewRevision();
    $blog->save();

    $this->drupalLogin($this->testUser);
    $this->visitViaVsite("node/{$blog->id()}/revisions", $this->group);
    $this->assertSession()->statusCodeEquals(200);

    $this->clickLink('Revert');
    $this->assertSession()->statusCodeEquals(200);

    $this->getSession()->getPage()->pressButton('Revert');
    $this->assertSession()->statusCodeEquals(200);
  }

}
