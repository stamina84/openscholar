<?php

namespace Drupal\Tests\os_news\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\views\Entity\View;

/**
 * NewsTest.
 *
 * @group functional-javascript
 * @group news
 */
class NewsTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Tests the node add news form.
   */
  public function testNewsAddForm(): void {
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $this->drupalLogin($group_member);

    $title = $this->randomMachineName();
    $this->visitViaVsite('node/add/news', $this->group);

    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->fillField('field_date[0][value][date]', date("Y-m-d"));
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);

    $news = $this->getNodeByTitle($title);

    // Check news is created.
    $this->assertNotEmpty($news);
    $this->assertSame($title, $news->getTitle());

    $this->markEntityForCleanup($news);
  }

  /**
   * Tests news listing.
   */
  public function testNewsListing(): void {
    $web_assert = $this->assertSession();
    $news = $this->createNode([
      'type' => 'news',
    ]);
    $this->addGroupContent($news, $this->group);
    $news_unpublished = $this->createNode([
      'type' => 'news',
      'status' => 0,
    ]);
    $this->addGroupContent($news, $this->group);

    $this->visitViaVsite('news', $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($news->getTitle());
    $web_assert->pageTextNotContains($news_unpublished->getTitle());
  }

  /**
   * Tests news sticky listing.
   */
  public function testNewsStickyListing(): void {
    $news1 = $this->createNode([
      'type' => 'news',
    ]);
    $this->addGroupContent($news1, $this->group);
    $news2 = $this->createNode([
      'type' => 'news',
    ]);
    $this->addGroupContent($news2, $this->group);
    $news_sticky = $this->createNode([
      'type' => 'news',
      'sticky' => 1,
    ]);
    $this->addGroupContent($news_sticky, $this->group);

    $view = View::load('news');
    $view_exec = $view->getExecutable();
    $view_exec->setDisplay('page_1');
    $view_exec->preExecute();
    $view_exec->execute();
    $build = $view_exec->render('page_1');
    $rows = $build['#rows'][0]['#rows'];

    // Get first views-row.
    $first_row = $rows[0]['#node'];
    $this->assertContains($news_sticky->getTitle(), $first_row->getTitle(), 'Sticky news is not the first.');
  }

  /**
   * Tests news archives listing.
   */
  public function testNewsArchivesListing(): void {
    $web_assert = $this->assertSession();
    $news_2010 = $this->createNode([
      'type' => 'news',
      'field_date' => [
        'value' => '2010-01-01',
      ],
    ]);
    $this->addGroupContent($news_2010, $this->group);
    $news_2011_02 = $this->createNode([
      'type' => 'news',
      'field_date' => [
        'value' => '2011-02-01',
      ],
    ]);
    $this->addGroupContent($news_2011_02, $this->group);
    $news_2011_03 = $this->createNode([
      'type' => 'news',
      'field_date' => [
        'value' => '2011-03-01',
      ],
    ]);
    $this->addGroupContent($news_2011_03, $this->group);
    $news = $this->createNode([
      'type' => 'news',
    ]);
    $this->addGroupContent($news, $this->group);

    $this->visitViaVsite('news/archive', $this->group);
    $web_assert->statusCodeEquals(200);
    // Assert non-filtered page.
    $web_assert->pageTextContains($news_2010->getTitle());
    $web_assert->pageTextContains($news_2011_02->getTitle());
    $web_assert->pageTextContains($news_2011_03->getTitle());
    $web_assert->pageTextContains($news->getTitle());

    $this->visitViaVsite('news/archive/2010', $this->group);
    $web_assert->statusCodeEquals(200);
    // Assert year filtered page.
    $web_assert->pageTextContains($news_2010->getTitle());
    $web_assert->pageTextNotContains($news_2011_02->getTitle());
    $web_assert->pageTextNotContains($news_2011_03->getTitle());
    $web_assert->pageTextNotContains($news->getTitle());

    $this->visitViaVsite('news/archive/2011/02', $this->group);
    $web_assert->statusCodeEquals(200);
    // Assert month filtered page.
    $web_assert->pageTextContains($news_2011_02->getTitle());
    $web_assert->pageTextNotContains($news_2010->getTitle());
    $web_assert->pageTextNotContains($news_2011_03->getTitle());
    $web_assert->pageTextNotContains($news->getTitle());
  }

}
