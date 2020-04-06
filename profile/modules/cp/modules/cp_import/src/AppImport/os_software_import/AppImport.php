<?php

namespace Drupal\cp_import\AppImport\os_software_import;

use DateTime;
use Drupal\Component\Utility\UrlHelper;
use Drupal\cp_import\AppImport\Base;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;

/**
 * Handles Software Import.
 *
 * @package Drupal\cp_import\AppImport\os_software_import
 */
class AppImport extends Base {

  /**
   * Bundle type.
   *
   * @var string
   */
  protected $type = 'software_project';

  /**
   * Group plugin id.
   *
   * @var string
   */
  protected $groupPluginId = 'group_node:software_project';

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
    $softwareHeaders = ['Title', 'Body', 'Files', 'Created date', 'Path'];
    $missing = [
      '@Title' => '',
      '@Body' => '',
      '@Files' => '',
      '@Created date' => '',
      '@Path' => '',
    ];

    foreach ($data as $row) {
      $columnHeaders = array_keys($row);
      foreach ($softwareHeaders as $softwareHeader) {
        if (!in_array($softwareHeader, $columnHeaders)) {
          $missing['@' . $softwareHeader] = $this->t('<li> @column </li>', ['@column' => $softwareHeader]);
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
    $bodyRows = '';
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
      // Validate Body.
      if (!$row['Body']) {
        $bodyRows .= $row_number . ',';
      }
      // Validate Date.
      if ($createdDate = $row['Created date']) {
        $date = DateTime::createFromFormat(Base::CUSTOM_DATE_FORMAT, $createdDate);
        if (!$date || !($date->format(Base::CUSTOM_DATE_FORMAT) == $createdDate || $date->format('n-j-Y') == $createdDate)) {
          $dateRows .= $row_number . ',';
        }
      }
    }
    $msg_count = 0;
    $titleRows = rtrim($titleRows, ',');
    if ($titleRows) {
      $msg_arr = $this->getErrorMessage($titleRows, 'The Title is required.');
      $message['@title'] = $msg_arr['message'];
      $msg_count += $msg_arr['count'];
      $hasError = TRUE;
    }
    $fileRows = rtrim($fileRows, ',');
    if ($fileRows) {
      $msg_arr = $this->getErrorMessage($fileRows, 'File url is invalid.');
      $message['@file'] = $msg_arr['message'];
      $msg_count += $msg_arr['count'];
      $hasError = TRUE;
    }
    $bodyRows = rtrim($bodyRows, ',');
    if ($bodyRows) {
      $msg_arr = $this->getErrorMessage($bodyRows, 'Body is required.');
      $message['@body'] = $msg_arr['message'];
      $msg_count += $msg_arr['count'];
      $hasError = TRUE;
    }
    $dateRows = rtrim($dateRows, ',');
    if ($dateRows) {
      $msg_arr = $this->getErrorMessage($dateRows, 'Created date format is invalid.');
      $message['@date'] = $msg_arr['message'];
      $msg_count += $msg_arr['count'];
      $hasError = TRUE;
    }
    if ($msg_count > 0) {
      $message['@summary'] = $this->t('The Import file has @count error(s). </br>', ['@count' => $msg_count]);
    }
    return $hasError ? $message : [];
  }

}
