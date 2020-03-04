<?php

namespace Drupal\Tests\os_search\ExistingSiteJavascript;

/**
 * OsSearchJsTest.
 *
 * @group functional-javascript
 * @group os-search
 */
class OsSearchJsTest extends SearchJavascriptTestBase {

  /**
   * Tests if search is working.
   */
  public function testSearch(): void {
    $web_assert = $this->assertSession();

    $this->visit('/search');
    $web_assert->statusCodeEquals(200);
    $page = $this->getCurrentPage();
    $page->fillField('keys', 'jstestsearch');

    // Find and press Search button.
    $preview_button = $page->findButton('Search');
    $preview_button->press();
    $web_assert->statusCodeEquals(200);

    // Assert that page contains results.
    $page = $this->getCurrentPage();
    $this->assertContains('27 results found', $page->getHtml());
  }

  /**
   * Tests if search with group is working.
   */
  public function testSearchByGroup(): void {
    $web_assert = $this->assertSession();

    $this->visitViaVsite('search', $this->group);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $this->assertContains('15 results found', $page->getHtml());

    // Test Search widgets.
    $right_sidebar = $this->getSession()->getPage()->find('css', 'div.region-sidebar-second');
    $this->assertContains('Current search', $right_sidebar->getHtml());
    $this->assertContains('Sort by', $right_sidebar->getHtml());
    $this->assertContains('Filter By Post Date', $right_sidebar->getHtml());
    $this->assertContains('Filter By Post Type', $right_sidebar->getHtml());
    $this->assertContains('Filter By Other Sites', $right_sidebar->getHtml());
    $this->assertContains('Filter By Taxonomy', $right_sidebar->getHtml());

    $this->visitViaVsite('search', $this->anotherGroup);
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $this->assertContains('12 results found', $page->getHtml());
  }

  /**
   * Tests if search with post type is working.
   */
  public function testSearchByType(): void {
    $post_type = 'f[0]=custom_search_bundle:blog';

    $web_assert = $this->assertSession();
    $this->visit("/search/jstestsearch?{$post_type}");
    $web_assert->statusCodeEquals(200);

    $page = $this->getCurrentPage();
    $this->assertContains('Current search', $page->getHtml());
    $this->assertSession()->linkByHrefExists('/search/jstestsearch?');
    $this->assertSession()->linkExists('(-)');
    $this->assertContains('9 results found', $page->getHtml());
    $this->assertContains('blog jstestsearch', $page->getHtml());
    $this->assertNotContains('news jstestsearch', $page->getHtml());
    $this->assertNotContains('reference Jstestsearch', $page->getHtml());

    $this->visitViaVsite("search/jstestsearch?{$post_type}", $this->group);
    $this->assertContains('5 results found', $page->getHtml());
    $this->assertContains("Venus blog jstestsearch Group{$this->group->id()} 1", $page->getHtml());
    $this->assertNotContains("Earth news jstestsearch Group{$this->group->id()} 1", $page->getHtml());
    $this->assertNotContains("Jupiter Reference Jstestsearch Group{$this->group->id()} 1", $page->getHtml());

    $year = (int) date('Y', time());
    $month = (string) date('M Y', time());

    $post_type = "f[0]=custom_search_bundle:bibcite_reference&f[1]=custom_date:year-{$year}";
    $this->visitViaVsite("search/Jstestsearch?{$post_type}", $this->anotherGroup);
    $remove_filter = "{$this->anotherGroupAlias}/search/Jstestsearch?f%5B1%5D=custom_date%3Ayear-{$year}";

    $page = $this->getCurrentPage();
    $this->assertSession()->linkExists("{$month} (4)");
    $this->assertSession()->linkByHrefExists($remove_filter);
    $this->assertContains('Search found 4 items', $page->getHtml());
    $this->assertContains('4 results found', $page->getHtml());
    $this->assertContains("Jupiter Reference Jstestsearch Group{$this->anotherGroup->id()}", $page->getHtml());
    $this->assertNotContains("Earth News jstestsearch", $page->getHtml());
    $this->assertNotContains("Venus Blog jstestsearch", $page->getHtml());
  }

