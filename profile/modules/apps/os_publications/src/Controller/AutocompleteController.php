<?php

namespace Drupal\os_publications\Controller;

use Drupal\bibcite\HumanNameParser;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for handling Contributor name autocomplete.
 */
class AutocompleteController extends ControllerBase {

  /**
   * The human name parser service.
   *
   * @var \Drupal\bibcite\HumanNameParser
   */
  protected $humanParser;

  /**
   * Constructs a AutocompleteController object.
   *
   * @param \Drupal\bibcite\HumanNameParser $human_parser
   *   The human name parser service.
   */
  public function __construct(HumanNameParser $human_parser) {
    $this->humanParser = $human_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bibcite.human_name_parser')
    );
  }

  /**
   * Parse the name into its constituent parts.
   *
   * @param string $name
   *   Human name string.
   */
  public function parseContributorName($name) {
    try {
      $results = $this->humanParser->parse($name);
    }
    catch (\Exception $e) {
      $results = [];
    }
    return new JsonResponse(array_filter($results));
  }

}
