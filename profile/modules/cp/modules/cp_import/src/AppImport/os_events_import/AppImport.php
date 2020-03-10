<?php

namespace Drupal\cp_import\AppImport\os_events_import;

use DateTime;
use Drupal\Component\Utility\UrlHelper;
use Drupal\cp_import\AppImport\Base;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;

/**
 * Class Events AppImport.
 *
 * @package Drupal\cp_import\AppImport\os_events_import
 */
class AppImport extends Base {

  /**
   * Bundle type.
   *
   * @var string
   */
  protected $type = 'events';

  /**
   * Group plugin id.
   *
   * @var string
   */
  protected $groupPluginId = 'group_node:events';

  public const SUPPORTED_FORMAT = [
    'Y-m-d',
    'Y-m-d h:i A',
    'Y-m-d g:i A',
    'Y-n-j',
    'Y-n-j h:i A',
    'Y-n-j g:i A',
  ];

  /**
   * {@inheritdoc}
   */
  public function preRowSaveActions(MigratePreRowSaveEvent $event) {
    $row = $event->getRow();
    $dest_val = $row->getDestination();
    $media_val = $dest_val['field_attached_media']['target_id'];
    // If not a valid url return and don't do anything , this reduces the risk
    // of malicious scripts as we do not want to support HTML media from here.
    if (!UrlHelper::isValid($media_val)) {
      return;
    }
    // Get the media.
    $media_entity = $this->cpImportHelper->getMedia($media_val, $this->type, 'field_attached_media');
    if ($media_entity) {
      $row->setDestinationProperty('field_attached_media/target_id', $media_entity->id());
    }
    $row->setDestinationProperty('field_recurring_date/timezone', drupal_get_user_timezone());
  }

  /**
   * {@inheritdoc}
   */
  public function migratePostRowSaveActions(MigratePostRowSaveEvent $event) {
    $ids = $event->getDestinationIdValues();
    foreach ($ids as $id) {
      $this->cpImportHelper->addContentToVsite($id, $this->groupPluginId, 'node');
    }
    parent::migratePostRowSaveActions($event);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRowActions(MigratePrepareRowEvent $event) {
    $source = $event->getRow()->getSource();

    $start_date = $this->cpImportHelper->transformSourceDate($source['Start date'], self::SUPPORTED_FORMAT, 'Y-m-d h:i A');
    $event->getRow()->setSourceProperty('Start date', $start_date);

    $end_date = $this->cpImportHelper->transformSourceDate($source['End date'], self::SUPPORTED_FORMAT, 'Y-m-d h:i A');
    $event->getRow()->setSourceProperty('End date', $end_date);

    $signup_status = $source['Registration'];
    $signup_flag = FALSE;
    if (strtolower($signup_status) == 'on') {
      $signup_flag = TRUE;
    }
    $event->getRow()->setSourceProperty('Registration', $signup_flag);
    parent::prepareRowActions($event);
  }

  /**
   * Validates headers from csv file array.
   *
   * @param array $data
   *   Array derived from csv file.
   *
   * @return array
   *   Missing errors or empty if no errors.
   */
  public function validateHeaders(array $data): array {
    $headerMissing = FALSE;
    $eventsHeaders = [
      'Title',
      'Body',
      'Start date',
      'End date',
      'Location',
      'Registration',
      'Files',
      'Created date',
      'Path',
    ];
    $missing = [
      '@Title' => '',
      '@Body' => '',
      '@Start date' => '',
      '@End date' => '',
      '@Location' => '',
      '@Registration' => '',
      '@Files' => '',
      '@Created date' => '',
      '@Path' => '',
    ];

    foreach ($data as $row) {
      $columnHeaders = array_keys($row);
      foreach ($eventsHeaders as $eventsHeader) {
        if (!in_array($eventsHeader, $columnHeaders)) {
          $missing['@' . $eventsHeader] = $this->t('<li> @column </li>', ['@column' => $eventsHeader]);
          $headerMissing = TRUE;
        }
      }
    }
    return $headerMissing ? $missing : [];
  }

  /**
   * Validates Rows for csv import.
   *
   * @param array $data
   *   Array derived from csv file.
   *
   * @return array
   *   Missing errors or empty if no errors.
   */
  public function validateRows(array $data) : array {
    $hasError = FALSE;
    $titleRows = '';
    $fileRows = '';
    $dateRows = '';
    $endDateRows = '';
    $startDateRows = '';
    $message = [
      '@title' => '',
      '@file' => '',
      '@date' => '',
      '@start_date' => '',
      '@end_date' => '',
    ];

    foreach ($data as $delta => $row) {
      $row_number = ++$delta;
      // Validate Title.
      if (!$row['Title']) {
        $titleRows .= $row_number . ',';
      }
      // Validate File url.
      if ($url = $row['Files']) {
        $headers = get_headers($url, 1);
        if (strpos($headers[0], '200') === FALSE) {
          $fileRows .= $row_number . ',';
        }
      }
      // Validate Date.
      if ($createdDate = $row['Created date']) {
        $date = DateTime::createFromFormat(Base::CUSTOM_DATE_FORMAT, $createdDate);
        if (!$date || !($date->format(Base::CUSTOM_DATE_FORMAT) == $createdDate || $date->format('n-j-Y') == $createdDate)) {
          $dateRows .= $row_number . ',';
        }
      }

      if (!$this->cpImportHelper->validateSourceDate($row['Start date'], self::SUPPORTED_FORMAT)) {
        $startDateRows .= $row_number . ',';
      }

      if (!$this->cpImportHelper->validateSourceDate($row['End date'], self::SUPPORTED_FORMAT)) {
        $endDateRows .= $row_number . ',';
      }
    }
    $titleRows = rtrim($titleRows, ',');
    if ($titleRows) {
      $message['@title'] = $this->t('Title is required for row/rows @titleRows</br>', ['@titleRows' => $titleRows]);
      $hasError = TRUE;
    }
    $fileRows = rtrim($fileRows, ',');
    if ($fileRows) {
      $message['@file'] = $this->t('File url is invalid for row/rows @fileRows</br>', ['@fileRows' => $fileRows]);
      $hasError = TRUE;
    }
    $dateRows = rtrim($dateRows, ',');
    if ($dateRows) {
      $message['@date'] = $this->t('Date/Date Format is invalid for row/rows @dateRows</br>', ['@dateRows' => $dateRows]);
      $hasError = TRUE;
    }

    $startDateRows = rtrim($startDateRows, ',');
    if ($startDateRows) {
      $message['@start_date'] = $this->t('Start Date Format is invalid for row/rows @dateRows</br>', ['@dateRows' => $startDateRows]);
      $hasError = TRUE;
    }

    $endDateRows = rtrim($endDateRows, ',');
    if ($endDateRows) {
      $message['@end_date'] = $this->t('End Date Format is invalid for row/rows @dateRows</br>', ['@dateRows' => $endDateRows]);
      $hasError = TRUE;
    }
    return $hasError ? $message : [];
  }

}
