<?php

namespace Drupal\cp_import\AppImport\os_news_import;

use DateTime;
use Drupal\Component\Utility\UrlHelper;
use Drupal\cp_import\AppImport\Base;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;

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
      $media_entity = $this->cpImportHelper->getMedia($media_val, $this->type);
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
   * {@inheritdoc}
   */
  public function prepareRowActions(MigratePrepareRowEvent $event) {
    $source = $event->getRow()->getSource();
    \Drupal::logger('row')->notice(print_r($source, TRUE));
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

    $news_date = $source['Date'];
    if ($news_date) {
      $date = DateTime::createFromFormat('Y-m-d', $news_date);
      if ($date->format('Y-n-j') == $news_date) {
        $event->getRow()->setSourceProperty('Date', $date->format('Y-m-d'));
      }
    }
    else {
      $date = new DateTime();
      $event->getRow()->setSourceProperty('Date', $date->format('Y-m-d'));
    }
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
    $newsDateRows = rtrim($newsDateRows, ',');
    if ($newsDateRows) {
      $message['@news_date'] = $this->t('Date/Date Format is invalid for row/rows @newsDateRows</br>', ['@newsDateRows' => $newsDateRows]);
      $hasError = TRUE;
    }
    $dateRows = rtrim($emptyDateRows, ',');
    if ($emptyDateRows) {
      $message['@news_date'] = $this->t('Date is empty for row/rows @emptyDateRows</br>', ['@emptyDateRows' => $emptyDateRows]);
      $hasError = TRUE;
    }
    $imageRows = rtrim($fileRows, ',');
    if ($imageRows) {
      $message['@image'] = $this->t('Image url is invalid for row/rows @imageRows</br>', ['@imageRows' => $imageRows]);
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
