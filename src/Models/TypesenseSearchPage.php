<?php

namespace NSWDPC\Typesense\CMS\Models;

use ElliotSawyer\SilverstripeTypesense\Collection;
use NSWDPC\Search\Typesense\Services\SearchHandler;
use NSWDPC\Search\Typesense\Services\ScopedSearch;
use NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Typesense search page
 */
class TypesenseSearchPage extends \Page {

    private static string $table_name = 'TypesenseSearchPage';

    private static string $singular_name = 'Typesense search page';

    private static string $plural_name = 'Typesense search pages';

    private static array $has_one = [
        'Collection' => Collection::class // search in this collection
    ];

    private static array $db = [
        'UseAdvancedSearch' => 'Boolean', // trigger advanced search
        'IsGlobalSearch' => 'Boolean', // whether to use this page as the global search
        'ResultsPerPage' => 'Int' // if provided, the number of results per page, if not set, no pagination
    ];

    private static array $indexes = [
        'IsGlobalSearch' => true
    ];

    private static array $defaults = [
        'ResultsPerPage' => 10
    ];

    public function getControllerName()
    {
        return TypesenseSearchPageController::class;
    }

    /**
     * Return the title with the linked collection
     */
    public function TitleWithCollection(): string {
        $title = $this->MenuTitle ?? '';
        $collection = $this->Collection();
        return _t(
            self::class . ".TITLE_WITH_COLLECTION",
            "{title} - using collection '{collection}'",
            [
                'title' => $title,
                'collection' => $collection->Name ?? ''
            ]
        );
    }

    /**
     * Return CMS fields with typesense configuration fields
     */
    public function getCmsFields() {
        $fields = parent::getCmsFields();
        $fields->addFieldsToTab(
            'Root.Typesense',
            [
                CheckboxField::create(
                    'IsGlobalSearch',
                    _t(self::class . '.IS_GLOBAL_SEARCH', 'Use as site-wide search'),
                ),
                CheckboxField::create(
                    'UseAdvancedSearch',
                    _t(self::class . '.USE_ADVANCED_SEARCH', 'Use an advanced search form'),
                ),
                NumericField::create(
                    'ResultsPerPage',
                    _t(self::class . '.RESULTS_PER_PAGE', 'Results per page')
                )->setAttribute('max', SearchHandler::MAX_PER_PAGE)
                ->setAttribute('min', 0)
                ->setHtml5(true)
                ->setDescription(
                    _t(
                        self::class . '.RESULTS_PER_PAGE_HINT',
                        'Maximum: {num}',
                        [
                            'num' => SearchHandler::MAX_PER_PAGE
                        ]
                    )
                ),
                DropdownField::create(
                    'CollectionID',
                    _t(self::class . '.COLLECTION', 'Collection'),
                    Collection::get()->sort('Name')->map('ID','Name')
                )->setEmptyString('')
                ->setDescription(
                    _t(self::class . '.COLLECTION_FIELD_DESCRIPTION', 'Select a collection to search in'),
                )
                ->setRightTitle(
                    _t(self::class . '.COLLECTION_FIELD_CHANGE_WARNING', 'When you change the collection, the search scope will need to be reviewed.'),
                ),
                ScopedSearch::getSearchKeyField(),
                ScopedSearch::getSearchScopeField()
            ]
        );
        return $fields;
    }

    /**
     * Handle writing
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if($this->ResultsPerPage > SearchHandler::MAX_PER_PAGE) {
            $this->ResultsPerPage = SearchHandler::MAX_PER_PAGE;
        } else if($this->ResultsPerPage <= 0) {
            $this->ResultsPerPage = SearchHandler::DEFAULT_PER_PAGE;
        }
    }

    /**
     * Handle post-writing
     */
    public function onAfterWrite() {
        parent::onAfterWrite();
        if($this->IsGlobalSearch == 1) {
            DB::prepared_query("UPDATE \"TypesenseSearchPage\" SET IsGlobalSearch = 0 WHERE ID <> ?", [$this->ID]);
        }
    }

}
