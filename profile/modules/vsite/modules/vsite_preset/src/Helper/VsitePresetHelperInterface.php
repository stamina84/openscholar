<?php

namespace Drupal\vsite_preset\Helper;

use Drupal\group\Entity\GroupInterface;
use Drupal\vsite_preset\Entity\GroupPreset;

/**
 * Contract for VsitePresetHelper.
 */
interface VsitePresetHelperInterface {

  /**
   * Enable apps as per preset settings.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The newly created vsite.
   * @param \Drupal\vsite_preset\Entity\GroupPreset $preset
   *   The preset in context to work upon.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function enableApps(GroupInterface $group, GroupPreset $preset): void;

  /**
   * Creates default content for a preset.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The vsite for which content needs to be created.
   * @param string $uri
   *   The file uri to be parsed and used to fetch source csv.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createDefaultContent(GroupInterface $group, $uri): void;

}
