<?php

namespace Drupal\vsite_privacy\Plugin\VsitePrivacyLevel;

use Drupal\vsite_privacy\Plugin\VsitePrivacyLevelPluginBase;

/**
 * Vsite privacy level.
 *
 * @VsitePrivacyLevel(
 *   title = @Translation("Anyone with the link."),
 *   id = "unindexed",
 *   description = @Translation("Anyone who has the URL to your site can view your site. Your site will not be indexed by search engines."),
 *   weight = 2
 * )
 */
class VsitePrivacyLevelUnindexed extends VsitePrivacyLevelPluginBase {}
