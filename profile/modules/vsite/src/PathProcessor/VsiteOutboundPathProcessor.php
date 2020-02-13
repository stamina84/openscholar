<?php

namespace Drupal\vsite\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupInterface;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Modifications to urls based on our own requirements.
 */
class VsiteOutboundPathProcessor implements OutboundPathProcessorInterface {

  public const NON_VSITE_PATHS = [
    '/admin',
    '/user',
  ];

  /**
   * The Manager for vsites.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * The regex pattern for the NON_VSITE_PATHS.
   *
   * @var string
   */
  protected $nonVsitePathsRegexPattern = NULL;

  /**
   * Constructor.
   */
  public function __construct(VsiteContextManagerInterface $vsiteContextManager) {
    $this->vsiteContextManager = $vsiteContextManager;
  }

  /**
   * Converts the non-vsite paths into a single regex pattern.
   *
   * @return string
   *   The pattern.
   */
  protected function nonVsitePathsRegexPattern(): string {
    if ($this->nonVsitePathsRegexPattern) {
      return $this->nonVsitePathsRegexPattern;
    }

    $individual_path_pattern = array_map(static function ($path) {
      return "\\$path";
    }, self::NON_VSITE_PATHS);

    $this->nonVsitePathsRegexPattern = '/^(' . implode('|', $individual_path_pattern) . ')/';

    return $this->nonVsitePathsRegexPattern;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL): string {
    if (preg_match($this->nonVsitePathsRegexPattern(), $path) === 1) {
      $options['purl_context'] = FALSE;
    }

    $group = NULL;
    if (isset($options['entity']) && $options['entity'] instanceof GroupInterface) {
      $group = $options['entity'];
    }

    // Before altering the path, make sure that the request is done from an
    // active vsite.
    if ($request &&
      $group &&
      (!isset($options['purl_context']) || $options['purl_context'] !== FALSE) &&
      (!isset($options['purl_exit']) || !$options['purl_exit'])) {
      $path = $this->vsiteContextManager->getActiveVsiteAbsoluteUrl($path);
    }

    return $path;
  }

}
