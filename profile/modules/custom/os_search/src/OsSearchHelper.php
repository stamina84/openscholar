<?php

namespace Drupal\os_search;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Helper class for search.
 */
class OsSearchHelper {

  use StringTranslationTrait;

  /**
   * Entity storage for block content.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContent;

  /**
   * Entity storage for Search index entity.
   *
   * @var Drupal\Core\Entity\EntityStorageInterface
   */
  protected $indexStorage;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory) {
    $this->blockContent = $entity_type_manager->getStorage('block_content');
    $this->indexStorage = $entity_type_manager->getStorage('search_api_index');
    $this->configFactory = $config_factory;
  }

  /**
   * Function to get all fields with facet capabilities.
   *
   * General fields for OS Search Index.
   */
  public function getAllowedFacetIds(): array {
    $options = [];
    $enabled_facets = $this->configFactory->get('os.search.settings')->get('facet_widget') ?? [];
    $enabled_facets = array_filter($enabled_facets);
    $index_fields = $this->indexStorage->load('os_search_index')->getFieldsByDatasource(NULL);

    foreach (array_keys($enabled_facets) as $field_name) {
      $options[$field_name] = $index_fields[$field_name]->getLabel();
    }

    return $options;
  }

}
