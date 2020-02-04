<?php

namespace Drupal\Tests\os_search\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\os_search\Traits\SearchTestTrait;

/**
 * Base class for Search (os-search) tests.
 */
abstract class SearchTestBase extends OsExistingSiteTestBase {
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
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->setUpSearch();

    $this->anotherGroup = $this->createGroup();
    $this->anotherGroupAlias = $this->anotherGroup->get('path')->first()->getValue()['alias'];

    $this->generateContent($this->group);
    $this->generateContent($this->anotherGroup, 4);

    $task_manager = $this->container->get('search_api.index_task_manager');
    $task_manager->addItemsAll($this->index);
    $this->index->indexItems();

    // Wait is required as Elastic Server
    // takes sometime to respond to queries.
    $this->getIndexQueryStatus();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Revert search_api_page to use original index.
    $this->originalPage->set('index', 'os_search_index');
    $this->originalPage->save();

    parent::tearDown();

    // Cleanup Object properties.
    $this->cleanUpProperties(self::class);
  }

}
