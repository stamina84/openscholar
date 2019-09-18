<?php

namespace Drupal\Tests\os_widgets\ExistingSiteJavascript;

use Drupal\block_content\BlockContentInterface;
use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests os_widgets module.
 *
 * @group functional
 * @group widgets
 */
class WidgetsPagerTest extends OsExistingSiteJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $group_admin = $this->createUser();
    $this->addGroupAdmin($group_admin, $this->group);
    $this->drupalLogin($group_admin);
  }

  /**
   * Tests os_widgets publication author pager.
   */
  public function testPublicationAuthorWidgetPager() {
    $web_assert = $this->assertSession();
    // Create 21 publication elements, 3 pages.
    for ($i = 1; $i < 22; $i++) {
      $contributor = $this->createContributor();
      $reference = $this->createReference([
        'author' => [
          'target_id' => $contributor->id(),
          'category' => 'primary',
          'role' => 'author',
        ],
      ]);
      $this->group->addContent($reference, 'group_entity:bibcite_reference');
    }

    $block_content_title = $this->randomMachineName();
    $block_content = $this->createBlockContent([
      'type' => 'publication_authors',
      'field_widget_title' => $block_content_title,
    ]);

    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content, 'sidebar_second');

    $this->visitViaVsite("", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($block_content_title);
    $web_assert->pageTextContains('1 of 3');
    $next_page_element = $this->getBlockNextLink($block_content);
    $next_page_element->click();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('2 of 3');
    $prev_page_element = $this->getBlockPrevLink($block_content);
    $this->assertNotEmpty($prev_page_element);
  }

  /**
   * Tests os_widgets publication types pager.
   */
  public function testPublicationTypesWidgetPager() {
    $web_assert = $this->assertSession();
    $reference_bundles = $this->container->get('entity_type.bundle.info')->getBundleInfo('bibcite_reference');
    // Create publications, with limit 21.
    $i = 0;
    foreach (array_keys($reference_bundles) as $reference_bundle) {
      $reference = $this->createReference([
        'type' => $reference_bundle,
      ]);
      $this->group->addContent($reference, 'group_entity:bibcite_reference');
      if (++$i == 21) {
        break;
      }
    }

    $block_content_title = $this->randomMachineName();
    $block_content = $this->createBlockContent([
      'type' => 'publication_types',
      'field_types_whitelist' => array_keys($reference_bundles),
      'field_widget_title' => $block_content_title,
    ]);

    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content, 'sidebar_second');

    $this->visitViaVsite("", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($block_content_title);
    $web_assert->pageTextContains('1 of 3');
    $next_page_element = $this->getBlockNextLink($block_content);
    $next_page_element->click();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('2 of 3');
    $prev_page_element = $this->getBlockPrevLink($block_content);
    $this->assertNotEmpty($prev_page_element);
  }

  /**
   * Tests os_widgets publication years pager.
   */
  public function testPublicationYearsWidgetPager() {
    $web_assert = $this->assertSession();
    // Create 21 publication elements, 3 pages.
    for ($i = 1; $i < 22; $i++) {
      $reference = $this->createReference([
        'bibcite_year' => [
          'value' => $i + 1000,
        ],
      ]);
      $this->group->addContent($reference, 'group_entity:bibcite_reference');
    }

    $block_content_title = $this->randomMachineName();
    $block_content = $this->createBlockContent([
      'type' => 'publication_years',
      'field_widget_title' => $block_content_title,
    ]);

    $this->group->addContent($block_content, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content, 'sidebar_second');
    $this->visitViaVsite("", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($block_content_title);
    $web_assert->pageTextContains('1 of 3');
    $next_page_element = $this->getBlockNextLink($block_content);
    $next_page_element->click();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('2 of 3');
    $prev_page_element = $this->getBlockPrevLink($block_content);
    $this->assertNotEmpty($prev_page_element);
  }

  /**
   * Tests os_widgets publication widgets on same page and test pager.
   *
   * Testing pagers not correlation to each other.
   */
  public function testPublicationThreeWidgetsInSamePagePager() {
    $web_assert = $this->assertSession();
    $reference_bundles = $this->container->get('entity_type.bundle.info')->getBundleInfo('bibcite_reference');
    $reference_bundles_keys = array_keys($reference_bundles);
    // Create 21 publication elements, 3 pages.
    for ($i = 1; $i < 22; $i++) {
      $contributor = $this->createContributor();
      $reference_bundle = array_shift($reference_bundles_keys);
      $reference = $this->createReference([
        'type' => $reference_bundle,
        'author' => [
          'target_id' => $contributor->id(),
          'category' => 'primary',
          'role' => 'author',
        ],
        'bibcite_year' => [
          'value' => $i + 1000,
        ],
      ]);
      $this->group->addContent($reference, 'group_entity:bibcite_reference');
    }

    $types_title = $this->randomMachineName();
    $block_content_types = $this->createBlockContent([
      'type' => 'publication_types',
      'field_types_whitelist' => array_keys($reference_bundles),
      'field_widget_title' => $types_title,
    ]);
    $this->group->addContent($block_content_types, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content_types, 'sidebar_second');

    $years_title = $this->randomMachineName();
    $block_content_years = $this->createBlockContent([
      'type' => 'publication_years',
      'field_widget_title' => $years_title,
    ]);
    $this->group->addContent($block_content_years, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content_years, 'sidebar_second');

    $authors_title = $this->randomMachineName();
    $block_content_authors = $this->createBlockContent([
      'type' => 'publication_authors',
      'field_widget_title' => $authors_title,
    ]);
    $this->group->addContent($block_content_authors, 'group_entity:block_content');
    $this->placeBlockContentToRegion($block_content_authors, 'sidebar_second');

    $this->visitViaVsite("", $this->group);
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains($types_title);
    $web_assert->pageTextContains($years_title);
    $web_assert->pageTextContains($authors_title);

    // Test types pager.
    $next_page_element = $this->getBlockNextLink($block_content_types);
    $next_page_element->click();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContainsOnce('2 of 3');
    $prev_page_element = $this->getBlockPrevLink($block_content_types);
    $this->assertNotEmpty($prev_page_element);

    // Test authors pager.
    $this->visitViaVsite("", $this->group);
    $next_page_element = $this->getBlockNextLink($block_content_authors);
    $next_page_element->click();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContainsOnce('2 of 3');
    $prev_page_element = $this->getBlockPrevLink($block_content_authors);
    $this->assertNotEmpty($prev_page_element);

    // Test years pager.
    $this->visitViaVsite("", $this->group);
    $next_page_element = $this->getBlockNextLink($block_content_years);
    $next_page_element->click();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContainsOnce('2 of 3');
    $prev_page_element = $this->getBlockPrevLink($block_content_years);
    $this->assertNotEmpty($prev_page_element);
  }

  /**
   * Get given block's next link.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   Block content where need to search.
   *
   * @return \Behat\Mink\Element\NodeElement|mixed|null
   *   Found element or null.
   */
  protected function getBlockNextLink(BlockContentInterface $block_content) {
    $page = $this->getCurrentPage();
    $block_section_element = $page->findById('block-block-content' . $block_content->uuid());
    return $block_section_element->find('xpath', '//a[@title="Go to next page"]');
  }

  /**
   * Get given block's prev link.
   *
   * @param \Drupal\block_content\BlockContentInterface $block_content
   *   Block content where need to search.
   *
   * @return \Behat\Mink\Element\NodeElement|mixed|null
   *   Found element or null.
   */
  protected function getBlockPrevLink(BlockContentInterface $block_content) {
    $page = $this->getCurrentPage();
    $block_section_element = $page->findById('block-block-content' . $block_content->uuid());
    return $block_section_element->find('xpath', '//a[@title="Go to previous page"]');
  }

}
