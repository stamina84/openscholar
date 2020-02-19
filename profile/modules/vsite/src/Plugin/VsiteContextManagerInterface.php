<?php

namespace Drupal\vsite\Plugin;

use Drupal\group\Entity\GroupInterface;

/**
 * Interface for the VsiteContextManager class.
 */
interface VsiteContextManagerInterface {

  /**
   * Activate the vsite represented by the given group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to be activated.
   */
  public function activateVsite(GroupInterface $group);

  /**
   * Return the active vsite.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The group if it is active, otherwise NULL.
   */
  public function getActiveVsite() : ?GroupInterface;

  /**
   * Return the purl for the active vsite.
   */
  public function getActivePurl();

  /**
   * Get absolute url for the active vsite.
   *
   * @param string $path
   *   The URL path that is requested.
   *
   * @return string
   *   The absolute path for the vsite.
   */
  public function getActiveVsiteAbsoluteUrl(string $path = ''): string;

  /**
   * Changes the dummy query string added to CSS and JS files of a vsite.
   *
   * Changing the dummy query string appended to CSS and JavaScript files forces
   * all browsers to reload fresh files.
   *
   * @param \Drupal\group\Entity\GroupInterface $vsite
   *   The vsite.
   */
  public static function vsiteFlushCssJs(GroupInterface $vsite): void;

}
