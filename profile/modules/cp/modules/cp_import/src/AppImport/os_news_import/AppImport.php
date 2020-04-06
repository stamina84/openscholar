<?php

namespace Drupal\cp_import\AppImport\os_news_import;

use DateTime;
use Drupal\Component\Utility\UrlHelper;
use Drupal\cp_import\AppImport\Base;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;

/**
 * Class News AppImport.
 *
 * @package Drupal\cp_import\AppImport\os_news_import
 */
class AppImport extends Base {

  /**
   * Bundle type.
   *
   * @var string
   */
  protected $type = 'news';

  /**
   * Group plugin id.
   *
   * @var string
   */
  protected $groupPluginId = 'group_node:news';

  /**
   * {@inheritdoc}
   */
  public function preRowSaveActions(MigratePreRowSaveEvent $event) {
    $row = $event->getRow();
    $dest_val = $row->getDestination();
    $media_val = $dest_val['field_attached_media']['target_id'];
    // If not a valid url return and don't do anything , this reduces the risk
    // of malicious scripts as we do not want to support HTML media from here.
    if (UrlHelper::isValid($media_val)) {
      // Get the media.
      $media_entity = $this->cpImportHelper->getMedia($media_val, $this->type, 'field_attached_media');
      if ($media_entity) {
        $row->setDestinationProperty('field_attached_media/target_id', $media_entity->id());
      }
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
    $newsHeaders = [
      'Title',
      'Date',
      'Body',
      'Redirect',
      'Image',
      'Files',
      'Created date',
      'Path',
    ];
    $missing = [
      '@Title' => '',
      '@Date' => '',
      '@Body' => '',
      '@Redirect' => '',
      '@Image' => '',
      '@Files' => '',
      '@Created date' => '',
      '@Path' => '',
    ];

    foreach ($data as $row) {
      $columnHeaders = array_keys($row);
      foreach ($newsHeaders as $newsHeader) {
        if (!in_array($newsHeader, $columnHeaders)) {
          $missing['@' . $newsHeader] = $this->t('<li> @column </li>', ['@column' => $newsHeader]);
          $headerMissing = TRUE;
        }
      }
    }
    return $headerMissing ? $missing : [];
  }

  /**
   * Validates Rows for News csv import.
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
    $bodyRows = '';
    $fileRows = '';
    $dateRows = '';
    $newsDateRows = '';
    $imageRows = '';
    $emptyDateRows = '';
    $message = [
      '@title' => '',
      '@news_date' => '',
      '@file' => '',
      '@date' => '',
      '@image' => '',
    ];

    foreach ($data as $delta => $row) {
      $row_number = ++$delta;
      // Validate Title.
      if (!$row['Title']) {
        $titleRows .= $row_number . ',';
      }
      // Validate Date.
      if ($news_date = $row['Date']) {
        $date = DateTime::createFromFormat('Y-m-d', $news_date);
        if (!$date || !($date->format('Y-m-d') == $news_date || $date->format('Y-n-j') == $news_date)) {
          $newsDateRows .= $row_number . ',';
        }
      }
      else {
        $emptyDateRows .= $row_number . ',';
      }
      // Validate Body.
      if (!$row['Body']) {
        $bodyRows .= $row_number . ',';
      }
      // Validate Image url.
      if ($url = $row['Image']) {
        $headers = get_headers($url, 1);
        if (strpos($headers[0], '200') === FALSE) {
          $imageRows .= $row_number . ',';
        }
      }
      // Validate File url.
      if ($url = $row['Files']) {
        $headers = get_headers($url, 1);
        if (strpos($headers[0], '200') === FALSE) {
          $fileRows .= $row_number . ',';
        }
      }
      // Validate Created Date.
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
    $newsDateRows = rtrim($newsDateRows, ',');
    if ($newsDateRows) {
      $msg_arr = $this->getErrorMessage($newsDateRows, 'News date format is invalid.');
      $message['@news_date'] = $msg_arr['message'];
      $msg_count += $msg_arr['count'];
      $hasError = TRUE;
    }
    $emptyDateRows = rtrim($emptyDateRows, ',');
    if ($emptyDateRows) {
      $msg_arr = $this->getErrorMessage($emptyDateRows, 'News date format is empty.');
      $message['@news_date'] = $msg_arr['message'];
      $msg_count += $msg_arr['count'];
      $hasError = TRUE;
    }
    $imageRows = rtrim($imageRows, ',');
    if ($imageRows) {
      $msg_arr = $this->getErrorMessage($imageRows, 'Image url is invalid.');
      $message['@image'] = $msg_arr['message'];
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