  /**
   * Tests if search sort is working.
   */
  public function testSearchSortsAsc(): void {
    $sort_type = 'sort=title&dir=ASC';

    $web_assert = $this->assertSession();
    $this->visit("/search/Jstestsearch?{$sort_type}");
    $web_assert->statusCodeEquals(200);

    $links = $this->getCurrentPage()
      ->findAll('named', [
        'link',
        'Earth news Searchjstest',
      ]);

    $i = 1;
    $group_id = $this->group->id();
    foreach ($links as $link) {
      if ($i > 5) {
        $i = 1;
        $group_id = $this->anotherGroup->id();
      }

      $this->assertEquals("Earth news Jstestsearch Group{$group_id} {$i}", $link->getText());
      $i++;
    }
  }

  /**
   * Tests term search facets.
   */
  public function testTermFacets() {
    /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager */
    $vsite_context_manager = $this->container->get('vsite.context_manager');
    // Activate vsite and create a vocabulary.
    $vsite_context_manager->activateVsite($this->group);
    $vocabulary = $this->createVocabulary();
    $config = $this->container->get('config.factory');
    $config_vocab = $config->getEditable('taxonomy.vocabulary.' . $vocabulary->id());
    $config_vocab->set('allowed_vocabulary_reference_types', ['node:blog'])->save(TRUE);
    $web_assert = $this->assertSession();

    $term1 = $this->createTerm($vocabulary, ['name' => 'Search Test1']);
    $term2 = $this->createTerm($vocabulary, ['name' => 'Search Test2']);
    $term3 = $this->createTerm($vocabulary, ['name' => 'Search Test3']);

    $this->group->addContent($term1, 'group_entity:taxonomy_term');
    $this->group->addContent($term2, 'group_entity:taxonomy_term');
    $this->group->addContent($term3, 'group_entity:taxonomy_term');

    $blog = $this->createNode([
      'type' => 'blog',
      'status' => 1,
      'field_taxonomy_terms' => [
        $term1->id(),
        $term2->id(),
        $term3->id(),
      ],
    ]);
    $this->group->addContent($blog, 'group_node:blog');

    $blog = $this->createNode([
      'type' => 'blog',
      'status' => 1,
      'field_taxonomy_terms' => [
        $term2->id(),
      ],
    ]);
    $this->group->addContent($blog, 'group_node:blog');

    $task_manager = $this->container->get('search_api.index_task_manager');
    $task_manager->addItemsAll($this->index);
    $this->index->indexItems();

    // Wait is required as Elastic Server
    // takes sometime to respond to queries.
    $this->getIndexQueryStatus($this->differentiator, 1, 15);

    $this->visitViaVsite("search", $this->group);
    $web_assert->statusCodeEquals(200);
    $this->assertContains('17 results found', $this->getCurrentPage()->getHtml());

    $this->visitViaVsite("search?f[0]=custom_taxonomy:{$term2->id()}", $this->group);
    $web_assert->statusCodeEquals(200);
    $filter_by_taxonomy_links = $this->getSession()->getPage()->findAll('css', '#block-globalfilterbytaxonomy ul li a');

    $this->assertEquals("(-)", $filter_by_taxonomy_links[0]->getText());
    $this->assertEquals("Search Test1 (1)", $filter_by_taxonomy_links[1]->getText());
    $this->assertEquals("Search Test3 (1)", $filter_by_taxonomy_links[2]->getText());

    $filter_by_taxonomy_items = $this->getSession()->getPage()->findAll('css', '#block-globalfilterbytaxonomy ul li');
    $this->assertEquals("(-) Search Test2", $filter_by_taxonomy_items[0]->getText());
  }

}
