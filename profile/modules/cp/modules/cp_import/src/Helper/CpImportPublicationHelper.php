<?php

namespace Drupal\cp_import\Helper;

use Drupal\bibcite\Plugin\BibciteFormatManager;
use Drupal\bibcite_entity\Entity\Reference;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\vsite\Plugin\VsiteContextManager;
use Symfony\Component\Serializer\Serializer;

/**
 * Class CpImportPublicationHelper.
 *
 * @package Drupal\cp_import\Helper
 */
class CpImportPublicationHelper extends CpImportHelperBase {

  /**
   * BibciteFormat Manager service.
   *
   * @var \Drupal\bibcite\Plugin\BibciteFormatManager
   */
  protected $formatManager;

  /**
   * Serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * CpImportPublicationHelper constructor.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManager $vsiteContextManager
   *   VsiteContextManager instance.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   EntityTypeManager instance.
   * @param \Drupal\bibcite\Plugin\BibciteFormatManager $bibciteFormatManager
   *   BibciteManager instance.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   Serializer instance.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config Factory instance.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger channel factory instance.
   */
  public function __construct(VsiteContextManager $vsiteContextManager, EntityTypeManager $entityTypeManager, BibciteFormatManager $bibciteFormatManager, Serializer $serializer, ConfigFactory $configFactory, LoggerChannelFactory $loggerChannelFactory) {
    parent::__construct($vsiteContextManager, $entityTypeManager);
    $this->formatManager = $bibciteFormatManager;
    $this->serializer = $serializer;
    $this->configFactory = $configFactory;
    $this->logger = $loggerChannelFactory;
  }

  /**
   * Denormalize the entry and save the entity.
   *
   * @param array $entry
   *   Single entry from the import file.
   * @param string $formatId
   *   Format id.
   *
   * @return array
   *   For use in batch contexts.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function savePublicationEntity(array $entry, $formatId): array {
    $result = [];
    $format = $this->formatManager->createInstance($formatId);
    $config = $this->configFactory->get('bibcite_import.settings');
    $denormalize_context = [
      'contributor_deduplication' => $config->get('settings.contributor_deduplication'),
      'keyword_deduplication' => $config->get('settings.keyword_deduplication'),
    ];

    // To handle special cases when year is a coded string instead of a number.
    $yearMapping = $this->configFactory->get('os_publications.settings')->get('publications_years_text');
    if (isset($entry['year']) && is_string($entry['year'])) {
      foreach ($yearMapping as $code => $text) {
        if (strtolower(str_replace(' ', '', $entry['year'])) === strtolower(str_replace(' ', '', $text))) {
          $entry['year'] = $code;
        }
      }
    }

    /** @var \Drupal\bibcite_entity\Entity\Reference $entity */
    try {
      $entity = $this->serializer->denormalize($entry, Reference::class, $format->getPluginId(), $denormalize_context);
    }
    catch (\UnexpectedValueException $e) {
      // Skip import for this row.
    }

    if (!empty($entity)) {
      try {
        if ($entity->save()) {
          $result['success'] = $entity->id() . ' : ' . $entity->label();
          // Map Title and Abstract fields.
          $this->mapPublicationHtmlFields($entity);
          // Add newly saved entity to the group in context.
          $this->addContentToVsite($entity->id(), 'group_entity:bibcite_reference', $entity->getEntityTypeId());
        }
      }
      catch (\Exception $e) {
        $message = [
          $this->t('Entity can not be saved.'),
          $this->t('Label: @label', ['@label' => $entity->label()]),
          '<pre>',
          $e->getMessage(),
          '</pre>',
        ];
        $this->logger->get('bibcite_import')->error(implode("\n", $message));
        $result['errors'] = $entity->label();
      }
      $result['message'] = $entity->label();
    }
    return $result;
  }

  /**
   * Map fields and save entity.
   *
   * @param \Drupal\bibcite_entity\Entity\Reference $entity
   *   Bibcite Reference entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function mapPublicationHtmlFields(Reference $entity): void {
    $entity->html_title->value = $entity->title->value;
    $entity->html_abstract->value = $entity->bibcite_abst_e->value;
    // Important for abstract content to recognize html content.
    $entity->html_abstract->format = 'filtered_html';
    $entity->save();
  }

}
