<?php

namespace Drupal\Tests\os_blog\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\views\Entity\View;

/**
 * BlogTest.
 *
 * @group functional-javascript
 * @group blog
 */
class BlogTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Group administrator.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupAdmin;

  /**
   * Group member.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $groupMember;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
    $this->configFactory = $this->container->get('config.factory');
    $this->vsiteContextManager->activateVsite($this->group);
    $this->configFactory->getEditable('os_blog.settings')
      ->set('comment_type', 'disqus_comments')
      ->set('disqus_shortname', 'testing-disqus')
      ->save();
    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
    $this->groupMember = $this->CreateUser();
    $this->addGroupEnhancedMember($this->groupMember, $this->group);
  }

  /**
   * Tests the node add blog form.
   */
  public function testBlogAddForm(): void {
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $this->drupalLogin($group_member);

    $title = $this->randomMachineName();
    $this->visitViaVsite('node/add/blog', $this->group);

    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);

    $blog = $this->getNodeByTitle($title);

    // Check blog is created.
    $this->assertNotEmpty($blog);
    $this->assertSame($title, $blog->getTitle());

    $this->markEntityForCleanup($blog);
  }

  /**
   * Tests blog listing.
   */
  public function testBlogListing(): void {
    $web_assert = $this->assertSession();
    $blog = $this->createNode([
      'type' => 'blog',
    ]);
    $this->addGroupContent($blog, $this->group);
    $blog_unpublished = $this->createNode([
      'type' => 'blog',
      'status' => 0,
    ]);
    $this->addGroupContent($blog, $this->group);

    $this->visitViaVsite('blog', $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($blog->getTitle());
    $web_assert->pageTextNotContains($blog_unpublished->getTitle());
  }

  /**
   * Tests blog sticky listing.
   */
  public function testBlogStickyListing(): void {
    $blog1 = $this->createNode([
      'type' => 'blog',
    ]);
    $this->addGroupContent($blog1, $this->group);
    $blog2 = $this->createNode([
      'type' => 'blog',
    ]);
    $this->addGroupContent($blog2, $this->group);
    $blog_sticky = $this->createNode([
      'type' => 'blog',
      'sticky' => 1,
    ]);
    $this->addGroupContent($blog_sticky, $this->group);

    $view = View::load('blog');
    $view_exec = $view->getExecutable();
    $view_exec->setDisplay('page_1');
    $view_exec->preExecute();
    $view_exec->execute();
    $build = $view_exec->render('page_1');
    $rows = $build['#rows'][0]['#rows'];

    // Get first views-row.
    $first_row = $rows[0]['#node'];
    $this->assertContains($blog_sticky->getTitle(), $first_row->getTitle(), 'Sticky blog is not the first.');
  }

  /**
   * Tests blog archives listing.
   */
  public function testBlogArchivesListing(): void {
    $web_assert = $this->assertSession();
    $blog_2010 = $this->createNode([
      'type' => 'blog',
      'created' => strtotime('2010-01-01 20:00:00'),
    ]);
    $this->addGroupContent($blog_2010, $this->group);
    $blog_2011_02 = $this->createNode([
      'type' => 'blog',
      'created' => strtotime('2011-02-01 20:00:00'),
    ]);
    $this->addGroupContent($blog_2011_02, $this->group);
    $blog_2011_03 = $this->createNode([
      'type' => 'blog',
      'created' => strtotime('2011-03-01 20:00:00'),
    ]);
    $this->addGroupContent($blog_2011_03, $this->group);
    $blog = $this->createNode([
      'type' => 'blog',
    ]);
    $this->addGroupContent($blog, $this->group);

    $this->visitViaVsite('blog/archives', $this->group);
    $web_assert->statusCodeEquals(200);
    // Assert non-filtered page.
    $web_assert->pageTextContains($blog_2010->getTitle());
    $web_assert->pageTextContains($blog_2011_02->getTitle());
    $web_assert->pageTextContains($blog_2011_03->getTitle());
    $web_assert->pageTextContains($blog->getTitle());

    $this->visitViaVsite('blog/archives/2010', $this->group);
    $web_assert->statusCodeEquals(200);
    // Assert year filtered page.
    $web_assert->pageTextContains($blog_2010->getTitle());
    $web_assert->pageTextNotContains($blog_2011_02->getTitle());
    $web_assert->pageTextNotContains($blog_2011_03->getTitle());
    $web_assert->pageTextNotContains($blog->getTitle());

    $this->visitViaVsite('blog/archives/2011/02', $this->group);
    $web_assert->statusCodeEquals(200);
    // Assert month filtered page.
    $web_assert->pageTextContains($blog_2011_02->getTitle());
    $web_assert->pageTextNotContains($blog_2010->getTitle());
    $web_assert->pageTextNotContains($blog_2011_03->getTitle());
    $web_assert->pageTextNotContains($blog->getTitle());
  }

  /**
   * Test if vsite Disqus domain show on blog page.
   */
  public function testDisqusOnBlogPage() {
    $this->drupalLogin($this->groupMember);
    // Creating blog type node.
    $title = 'Disqus comments testing';
    $this->visitViaVsite('node/add/blog', $this->group);
    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);
    // Asserting disqus comments iframe added to blog.
    $this->assertSession()->waitForElementVisible('css', '.field--name-field-disqus-comments');
    $this->assertSession()->waitForElementVisible('css', 'iframe');
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'src', 'testing-disqus');
  }

  /**
   * Test if vsite Disqus domain show on blog page.
   */
  public function testNoDisqusOnBlogPage() {
    $this->configFactory->getEditable('os_blog.settings')
      ->set('comment_type', 'no_comments')
      ->save();
    $this->drupalLogin($this->groupMember);
    // Creating blog type node.
    $title = 'Without Disqus comments';
    $this->visitViaVsite('node/add/blog', $this->group);
    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);
    // Asserting no disqus comments are added tp blog page.
    $this->assertSession()->assertNoElementAfterWait('css', '.field--name-field-disqus-comments');
  }

}
