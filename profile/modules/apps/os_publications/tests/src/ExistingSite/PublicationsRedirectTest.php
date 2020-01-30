<?php

namespace Drupal\Tests\os_publications\ExistingSite;

/**
 * Class PublicationsRedirectTest.
 *
 * @group functional
 * @group publications
 * @coversDefaultClass \Drupal\os_publications\Plugin\CpSetting\PublicationSettingsForm
 */
class PublicationsRedirectTest extends TestBase {

  /**
   * Group administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
  }

  /**
   * Tests publication redirect.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testRedirect(): void {
    $this->drupalLogin($this->groupAdmin);
    $web_assert = $this->assertSession();
    $this->visitViaVsite('cp/settings/apps-settings/publications', $this->group);
    $web_assert->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [
      'os_publications_preferred_bibliographic_format' => 'harvard_chicago_author_date',
      'biblio_sort' => 'year',
      'biblio_order' => 'DESC',
      'os_publications_export_format[bibtex]' => 'bibtex',
      'os_publications_export_format[endnote8]' => 'endnote8',
      'os_publications_export_format[endnote7]' => 'endnote7',
      'os_publications_export_format[tagged]' => 'tagged',
      'os_publications_export_format[ris]' => 'ris',
    ], 'Save configuration');

    $this->visitViaVsite("publications", $this->group);

    $web_assert->pageTextNotContains('Publications by Year');
    $web_assert->pageTextContains('Publications');
  }

}
