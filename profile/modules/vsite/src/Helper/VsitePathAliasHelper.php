<?php

namespace Drupal\vsite\Helper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\PathAliasInterface;
use Drupal\purl\Plugin\ModifierIndex;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

/**
 * Class VsitePathAliasHelper.
 *
 * @package Drupal\vsite\Helper
 */
class VsitePathAliasHelper implements VsitePathAliasHelperInterface {

  /**
   * PURL's list of all modifiers.
   *
   * @var \Drupal\purl\Plugin\ModifierIndex
   */
  protected $modifierIndex;

  /**
   * Manager for entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Manager for vsites.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Constructor.
   */
  public function __construct(ModifierIndex $modifierIndex, EntityTypeManagerInterface $entityTypeManager, VsiteContextManagerInterface $vsiteContextManager) {
    $this->modifierIndex = $modifierIndex;
    $this->entityTypeManager = $entityTypeManager;
    $this->vsiteContextManager = $vsiteContextManager;
  }

  /**
   * {@inheritdoc}
   */
  public function save(PathAliasInterface $pathAlias): void {
    $source = $pathAlias->getPath();
    $alias = $pathAlias->getAlias();
    $is_group_source = preg_match('|^\/group\/[\d]*$|', $source);
    $new_alias = $alias;
    if (!$is_group_source) {
      $new_alias = $this->pathToToken($alias);
    }
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->vsiteContextManager->getActiveVsite();
    if ($group && !$is_group_source) {
      $group_prefix = '/[vsite:' . $group->id() . ']';
      if ($new_alias == $alias) {
        $new_alias = $group_prefix . $alias;
      }
      $pathAlias->setAlias($new_alias);
    }
  }

  /**
   * Takes the original path and translates it to a token.
   *
   * I.e. site01/about becomes [vsite:1]/about.
   *
   * @param string $path
   *   The path with the vsite's purl.
   *
   * @return string
   *   The path with the purl replaced by a token.
   */
  protected function pathToToken(string $path) {
    if (strpos($path, 'group/') !== FALSE) {
      return $path;
    }
    $modifiers = $this->getModifiers();

    list($site,) = explode('/', trim($path, '/'));
    foreach ($modifiers as $m) {
      if ($m->getModifierKey() == $site) {
        return preg_replace('|^/' . $site . '|', '/[vsite:' . $m->getValue() . ']', $path);
      }
    }

    return $path;
  }

  /**
   * Returns an array of all purl modifiers.
   *
   * @return \Drupal\purl\Modifier[]
   *   An array of purl modifiers
   */
  protected function getModifiers() {
    /** @var \Drupal\purl\Entity\Provider $provider */
    $provider = $this->entityTypeManager->getStorage('purl_provider')->load('group_purl_provider');
    return $this->modifierIndex->getProviderModifiers($provider);
  }

}
