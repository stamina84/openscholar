<?php

namespace Drupal\vsite_privacy\Plugin\VsitePrivacyLevel;

use Drupal\vsite_privacy\Plugin\VsitePrivacyLevelPluginBase;

/**
 * Vsite privacy level.
 *
 * @VsitePrivacyLevel(
 *   title = @Translation("Public on the Web."),
 *   id = "public",
 *   description = @Translation("Anyone on the Internet can view your site. Your site will show in search results. No sign-in required."),
 *   weight = -1000
 * )
 */
class VsitePrivacyLevelPublic extends VsitePrivacyLevelPluginBase {}
