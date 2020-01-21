<?php

namespace Drupal\os_pages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use Drupal\os_pages\BooksHelperInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Class BooksAutocompleteController.
 */
class BooksAutocompleteController extends ControllerBase {

  /**
   * The os_pages helper service.
   *
   * @var \Drupal\os_pages\BooksHelperInterface
   */
  protected $booksHelper;

  /**
   * BooksAutocompleteController constructor.
   *
   * @param \Drupal\os_pages\BooksHelperInterface $books_helper
   *   The os_pages helper service.
   */
  public function __construct(BooksHelperInterface $books_helper) {
    $this->booksHelper = $books_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os_pages.books_helper')
    );
  }

  /**
   * Handler for autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The input request.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The current vsite.
   * @param \Drupal\node\NodeInterface $node
   *   The current node entity.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matched entity labels as a JSON response.
   */
  public function handleAutocomplete(Request $request, GroupInterface $group = NULL, NodeInterface $node = NULL) {
    $results = [];
    if ($group) {
      $input = $request->query->get('q');
      $input = Xss::filter($input);
      // Get the typed string from the URL, if it exists.
      if ($input) {
        $matching_nids = $this->booksHelper->getMatchingNodes($input);
        $results = $this->booksHelper->getGroupBookResults($group, $matching_nids, $node);
      }

    }
    return new JsonResponse($results);
  }

}
