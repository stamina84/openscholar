<?php

namespace Drupal\Tests\os_publications\ExistingSite;

use Drupal\Core\Render\Markup;

/**
 * PublicationsViewsFunctionalTest.
 *
 * @group functional
 * @group publications
 */
class PublicationsViewsFunctionalTest extends TestBase {

  /**
   * Default bibcite citation style.
   *
   * @var array
   */
  protected $defaultBibciteCitationStyle;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Citation styler.
   *
   * @var \Drupal\bibcite\CitationStylerInterface
   */
  protected $citationStyler;

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

    $this->configFactory = $this->container->get('config.factory');
    /** @var \Drupal\Core\Config\ImmutableConfig $bibcite_settings */
    $bibcite_settings = $this->configFactory->get('bibcite.settings');
    $this->defaultBibciteCitationStyle = $bibcite_settings->get('default_style');
    $this->citationStyler = $this->container->get('bibcite.citation_styler');

    $this->groupAdmin = $this->createUser();
    $this->addGroupAdmin($this->groupAdmin, $this->group);
  }

  /**
   * Tests whether publication style is changed as per the settings.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testReferenceStyle(): void {
    $contributor = $this->createContributor([
      'first_name' => 'Leonardo',
      'middle_name' => '',
      'last_name' => 'Vinci',
    ]);

    $reference = $this->createReference([
      'html_title' => 'Mona Lisa',
      'author' => [
        'target_id' => $contributor->id(),
        'category' => 'primary',
        'role' => 'author',
      ],
      'bibcite_year' => [],
    ]);
    $this->group->addContent($reference, 'group_entity:bibcite_reference');

    $this->citationStyler->setStyleById('apa');

    // Construct data array as required by render method.
    $text = Markup::create($reference->html_title->value);
    $link = '"' . $reference->toLink($text)->toString() . '"';
    $author = [
      'category' => "primary",
      'role' => "author",
      'family' => $contributor->getLastName(),
      'given' => $contributor->getFirstName(),
    ];
    $data = [
      'title' => $link,
      'author' => [$author],
    ];

    $this->drupalLogin($this->groupAdmin);

    $render = $this->citationStyler->render($data);
    $expected = preg_replace('/\s*/m', '', $render);
    // Render method is adding some additional style, not present in actual
    // output, rest of the output is same.
    $expected = str_replace('style="text-indent:-25px;padding-left:25px;"', '', $expected);

    $this->visit("{$this->group->get('path')->first()->getValue()['alias']}/publications");
    $actual = $this->getActualHtml();
    $this->assertSame($expected, $actual);

    $this->visit("{$this->group->get('path')->first()->getValue()['alias']}/publications/title");
    $actual = $this->getActualHtml();
    $this->assertSame($expected, $actual);

    $this->visit("{$this->group->get('path')->first()->getValue()['alias']}/publications/author");
    $actual = $this->getActualHtml();
    $this->assertSame($expected, $actual);

    $this->visit("{$this->group->get('path')->first()->getValue()['alias']}/publications/year");
    $actual = $this->getActualHtml();
    $this->assertSame($expected, $actual);

    $this->drupalLogout();
  }

  /**
   * Check anonymous user access to publications.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function testAnonymousUserAccess(): void {
    $this->visit('/publications');

    $this->assertSession()->statusCodeEquals(403);

    $this->visit("{$this->group->get('path')->first()->getValue()['alias']}/publications");

    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    /** @var \Drupal\Core\Config\Config $bibcite_settings_mut */
    $bibcite_settings_mut = $this->configFactory->getEditable('bibcite.settings');
    $bibcite_settings_mut
      ->set('default_style', $this->defaultBibciteCitationStyle)
      ->save();

    parent::tearDown();
  }

}
