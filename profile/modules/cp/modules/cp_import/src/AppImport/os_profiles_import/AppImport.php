<?php

namespace Drupal\cp_import\AppImport\os_profiles_import;

use DateTime;
use Drupal\Component\Utility\UrlHelper;
use Drupal\cp_import\AppImport\Base;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Drupal\media\Entity\Media;

/**
 * Class AppImport.
 *
 * @package Drupal\cp_import\AppImport\os_faq_import
 */
class AppImport extends Base {

  /**
   * Bundle type.
   *
   * @var string
   */
  protected $type = 'person';

  /**
   * Group plugin id.
   *
   * @var string
   */
  protected $groupPluginId = 'group_node:person';

  /**
   * {@inheritdoc}
   */
  public function preRowSaveActions(MigratePreRowSaveEvent $event) {
    $row = $event->getRow();
    $dest_val = $row->getDestination();
    $media_val = $dest_val['field_photo_person']['target_id'];
    $media_alt = $dest_val['field_first_name'] . '_' . $dest_val['field_last_name'];
    // If not a valid url return and don't do anything , this reduces the risk
    // of malicious scripts as we do not want to support HTML media from here.
    if (!UrlHelper::isValid($media_val)) {
      return;
    }
    // Get the media.
    $media_entity = $this->cpImportHelper->getMedia($media_val, $this->type, 'field_photo_person');
    if ($media_entity) {
      $file = Media::load($media_entity->id());
      $image = [
        [
          'target_id' => $file->get('field_media_image')->getValue()[0]['target_id'],
          'alt' => $media_alt,
        ],
      ];
      $row->setDestinationProperty('field_photo_person', $image);
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
    $links = [
      [
        'title' => $source['Websites title 1'],
        'uri' => $source['Websites url 1'],
      ],
      [
        'title' => $source['Websites title 2'],
        'uri' => $source['Websites url 2'],
      ],
      [
        'title' => $source['Websites title 3'],
        'uri' => $source['Websites url 3'],
      ],
    ];
    $event->getRow()->setSourceProperty('links', $links);
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
    $faqHeaders = [
      'First name',
      'Last name',
      'Photo',
      'Created date',
      'Email',
      'Path',
    ];
    $missing = [
      '@First name' => '',
      '@Last name' => '',
      '@Photo' => '',
      '@Created date' => '',
      '@Email' => '',
      '@Path' => '',
    ];

    foreach ($data as $row) {
      $columnHeaders = array_keys($row);
      foreach ($faqHeaders as $faqHeader) {
        if (!in_array($faqHeader, $columnHeaders)) {
          $missing['@' . $faqHeader] = $this->t('<li> @column </li>', ['@column' => $faqHeader]);
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
    $firstNameRows = '';
    $lastNameRows = '';
    $photoRows = '';
    $dateRows = '';
    $emailRows = '';
    $website1Rows = '';
    $website2Rows = '';
    $website3Rows = '';
    $message = [
      '@firstNameRows' => '',
      '@lastNameRows' => '',
      '@photo' => '',
      '@date' => '',
      '@website1' => '',
      '@website2' => '',
      '@website3' => '',
      '@email' => '',
    ];

    foreach ($data as $delta => $row) {
      $row_number = ++$delta;
      // Validate First Name.
      if (!$row['First name']) {
        $firstNameRows .= $row_number . ',';
      }
      // Validate Last Name.
      if (!$row['Last name']) {
        $lastNameRows .= $row_number . ',';
      }
      // Validate Photo url.
      if ($url = $row['Photo']) {
        $headers = get_headers($url, 1);
        if (strpos($headers[0], '200') === FALSE) {
          $photoRows .= $row_number . ',';
        }
      }
      // Validate Date.
      if ($createdDate = $row['Created date']) {
        $date = DateTime::createFromFormat(Base::CUSTOM_DATE_FORMAT, $createdDate);
        if (!$date || !($date->format(Base::CUSTOM_DATE_FORMAT) == $createdDate || $date->format('n-j-Y') == $createdDate)) {
          $dateRows .= $row_number . ',';
        }
      }
      // Validate Email.
      if ($email = $row['Email']) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $emailRows .= $row_number . ',';

        }
      }
      // Validate website 1 url.
      if ($row['Websites url 1']) {
        if (!UrlHelper::isValid($row['Websites url 1'], TRUE)) {
          $website1Rows .= $row_number . ',';
        }
      }
      // Validate website 2 url.
      if ($row['Websites url 2']) {
        if (!UrlHelper::isValid($row['Websites url 2'], TRUE)) {
          $website2Rows .= $row_number . ',';
        }
      }
      // Validate website 3 url.
      if ($row['Websites url 3']) {
        if (!UrlHelper::isValid($row['Websites url 3'], TRUE)) {
          $website3Rows .= $row_number . ',';
        }
      }

    }
    $firstNameRows = rtrim($firstNameRows, ',');
    if ($firstNameRows) {
      $message['@firstNameRows'] = $this->t('First name is required for row/rows @firstNameRows</br>', ['@firstNameRows' => $firstNameRows]);
      $hasError = TRUE;
    }
    $lastNameRows = rtrim($lastNameRows, ',');
    if ($lastNameRows) {
      $message['@lastNameRows'] = $this->t('Last name is required for row/rows @lastNameRows</br>', ['@lastNameRows' => $lastNameRows]);
      $hasError = TRUE;
    }
    $photoRows = rtrim($photoRows, ',');
    if ($photoRows) {

      $message['@photo'] = $this->t('Photo url is invalid for row/rows @photo</br>', ['@photo' => $photoRows]);
      $hasError = TRUE;
    }
    $dateRows = rtrim($dateRows, ',');
    if ($dateRows) {
      $message['@date'] = $this->t('Date/Date Format is invalid for row/rows @dateRows</br>', ['@dateRows' => $dateRows]);
      $hasError = TRUE;
    }
    $emailRows = rtrim($emailRows, ',');
    if ($emailRows) {
      $message['@email'] = $this->t('Email/Email Format is invalid for row/rows @emailRows</br>', ['@emailRows' => $emailRows]);
      $hasError = TRUE;
    }
    $website1Rows = rtrim($website1Rows, ',');
    if ($website1Rows) {
      $message['@website1'] = $this->t('Websites url 1 is invalid for row/rows @website1Rows</br>', ['@website1Rows' => $website1Rows]);
      $hasError = TRUE;
    }
    $website2Rows = rtrim($website2Rows, ',');
    if ($website2Rows) {
      $message['@website2'] = $this->t('Websites url 2 is invalid for row/rows @website2Rows</br>', ['@website2Rows' => $website2Rows]);
      $hasError = TRUE;
    }
    $website3Rows = rtrim($website3Rows, ',');
    if ($website3Rows) {
      $message['@website3'] = $this->t('Websites url 3 is invalid for row/rows @website3Rows</br>', ['@website3Rows' => $website3Rows]);
      $hasError = TRUE;
    }
    return $hasError ? $message : [];
  }

}
