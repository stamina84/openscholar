<?php

namespace Drupal\os_widgets\Helper;

/**
 * Helper class for follow me widget default networks.
 */
class FollowMeWidgetHelper {

  /**
   * Retrieves the default networks available.
   *
   * @return array
   *   An associative array, keyed by the machine name. The values are an array
   *   including title of the network, along with the domain to be used for
   *   input validation of the network.
   */
  public function osWidgetsFollowMeDefaultNetworks() {
    $networks = [
      'facebook'  => [
        'title' => t('Facebook'),
        'domain' => 'facebook.com',
        'offset' => 531,
      ],
      'virb' => [
        'title' => t('Virb'),
        'domain' => 'virb.com',
        'offset' => 142,
      ],
      'myspace' => [
        'title' => t('MySpace'),
        'domain' => 'myspace.com',
        'offset' => 290,
      ],
      'twitter' => [
        'title' => t('Twitter'),
        'domain' => 'twitter.com',
        'offset' => 189,
      ],
      'google_photos' => [
        'title' => t('Google Photos'),
        'domain' => 'photos.google.com',
        'offset' => 676,
      ],
      'flickr' => [
        'title' => t('Flickr'),
        'domain' => 'flickr.com',
        'offset' => 483,
      ],
      'youtube' => [
        'title' => t('YouTube'),
        'domain' => 'youtube.com',
        'offset' => 94,
      ],
      'vimeo' => [
        'title' => t('Vimeo'),
        'domain' => 'vimeo.com',
        'offset' => 47,
      ],
      'lastfm' => [
        'title' => t('last.fm'),
        'domain' => 'last.fm',
        'offset' => 389,
      ],
      'linkedin' => [
        'title' => t('LinkedIn'),
        'domain' => 'linkedin.com',
        'offset' => 340,
      ],
      'delicious' => [
        'title' => t('Delicious'),
        'domain' => 'del.icio.us',
        'offset' => 579,
      ],
      'tumblr' => [
        'title' => t('Tumblr'),
        'domain' => 'tumblr.com',
        'offset' => 241,
      ],
      'pinterest' => [
        'title' => t('Pinterest'),
        'domain' => 'pinterest.com',
        'offset' => 0,
      ],
      'instagram' => [
        'title' => t('Instagram'),
        'domain' => 'instagram.com',
        'offset' => 630,
      ],
      'soundcloud' => [
        'title' => t('soundcloud'),
        'domain' => 'soundcloud.com',
        'offset' => 436,
      ],
      'googleplus' => [
        'title' => t('Google+'),
        'domain' => 'plus.google.com',
        'offset' => 722,
      ],
    ];
    return $networks;
  }

}
