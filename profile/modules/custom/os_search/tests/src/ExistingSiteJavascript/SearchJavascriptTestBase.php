<?php

namespace Drupal\Tests\os_search\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;
use Drupal\Tests\os_search\Traits\SearchTestTrait;
use Drupal\search_api\Entity\Index;

/**
 * Base class for Search (os-search) tests.
 */
abstract class SearchJavascriptTestBase extends OsExistingSiteJavascriptTestBase {
  use SearchTestTrait;

  /**
   * A search index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  protected $index = NULL;

  /**
   * Test group.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $anotherGroup;

  /**
   * Test group alias.
   *
   * @var string
   */
  protected $anotherGroupAlias;

  /**
   * Content differentiator.
   *
   * @var string
   */
  protected $differentiator = 'jstestsearch';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->index = Index::load('os_search_index');
    $this->anotherGroup = $this->createGroup();
    $this->anotherGroupAlias = $this->anotherGroup->get('path')->first()->getValue()['alias'];

    $this->generateContent($this->group, 5, $this->differentiator);
    $this->generateContent($this->anotherGroup, 4, $this->differentiator);

    $task_manager = $this->container->get('search_api.index_task_manager');
    $task_manager->addItemsAll($this->index);
    $this->index->indexItems();

    // Wait is required as Elastic Server
    // takes sometime to respond to queries.
    while ($this->getIndexQueryStatus($this->differentiator) <= 0) {
      $this->waitForSeconds();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    parent::tearDown();

    // Cleanup Object properties.
    $this->cleanUpProperties(self::class);
  }

}
