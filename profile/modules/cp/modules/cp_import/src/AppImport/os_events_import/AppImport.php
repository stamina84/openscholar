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
    $media_entity = $this->cpImportHelper->getMedia($media_val, $this->type);
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
    $createdDate = $source['Created date'];
    if ($createdDate) {
      $date = DateTime::createFromFormat('Y-m-d', $createdDate);
      if ($date->format('Y-n-j') == $createdDate) {
        $event->getRow()->setSourceProperty('Created date', $date->format('Y-m-d'));
      }
    }
    else {
      $date = new DateTime();
      $event->getRow()->setSourceProperty('Created date', $date->format('Y-m-d'));
    }

    $start_date = strtoupper($source['Start date']);
    $date = DateTime::createFromFormat('Y-m-d', $start_date);
    if ($date && $date->format('Y-m-d') == $start_date) {
      $start_date = $date->format('Y-m-d H:i A');
    }
    $event->getRow()->setSourceProperty('Start date', $start_date);

    $end_date = strtoupper($source['End date']);
    $date = DateTime::createFromFormat('Y-m-d', $end_date);
    if ($date && $date->format('Y-m-d') == $end_date) {
      $end_date = $date->format('Y-m-d H:i A');
    }
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
    $message = [
      '@title' => '',
      '@file' => '',
      '@date' => '',
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
        $date = DateTime::createFromFormat('Y-m-d', $createdDate);
        if (!$date || !($date->format('Y-m-d') == $createdDate || $date->format('Y-n-j') == $createdDate)) {
          $dateRows .= $row_number . ',';
        }
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
    return $hasError ? $message : [];
  }

}
