<?php

namespace Drupal\Tests\os_search\ExistingSite;

/**
 * OsSearchTest.
 *
 * @group kernel
 * @group os-search
 */
class OsSearchTest extends SearchTestBase {

  /**
   * Tests if search is working.
   */
  public function testSearch(): void {
    $query = $this->index->query();
    $query->keys('Earth');

    $results = $query->execute();
    $count = $results->getResultCount();
    $this->assertEquals($count, $this->earthDocsCount);

    $query = $this->index->query();
    $query->keys('Venus');

    $results = $query->execute();
    $count = $results->getResultCount();
    $this->assertEquals($count, $this->venusDocsCount);
  }

  /**
   * Tests if Post type filter is working.
   */
  public function testSearchPostType(): void {
    $query = $this->index->query();
    $query->keys('searchtest');

    $enabled_apps_list = ['news', 'bibcite_reference'];
    $query->addCondition('custom_search_bundle', $enabled_apps_list, 'IN');

    $results = $query->execute();
    $count = $results->getResultCount();

    $this->assertEquals($count, ($this->earthDocsCount + $this->jupiterDocsCount));
  }

  /**
   * Tests if group filter is working.
   */
  public function testSearchGroupType(): void {
    $query = $this->index->query();
    $query->keys('searchtest');
    $query->addCondition('custom_search_group', $this->group->id());

    $results = $query->execute();
    $count = $results->getResultCount();
    $this->assertEquals($count, 15);

    $query = $this->index->query();
    $query->keys('searchtest');
    $query->addCondition('custom_search_group', $this->anotherGroup->id());

    $results = $query->execute();
    $count = $results->getResultCount();
    $this->assertEquals($count, 12);
  }

}
