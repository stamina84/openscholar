<?php

namespace Drupal\Tests\cp_import\ExistingSite;

use Drupal\Tests\openscholar\ExistingSite\OsExistingSiteTestBase;

/**
 * Class CpImportPublicationsTest.
 *
 * @group kernel
 * @group cp-1
 *
 * @coversDefaultClass \Drupal\cp_import\Helper\CpImportPublicationHelper
 */
class CpImportPublicationsTest extends OsExistingSiteTestBase {

  /**
   * CpImport helper service.
   *
   * @var \Drupal\cp_import\Helper\CpImportPublicationHelper
   */
  protected $cpImportHelper;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->vsiteContextManager->activateVsite($this->group);
    $this->cpImportHelper = $this->container->get('cp_import.publication_helper');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests Saving a Bibtex entry works.
   *
   * @covers ::savePublicationEntity
   * @covers ::mapPublicationHtmlFields
   */
  public function testCpImportHelperSavePublicationBibtex() {

    $storage = $this->entityTypeManager->getStorage('bibcite_reference');
    $title = $this->randomString();

    // Test Negative.
    $pubArr = $storage->loadByProperties(['title' => $title]);
    $this->assertEmpty($pubArr);

    // Prepare data entry array.
    $abstract = 'This paper presents measurements of spectrally';
    $entry = [
      'type' => 'article',
      'journal' => 'Proceeding of the Combustion Institute',
      'title' => $title,
      'volume' => '32',
      'year' => '2009',
      'pages' => '963-970',
      'chapter' => '963',
      'abstract' => $abstract,
      'author' => ['F. Goulay', 'L. Nemes'],
    ];

    $context = $this->cpImportHelper->savePublicationEntity($entry, 'bibtex');

    $pubArr = $storage->loadByProperties([
      'type' => 'journal_article',
      'title' => $title,
    ]);

    // Test Positive.
    // Assert Saving Bibtex entry worked.
    $this->assertNotEmpty($pubArr);
    $this->assertArrayHasKey('success', $context);
    /** @var \Drupal\bibcite_entity\Entity\Reference $pubEntity */
    $pubEntity = array_values($pubArr)[0];
    $this->markEntityForCleanup($pubEntity);
    // Assert values directly from the loaded entity to be sure.
    $this->assertEquals($title, $pubEntity->get('title')->getValue()[0]['value']);
    $this->assertEquals(32, $pubEntity->get('bibcite_volume')->getValue()[0]['value']);

    // Test Mapping worked.
    $this->assertEquals($title, $pubEntity->get('html_title')->getValue()[0]['value']);
    $this->assertEquals($abstract, $pubEntity->get('html_abstract')->getValue()[0]['value']);
  }

  /**
   * Tests Saving a Bibtex entry works with year as string and correct mapping.
   *
   * @covers ::savePublicationEntity
   * @covers ::mapPublicationHtmlFields
   */
  public function testCpImportHelperSavePublicationBibtexCodedYear() {

    $storage = $this->entityTypeManager->getStorage('bibcite_reference');
    $title = $this->randomString();

    // Prepare data entry array.
    $abstract = 'This paper presents measurements of spectrally';
    $entry = [
      'type' => 'article',
      'journal' => 'Proceeding of the Combustion Institute',
      'title' => $title,
      'volume' => '32',
      'year' => 'In Press',
      'pages' => '963-970',
      'chapter' => '963',
      'abstract' => $abstract,
      'author' => ['M. Nind', 'L. Find'],
    ];

    $context = $this->cpImportHelper->savePublicationEntity($entry, 'bibtex');

    $pubArr = $storage->loadByProperties([
      'type' => 'journal_article',
      'title' => $title,
    ]);

    // Assert Saving Bibtex entry with string year worked.
    $this->assertNotEmpty($pubArr);
    $this->assertArrayHasKey('success', $context);
    /** @var \Drupal\bibcite_entity\Entity\Reference $pubEntity */
    $pubEntity = array_values($pubArr)[0];
    $this->markEntityForCleanup($pubEntity);
    // Assert values.
    $this->assertEquals($title, $pubEntity->get('title')->getValue()[0]['value']);
    // Test year is mapped correctly.
    $this->assertEquals(10030, $pubEntity->get('bibcite_year')->getValue()[0]['value']);
  }

  /**
   * Tests Special character mapping works.
   *
   * @covers ::mapSpecialChars
   */
  public function testCpImportHelperMapSpecialChars() {

    $entry = [
      // Test some random symbols.
      'symbols' => '$\#$ \%\&nbsp;\&amp;\&nbsp; + - ( )\&nbsp; * \&amp; ^ \%$ $\#$ @ !\&nbsp;\&nbsp; {\~A}',
      // Test some random text and symbol combination.
      'texts' => '{\textyen} {\~A} {\textregistered} "paper" {\textquoteright}presents{\textquoteright} {\textquoteleft}measurements{\textquoteleft}',
    ];

    // &nbsp appearing is ok as it will be read and handled by the browser not
    // our mapper.
    $expectedSymbols = '# %&nbsp;&amp;&nbsp; + - ( )&nbsp; * &amp; ^ %$ # @ !&nbsp;&nbsp; Ã';
    $expectedTextSymbols = '¥ Ã ® "paper" ’presents’ ‘measurements‘';

    $this->cpImportHelper->mapSpecialChars($entry);

    $this->assertEquals($expectedSymbols, $entry['symbols']);
    $this->assertEquals($expectedTextSymbols, $entry['texts']);
  }

