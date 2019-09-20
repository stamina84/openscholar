<?php

/**
 * @file
 * Hook implementations for the vsite module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;

/**
 * Implements hook_token_info().
 */
function vsite_token_info() {
  $type = [
    'name' => t('Vsite'),
    'description' => t('Tokens for active vsite.'),
  ];

  $settings['site_type'] = [
    'name' => t("Site type"),
    'description' => t("Field site type value of active personal group."),
  ];

  $settings['site_logo'] = [
    'name' => t("Site logo"),
    'description' => t("Field site logo URL of active personal group."),
  ];

  $settings['site_description'] = [
    'name' => t("Site description"),
    'description' => t("Field site description of active personal group."),
  ];

  $settings['site_favicon'] = [
    'name' => t("Site favicon"),
    'description' => t("Field site favicon URL of active personal group."),
  ];

  return [
    'types' => ['vsite' => $type],
    'tokens' => ['vsite' => $settings],
  ];
}

/**
 * Implements hook_tokens().
 */
function vsite_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'vsite') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'site_type':
          /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContext */
          $vsiteContext = \Drupal::service('vsite.context_manager');

          $replacements[$original] = '';
          /** @var \Drupal\group\Entity\GroupInterface $group */
          if ($group = $vsiteContext->getActiveVsite()) {
            $group_type = $group->bundle();
            switch ($group_type) {
              case 'personal':
                $replacements[$original] = 'personal';
                break;

              case 'department':
                $replacements[$original] = 'university';
                break;

              case 'project':
                $replacements[$original] = 'company';
                break;
            }
          }
          break;

        case 'site_logo':
          /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContext */
          $vsiteContext = \Drupal::service('vsite.context_manager');

          $replacements[$original] = '';
          /** @var \Drupal\group\Entity\GroupInterface $group */
          if ($group = $vsiteContext->getActiveVsite()) {
            $site_logo = $group->get('field_site_logo');
            $value = $site_logo->getValue();
            if (!empty($value)) {
              $replacements[$original] = ImageStyle::load('large')->buildUrl($group->field_site_logo->entity->getFileUri());
            }
          }
          break;

        case 'site_description':
          /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContext */
          $vsiteContext = \Drupal::service('vsite.context_manager');

          $replacements[$original] = '';
          /** @var \Drupal\group\Entity\GroupInterface $group */
          if ($group = $vsiteContext->getActiveVsite()) {
            $site_description = $group->get('field_site_description');
            $value = $site_description->getValue();
            if (!empty($value)) {
              $replacements[$original] = check_markup($value[0]['value'], $value[0]['format']);
            }
          }
          break;

        case 'site_favicon':
          /** @var \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsiteContext */
          $config_factory = \Drupal::service('config.factory');
          $config = $config_factory->get('vsite.settings');
          $favicon_fid = $config->get('favicon_fid');
          if (!empty($favicon_fid) && $file = File::load($favicon_fid)) {
            $favicon_url = file_create_url($file->getFileUri());
          }
          else {
            $base_theme_path = drupal_get_path('theme', 'os_base');
            $favicon_url = file_create_url($base_theme_path . '/favicon.ico');
          }
          $replacements[$original] = $favicon_url;
          break;
      }
    }
  }

  return $replacements;
}