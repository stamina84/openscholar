<?php

namespace Drupal\Tests\os_publications\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * AuthorDuplicationTest.
 *
 * @group functional-javascript
 * @group publications
 */
class PublicationAuthorDuplicationTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Entity type manager service.
   *
   * @var object
   */
  protected $entityTypeManager;

  /**
   * Contributor Helper service.
   *
   * @var object
   */
  protected $contributorHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->contributorHelper = $this->container->get('os_publications.contributor_helper');
  }

  /**
   * Tests, if the contributor is not getting duplicated without autocomplete.
   */
  public function testAuthorDuplication(): void {
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $this->drupalLogin($group_member);

    $this->createPublicationWithAuthor();
    $this->createPublicationWithAuthor();

    $contributors = $this->entityTypeManager->getStorage('bibcite_contributor')->loadByProperties(['first_name' => 'Withtesting', 'last_name' => 'Authortesting']);

    foreach ($contributors as $contributor) {
      $this->markEntityForCleanup($contributor);
    }

    $this->assertEquals(count($contributors), 1);
  }

  /**
   * Helper function to create publication of type 'artwork'.
   */
  protected function createPublicationWithAuthor() {
    $bibcite_publisher = $this->randomMachineName();
    $this->visitViaVsite('bibcite/reference/add/book', $this->group);

    $this->getSession()->getPage()->fillField('bibcite_publisher[0][value]', $bibcite_publisher);
    $this->getSession()->getPage()->fillField('bibcite_year[0][value]', 1998);
    $this->getSession()->getPage()->fillField('author[0][target_id]', 'Withtesting Authortesting');

    $this->getSession()->getPage()->pressButton('Save');

    $books = $this->entityTypeManager->getStorage('bibcite_reference')->loadByProperties([
      'bibcite_publisher' => $bibcite_publisher,
    ]);

    if ($books) {
      $book = array_pop($books);
      $this->markEntityForCleanup($book);
    }
  }

}
