<?php

namespace Drupal\os_widgets\Helper;

/**
 * Helper class for follow me widget default networks.
 */
final class FollowMeWidgetHelper {

  /**
   * An associative array of default networks.
   */
  const DEFAULTNETWORKS = [
    'facebook'  => [
      'title' => 'Facebook',
      'domain' => 'facebook.com',
    ],
    'virb' => [
      'title' => 'Virb',
      'domain' => 'virb.com',
    ],
    'myspace' => [
      'title' => 'MySpace',
      'domain' => 'myspace.com',
    ],
    'twitter' => [
      'title' => 'Twitter',
      'domain' => 'twitter.com',
    ],
    'google_photos' => [
      'title' => 'Google Photos',
      'domain' => 'photos.google.com',
    ],
    'flickr' => [
      'title' => 'Flickr',
      'domain' => 'flickr.com',
    ],
    'youtube' => [
      'title' => 'YouTube',
      'domain' => 'youtube.com',
    ],
    'vimeo' => [
      'title' => 'Vimeo',
      'domain' => 'vimeo.com',
    ],
    'lastfm' => [
      'title' => 'last.fm',
      'domain' => 'last.fm',
    ],
    'linkedin' => [
      'title' => 'LinkedIn',
      'domain' => 'linkedin.com',
    ],
    'delicious' => [
      'title' => 'Delicious',
      'domain' => 'del.icio.us',
    ],
    'tumblr' => [
      'title' => 'Tumblr',
      'domain' => 'tumblr.com',
    ],
    'pinterest' => [
      'title' => 'Pinterest',
      'domain' => 'pinterest.com',
    ],
    'instagram' => [
      'title' => 'Instagram',
      'domain' => 'instagram.com',
    ],
    'soundcloud' => [
      'title' => 'soundcloud',
      'domain' => 'soundcloud.com',
    ],
    'googleplus' => [
      'title' => 'Google+',
      'domain' => 'plus.google.com',
    ],
  ];

}
