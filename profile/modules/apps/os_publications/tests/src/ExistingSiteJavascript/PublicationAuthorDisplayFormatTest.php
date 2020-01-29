<?php

namespace Drupal\Tests\os_publications\ExistingSiteJavascript;

use Drupal\Tests\openscholar\ExistingSiteJavascript\OsExistingSiteJavascriptTestBase;

/**
 * Tests Author autocomplete display format and parse feature.
 *
 * @group functional-javascript
 * @group publications
 *
 * @covers \Drupal\os_publications\Controller\AutocompleteController
 */
class PublicationAuthorDisplayFormatTest extends OsExistingSiteJavascriptTestBase {

  /**
   * Tests if contributors are properly displayed in autocomplete.
   */
  public function testAuthorAutocompleteDisplay(): void {
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $this->drupalLogin($group_member);

    $web_assert = $this->assertSession();

    $first_name = $this->randomString();
    $middle_name = $this->randomString();
    $last_name = $this->randomString();

    $this->createContributor([
      'first_name' => $first_name,
      'middle_name' => '',
      'last_name' => $last_name,
    ]);

    $this->createContributor([
      'first_name' => $first_name,
      'middle_name' => $middle_name,
      'last_name' => $last_name,
    ]);
    $this->visitViaVsite('bibcite/reference/add/book', $this->group);
    $web_assert->pageTextContains('Create Book');
    $page = $this->getSession()->getPage();
    $field_name = 'author';
    $autocomplete_field = $page->findField($field_name . '[0][target_id]');
    $autocomplete_field->setValue($first_name);
    $this->getSession()
      ->getDriver()
      ->keyDown($autocomplete_field
        ->getXpath(), ' ');
    $web_assert->waitOnAutocomplete();
    $results = $page->findAll('css', '.ui-autocomplete li');

    $name1 = $first_name . ' ' . $last_name;
    $name2 = $first_name . ' ' . $middle_name . ' ' . $last_name;
    $name_arr = [];
    foreach ($results as $value) {
      $name_arr[] = $value->getText();
    }
    $this->assertTrue(in_array($name1, $name_arr), 'Name not present in autocomplete suggestion.');
    $this->assertTrue(in_array($name2, $name_arr), 'Name not present in autocomplete suggestion.');
  }

  /**
   * Tests if contributors are properly parsed when entered.
   */
  public function testAuthorNameParser(): void {
    $group_member = $this->createUser();
    $this->addGroupEnhancedMember($group_member, $this->group);
    $this->drupalLogin($group_member);

    $web_assert = $this->assertSession();
    $field_name = 'author';

    // Check Firstname Lastname format.
    $name = 'John Lutts';
    $this->visitViaVsite('bibcite/reference/add/book', $this->group);
    $web_assert->pageTextContains('Create Book');
    $page = $this->getSession()->getPage();
    $autocomplete_field = $page->findField($field_name . '[0][target_id]');
    $autocomplete_field->setValue($name);
    file_put_contents('public://page-name.html', $this->getCurrentPageContent());
    $web_assert->waitForText($name);
    $this->getSession()
      ->getDriver()
      ->keyUp($autocomplete_field
        ->getXpath(), ' ');
    $text = 'FirstName: John LastName: Lutts';
    $this->waitForAjaxToFinish();
    $results = $page->findAll('css', '.os-author-parse-info');
    $this->assertEqual($text, current($results)->getText());

    // Check Firstname Middlename Lastname format.
    $this->visitViaVsite('bibcite/reference/add/book', $this->group);
    $web_assert->pageTextContains('Create Book');
    $page = $this->getSession()->getPage();
    $name = 'John David Lutts';
    $autocomplete_field = $page->findField($field_name . '[0][target_id]');
    $autocomplete_field->setValue($name);
    $web_assert->waitForText($name);
    $this->getSession()
      ->getDriver()
      ->keyUp($autocomplete_field
        ->getXpath(), ' ');

    $text = 'FirstName: John MiddleName: David LastName: Lutts';
    $this->waitForAjaxToFinish();
    $results = $page->findAll('css', '.os-author-parse-info');
    $this->assertEqual($text, current($results)->getText());

    // Check Prefix Firstname Middlename Lastname format.
    $this->visitViaVsite('bibcite/reference/add/book', $this->group);
    $web_assert->pageTextContains('Create Book');
    $page = $this->getSession()->getPage();
    $name = 'Mr. John David Lutts';
    $autocomplete_field = $page->findField($field_name . '[0][target_id]');
    $autocomplete_field->setValue($name);
    $web_assert->waitForText($name);
    $this->getSession()
      ->getDriver()
      ->keyUp($autocomplete_field
        ->getXpath(), ' ');

    $text = 'Prefix: Mr. FirstName: John MiddleName: David LastName: Lutts';
    $this->waitForAjaxToFinish();
    $results = $page->findAll('css', '.os-author-parse-info');
    $this->assertEqual($text, current($results)->getText());

    // Check Prefix Firstname Middlename Lastname Suffix format.
    $this->visitViaVsite('bibcite/reference/add/book', $this->group);
    $web_assert->pageTextContains('Create Book');
    $page = $this->getSession()->getPage();
    $name = 'Mr. John David Lutts Jr';
    $autocomplete_field = $page->findField($field_name . '[0][target_id]');
    $autocomplete_field->setValue($name);
    $web_assert->waitForText($name);
    $this->getSession()
      ->getDriver()
      ->keyUp($autocomplete_field
        ->getXpath(), ' ');

    $text = 'Prefix: Mr. FirstName: John MiddleName: David LastName: Lutts Suffix: Jr';
    $this->waitForAjaxToFinish();
    $results = $this->getSession()->getPage()->findAll('css', '.os-author-parse-info');
    $this->assertEqual($text, current($results)->getText());

  }

}
