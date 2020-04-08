<?php

namespace Drupal\Tests\os_rest\ExistingSite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;
use Drupal\Tests\openscholar\Traits\CpTaxonomyTestTrait;

/**
 * Class EntitiesDeletedTest.
 *
 * @package Drupal\Tests\os_rest\ExistingSite
 * @group functional
 * @group os
 */
class EntitiesDeletedTest extends OsExistingSiteTestBase {

  use CpTaxonomyTestTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Vsite Context Manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager = $this->container->get('vsite.context_manager');
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Test when node is deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testNodeTableRowInserted() {
    $blog = $this->createNode([
      'type' => 'blog',
    ]);
    $this->group->addContent($blog, 'group_node:blog');

    $blog->delete();

    $rows = $this->getEntitiesDeletedRows($blog);
    $this->assertNotEmpty($rows);
    $this->assertEquals(serialize(['group' => $this->group->id()]), $rows[$blog->id()]->extra);
  }

  /**
   * Test when media is deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testMediaTableRowInserted() {
    $entity = $this->createMedia();
    $this->group->addContent($entity, 'group_entity:' . $entity->getEntityTypeId());

    $entity->delete();

    $rows = $this->getEntitiesDeletedRows($entity);
    $this->assertNotEmpty($rows);
    $this->assertEquals(serialize(['group' => $this->group->id()]), $rows[$entity->id()]->extra);
  }

  /**
   * Test when taxonomy term is deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testTaxonomyTermTableRowInserted() {
    $vid = $this->randomMachineName();
    $this->createGroupVocabulary($this->group, $vid);
    $entity = $this->createGroupTerm($this->group, $vid);

    $entity->delete();

    $rows = $this->getEntitiesDeletedRows($entity);
    $this->assertNotEmpty($rows);
    $this->assertEquals(serialize(['group' => $this->group->id()]), $rows[$entity->id()]->extra);
  }

  /**
   * Test when taxonomy vocabulary is deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testTaxonomyVocabularyTableRowInserted() {
    $vid = $this->randomMachineName();
    $this->createGroupVocabulary($this->group, $vid);

    $entity = Vocabulary::load($vid);
    $entity->delete();

    $rows = $this->getEntitiesDeletedRows($entity);
    $this->assertNotEmpty($rows);
    $this->assertEquals(serialize(['group' => $this->group->id()]), $rows[$entity->id()]->extra);
  }

  /**
   * Test when reference is deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testReferenceTableRowInserted() {
    $entity = $this->createReference();

    $entity->delete();

    $rows = $this->getEntitiesDeletedRows($entity);
    $this->assertNotEmpty($rows);
  }

  /**
   * Test when file is deleted.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFileTableRowInserted() {
    $entity = $this->createFile();

    $entity->delete();

    $rows = $this->getEntitiesDeletedRows($entity);
    $this->assertEmpty($rows);
  }

  /**
   * Get entities deleted rows by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Filtered entity.
   *
   * @return mixed
   *   Array of rows grouped with entity id.
   */
  protected function getEntitiesDeletedRows(?EntityInterface $entity) {
    $db = $this->container->get('database');
    $query = $db->select('entities_deleted', 'ed');
    $query->fields('ed');
    $query->condition('entity_type', $entity->getEntityTypeId());
    $query->condition('entity_id', $entity->id());
    $result = $query->execute();
    $rows = $result->fetchAllAssoc('entity_id');
    return $rows;
  }

}
