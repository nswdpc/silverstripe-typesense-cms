<?php

namespace NSWDPC\Typesense\CMS\Tests\Models;

use NSWDPC\Search\Typesense\Models\TypesenseSearchCollection as Collection;
use NSWDPC\Search\Typesense\Services\SearchHandler;
use NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController;
use NSWDPC\Typesense\CMS\Models\TypesenseSearchPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;

class TypesenseSearchPageTest extends SapphireTest
{
    protected $usesDatabase = true;

    /**
     * TypesenseSearchPage has ScopedSearchExtension applied (see _config/config.yml), which requires a
     * non-empty SearchScope to pass validation on write. '{}' is the minimal valid JSON scope.
     */
    private function makeWritablePage(array $fields = []): TypesenseSearchPage
    {
        return TypesenseSearchPage::create(array_merge(['SearchScope' => '{}'], $fields));
    }

    public function testGetControllerNameReturnsTypesenseController(): void
    {
        $page = TypesenseSearchPage::create();
        $this->assertSame(TypesenseSearchPageController::class, $page->getControllerName());
    }

    public function testTitleWithCollectionIncludesLinkedCollectionName(): void
    {
        $collection = Collection::create(['Name' => 'Pages test collection']);
        $collection->write();

        $page = TypesenseSearchPage::create([
            'MenuTitle' => 'Search',
            'CollectionID' => $collection->ID,
        ]);

        $this->assertSame(
            "Search - using collection 'Pages test collection'",
            $page->TitleWithCollection()
        );
    }

    public function testTitleWithCollectionWithoutLinkedCollectionDoesNotError(): void
    {
        // With no CollectionID set, Collection() resolves to an empty, unsaved Collection
        // instance (not null), so this should render with an empty collection name rather than error.
        $page = TypesenseSearchPage::create(['MenuTitle' => 'Search']);
        $this->assertSame("Search - using collection ''", $page->TitleWithCollection());
    }

    public function testOnBeforeWriteClampsResultsPerPageAboveMax(): void
    {
        $page = $this->makeWritablePage(['Title' => 'Above max', 'ResultsPerPage' => SearchHandler::MAX_PER_PAGE + 50]);
        $page->write();
        $this->assertSame(SearchHandler::MAX_PER_PAGE, $page->ResultsPerPage);
    }

    public function testOnBeforeWriteAllowsResultsPerPageAtMax(): void
    {
        $page = $this->makeWritablePage(['Title' => 'At max', 'ResultsPerPage' => SearchHandler::MAX_PER_PAGE]);
        $page->write();
        $this->assertSame(SearchHandler::MAX_PER_PAGE, $page->ResultsPerPage);
    }

    public function testOnBeforeWriteResetsZeroResultsPerPageToDefault(): void
    {
        $page = $this->makeWritablePage(['Title' => 'Zero', 'ResultsPerPage' => 0]);
        $page->write();
        $this->assertSame(SearchHandler::DEFAULT_PER_PAGE, $page->ResultsPerPage);
    }

    public function testOnBeforeWriteResetsNegativeResultsPerPageToDefault(): void
    {
        $page = $this->makeWritablePage(['Title' => 'Negative', 'ResultsPerPage' => -5]);
        $page->write();
        $this->assertSame(SearchHandler::DEFAULT_PER_PAGE, $page->ResultsPerPage);
    }

    public function testOnBeforeWriteLeavesValidResultsPerPageUnchanged(): void
    {
        $page = $this->makeWritablePage(['Title' => 'Valid', 'ResultsPerPage' => 25]);
        $page->write();
        $this->assertSame(25, $page->ResultsPerPage);
    }

    public function testOnAfterWriteMakesNewGlobalSearchPageExclusive(): void
    {
        $pageA = $this->makeWritablePage(['Title' => 'Page A', 'IsGlobalSearch' => 1]);
        $pageA->write();

        $pageB = $this->makeWritablePage(['Title' => 'Page B', 'IsGlobalSearch' => 1]);
        $pageB->write();

        $pageA = TypesenseSearchPage::get()->byID($pageA->ID);
        $pageB = TypesenseSearchPage::get()->byID($pageB->ID);

        $this->assertSame(0, (int) $pageA->IsGlobalSearch, 'Previous global search page should have been unset');
        $this->assertSame(1, (int) $pageB->IsGlobalSearch, 'Newly written global search page should remain set');
    }

    public function testOnAfterWriteDoesNotAffectOtherPagesWhenNotGlobal(): void
    {
        $pageA = $this->makeWritablePage(['Title' => 'Page A', 'IsGlobalSearch' => 1]);
        $pageA->write();

        $pageB = $this->makeWritablePage(['Title' => 'Page B', 'IsGlobalSearch' => 0]);
        $pageB->write();

        $pageA = TypesenseSearchPage::get()->byID($pageA->ID);
        $this->assertSame(1, (int) $pageA->IsGlobalSearch, 'Writing a non-global page should not affect the existing global page');
    }

    public function testGetCmsFieldsAddsTypesenseTabFields(): void
    {
        $collectionA = Collection::create(['Name' => 'Zeta']);
        $collectionA->write();

        $collectionB = Collection::create(['Name' => 'Alpha']);
        $collectionB->write();

        $page = TypesenseSearchPage::create();
        $fields = $page->getCmsFields();

        $this->assertNotNull($fields->fieldByName('Root.Typesense'), 'Root.Typesense tab should exist');
        $this->assertNotNull($fields->dataFieldByName('IsGlobalSearch'));
        $this->assertNotNull($fields->dataFieldByName('UseAdvancedSearch'));

        $resultsPerPage = $fields->dataFieldByName('ResultsPerPage');
        $this->assertInstanceOf(NumericField::class, $resultsPerPage);
        $this->assertSame(SearchHandler::MAX_PER_PAGE, $resultsPerPage->getAttribute('max'));
        $this->assertSame(0, $resultsPerPage->getAttribute('min'));

        $collectionField = $fields->dataFieldByName('CollectionID');
        $this->assertInstanceOf(DropdownField::class, $collectionField);
        $source = $collectionField->getSource();
        $names = is_array($source) ? array_values($source) : array_values($source->toArray());
        $this->assertSame(['Alpha', 'Zeta'], $names, 'Collections should be sorted by name');
    }
}