  /**
   * Tests Editors are saved correctly and url mapping.
   *
   * @covers ::saveEditors
   * @covers ::savePublishersVersionUrl
   */
  public function testCpImportHelperSaveEditors() {

    $storage = $this->entityTypeManager->getStorage('bibcite_reference');
    $title = $this->randomString();
    // Prepare data entry array.
    $abstract = 'This paper presents measurements of spectrally';
    $entry = [
      'type' => 'article',
      'journal' => 'Proceeding of the Combustion Institute',
      'title' => $title,
      'volume' => '32',
      'year' => '2009',
      'pages' => '963-970',
      'chapter' => '963',
      'url' => 'http://abcde.net/thisarticle',
      'abstract' => $abstract,
      'author' => ['F. Goulay', 'L. Nemes'],
      // Editor not processed by the decoder so they will
      // be passed as a string for further processing.
      'editor' => 'Editor One and Editor Two',
    ];

    $context = $this->cpImportHelper->savePublicationEntity($entry, 'bibtex');

    $pubArr = $storage->loadByProperties([
      'type' => 'journal_article',
      'title' => $title,
    ]);

    // Assert Saving Bibtex entry with editors worked.
    $this->assertNotEmpty($pubArr);
    $this->assertArrayHasKey('success', $context);
    /** @var \Drupal\bibcite_entity\Entity\Reference $pubEntity */
    $pubEntity = array_values($pubArr)[0];
    $this->markEntityForCleanup($pubEntity);
    // Assert values.
    $this->assertEquals($title, $pubEntity->get('title')->getValue()[0]['value']);
    // Test editors are saved correctly.
    $contributors = $pubEntity->get('author')->getValue();
    $this->assertCount(4, $contributors);
    $this->assertEquals('editor', $contributors[2]['role']);
    $this->assertEquals('primary', $contributors[3]['category']);

    // Test url is saved correctly.
    $urlField = $pubEntity->get('publishers_version')->getValue()[0];
    $this->assertNotEmpty($urlField['title']);
    $this->assertEquals('http://abcde.net/thisarticle', $urlField['uri']);
  }

  /**
   * Tests Saving a Pubmed entry works.
   *
   * @covers ::savePublicationEntity
   * @covers ::mapPublicationHtmlFields
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function testCpImportHelperSavePublicationPubmedXml() {

    $storage = $this->entityTypeManager->getStorage('bibcite_reference');
    $title = $this->randomString();

    // Test Negative.
    $pubArr = $storage->loadByProperties(['title' => $title]);
    $this->assertEmpty($pubArr);

    // Prepare data entry array.
    $abstract = 'This is a journal article pubmed test.';
    $authors = [
      [
        'name' => "Jan L Brozek",
        'category' => 'primary',
      ],
      [
        'name' => "Monica Kraft",
        'category' => 'primary',
      ],
    ];
    $entry = [
      'ArticleTitle' => $title,
      'PublicationType' => "JOURNAL ARTICLE",
      'AuthorList' => $authors,
      'Volume' => '32',
      'Year' => '2009',
      'Pagination' => '963-970',
      'PMID' => '22928176',
      'Abstract' => $abstract,
      'url' => "https://www.ncbi.nlm.nih.gov/pubmed/22928176",
    ];

    $context = $this->cpImportHelper->savePublicationEntity($entry, 'pubmed');

    $pubArr = $storage->loadByProperties([
      'type' => 'journal_article',
      'title' => $title,
    ]);

    // Test Positive.
    // Assert Saving Pubmed entry worked.
    $this->assertNotEmpty($pubArr);
    $this->assertArrayHasKey('success', $context);
    /** @var \Drupal\bibcite_entity\Entity\Reference $pubEntity */
    $pubEntity = array_values($pubArr)[0];
    $this->markEntityForCleanup($pubEntity);
    // Assert values directly from the loaded entity to be sure.
    $this->assertEquals('22928176', $pubEntity->get('bibcite_pmid')->getValue()[0]['value']);
    $this->assertEquals('2009', $pubEntity->get('bibcite_year')->getValue()[0]['value']);

    // Test Mapping worked.
    $this->assertEquals($title, $pubEntity->get('html_title')->getValue()[0]['value']);
    $this->assertEquals($abstract, $pubEntity->get('html_abstract')->getValue()[0]['value']);
  }

}
