<?php

namespace Drupal\Tests\vsite\ExistingSite;

/**
 * Tests VsiteNonVsitePathsTest.
 *
 * @group functional
 * @group vsite
 */
class VsiteNonVsitePathsTest extends VsiteExistingSiteTestBase {

  /**
   * Tests Node links/paths are rendered correctly.
   */
  public function testNonVsiteGroupPathContent(): void {
    $group = $this->createGroup([
      'type' => 'personal',
      'path' => [
        'alias' => '/adminsite01',
      ],
    ]);

    $groupAdmin = $this->createUser();
    $this->addGroupAdmin($groupAdmin, $group);

    // Tests.
    $this->drupalLogin($groupAdmin);

    $blogTitle = 'Blog Test Node';
    $blogNode = $this->createNode([
      'type' => 'blog',
      'title' => $blogTitle,
    ]);

    $group->addContent($blogNode, 'group_node:blog');
    $this->drupalGet('/adminsite01/cp/content/browse/node');

    // Assert that correct link is rendered.
    $this->assertSession()->linkByHrefExists('/adminsite01/blog/blog-test-node');
    // Assert that it redirects to node page only.
    $this->getCurrentPage()->clickLink($blogTitle);
    $this->assertContains('/adminsite01/blog/blog-test-node', $this->getSession()->getCurrentUrl());

  }

}
