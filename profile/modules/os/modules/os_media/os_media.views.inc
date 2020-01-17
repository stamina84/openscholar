<?php

/**
 * @file
 * Views integrations.
 */

/**
 * Implements hook_views_data_alter() for adding more features to media+view.
 */
function os_media_views_data_alter(array &$data) {
  $data['media']['os_media_media_usage_filter'] = [
    'title' => t('Filter by entity title using the media'),
    'filter' => [
      'title' => t('Used In'),
      'help' => t('Filter by entity title using the media. Filtering is always done using `LIKE` operator.'),
      'field' => 'mid',
      'id' => 'os_media_media_usage_filter',
    ],
  ];

  $data['file_managed']['os_media_file_name'] = [
    'title' => t('File name that is associated with the media.'),
    'help' => t('This field is going to return the file name used by the media - used via either `field_media_image` or `field_media_file`. To add support for more fields, add relationship of that file field in the view, and make alterations in the field plugin handler `os_media_file_name`.'),
    'field' => [
      'title' => t('File name'),
      'field' => 'filename',
      'id' => 'os_media_file_name',
    ],
  ];

  $data['file_managed']['os_media_file_size'] = [
    'title' => t('File size that is associated with the media.'),
    'help' => t('This field is going to return the file size used by the media - used via either `field_media_image` or `field_media_file`. To add support for more fields, add relationship of that file field in the view, and make alterations in the field plugin handler `os_media_file_size`.'),
    'field' => [
      'title' => t('File size'),
      'field' => 'filesize',
      'id' => 'os_media_file_size',
    ],
  ];
}
