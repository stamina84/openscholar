<?php

namespace Drupal\Tests\os_presentations\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\views\Entity\View;

/**
 * PresentationTest.
 *
 * @group functional-javascript
 * @group presentations
 */
class PresentationTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Tests the node add presentation form.
   */
  public function testPresentationAddForm(): void {
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $this->drupalLogin($group_member);

    $title = $this->randomMachineName();
    $this->visitViaVsite('node/add/presentation', $this->group);

    $this->getSession()->getPage()->fillField('title[0][value]', $title);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);

    $presentation = $this->getNodeByTitle($title);

    // Check presentation is created.
    $this->assertNotEmpty($presentation);
    $this->assertSame($title, $presentation->getTitle());

    $this->markEntityForCleanup($presentation);
  }

  /**
   * Tests presentation listing.
   */
  public function testPresentationListing(): void {
    $web_assert = $this->assertSession();
    $presentation = $this->createNode([
      'type' => 'presentation',
    ]);
    $this->addGroupContent($presentation, $this->group);
    $presentation_unpublished = $this->createNode([
      'type' => 'presentation',
      'status' => 0,
    ]);
    $this->addGroupContent($presentation, $this->group);

    $this->visitViaVsite('presentations', $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($presentation->getTitle());
    $web_assert->pageTextNotContains($presentation_unpublished->getTitle());
  }

  /**
   * Tests presentation sticky listing.
   */
  public function testPresentationStickyListing(): void {
    $presentation1 = $this->createNode([
      'type' => 'presentation',
    ]);
    $this->addGroupContent($presentation1, $this->group);
    $presentation2 = $this->createNode([
      'type' => 'presentation',
    ]);
    $this->addGroupContent($presentation2, $this->group);
    $presentation_sticky = $this->createNode([
      'type' => 'presentation',
      'sticky' => 1,
    ]);
    $this->addGroupContent($presentation_sticky, $this->group);

    $view = View::load('presentations');
    $view_exec = $view->getExecutable();
    $view_exec->setDisplay('page_1');
    $view_exec->preExecute();
    $view_exec->execute();
    $build = $view_exec->render('page_1');
    $rows = $build['#rows'][0]['#rows'];

    // Get first views-row.
    $first_row = $rows[0]['#node'];
    $this->assertContains($presentation_sticky->getTitle(), $first_row->getTitle(), 'Sticky presentation is not the first.');
  }

}
