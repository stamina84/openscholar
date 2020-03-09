<?php

namespace Drupal\vsite\Path;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\purl\Plugin\ModifierIndex;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;

/**
 * Wraps AliasStorage so we can replace the vsite path with an integer id.
 */
class VsiteAliasRepository implements AliasRepositoryInterface {

  /**
   * The AliasRepositoryInterface object we're decorating.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected $repository;

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
  public function __construct(AliasRepositoryInterface $storage, ModifierIndex $modifierIndex, EntityTypeManagerInterface $entityTypeManager, VsiteContextManagerInterface $vsiteContextManager) {
    $this->repository = $storage;
    $this->modifierIndex = $modifierIndex;
    $this->entityTypeManager = $entityTypeManager;
    $this->vsiteContextManager = $vsiteContextManager;
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

  /**
   * Converts a vsite token into the site url.
   *
   * @param array $output
   *   The tokenied output.
   *
   * @return array
   *   The output with the token replaced with the vsite's purl.
   */
  protected function tokenToPath(array $output) {

    $matches = [];
    if (preg_match('|\[vsite:([\d]+)\]|', $output['alias'], $matches)) {
      $id = $matches[1];
      $modifiers = $this->getModifiers();
      foreach ($modifiers as $m) {
        if ($m->getValue() == $id) {
          $output['alias'] = str_replace($matches[0], $m->getModifierKey(), $output['alias']);
          return $output;
        }
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function preloadPathAlias($preloaded, $langcode) {
    $output = $this->repository->preloadPathAlias($preloaded, $langcode);

    foreach ($output as &$alias) {
      $alias = $this->tokenToPath($alias);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function pathHasMatchingAlias($initial_substring) {
    return $this->repository->pathHasMatchingAlias($initial_substring);
  }

  /**
   * {@inheritdoc}
   */
  public function lookupBySystemPath($path, $langcode) {
    $output = $this->repository->lookupBySystemPath($path, $langcode);
    if ($output && strpos($path, '/group/') === FALSE) {
      $output = $this->tokenToPath($output);
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   *
   * This is the entry point for requests to determine the real route.
   *
   * PURL strips the modifier from the request and starts a new request
   *   with the stripped-down path. By the time processing gets here, there's
   *   no modifiers at all on the path at all. We have to add it back on in
   *   order to detect the right entity properly.
   */
  public function lookupByAlias($alias, $langcode) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    if ($group = $this->vsiteContextManager->getActiveVsite()) {
      $alias = '/[vsite:' . $group->id() . ']' . $alias;
    }
    return $this->repository->lookupByAlias($alias, $langcode);
  }

}
