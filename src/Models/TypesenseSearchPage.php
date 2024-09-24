<?php

namespace NSWDPC\Typesense\CMS\Models;

use ElliotSawyer\SilverstripeTypesense\Collection;
use NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\DataObject;

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
        'SearchFields' => 'Text', // further restrict search fields for this search
        'UseAdvancedSearch' => 'Boolean' // trigger advanced search
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
     * Return the search fields
     */
    public function getAvailableSearchFields(): array {
        $collection = $this->Collection();
        $fields = [];
        if($collection instanceof Collection) {
            $fields = $collection->Fields()
                ->filter(['type' => ['string','string[]']]) // only search on these types for now
                ->map('name','name')
                ->toArray();
        }
        return $fields;
    }

    /**
     * If fields are selected, get them, otherwise return all fields in the collection
     */
    public function getFieldsForSearch(): array {
        $fields = [];
        try {
            $fields = json_decode($this->SearchFields, true, 512, JSON_THROW_ON_ERROR);
            if(!is_array($fields)) {
                $fields = [];
            }
        } catch(\Exception $e) {

        }
        if($fields === []) {
            $fields = array_values($this->getAvailableSearchFields());
        }
        return array_filter($fields);
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
                    'UseAdvancedSearch',
                    _t(self::class . '.USE_ADVANCED_SEARCH', 'Use an advanced search form'),
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
                    _t(self::class . '.COLLECTION_FIELD_CHANGE_WARNING', 'When you change the collection, the fields below will be cleared. Choose the fields to search in after saving.'),
                ),
                ListboxField::create(
                    'SearchFields',
                    _t(self::class . '.FIELDS_TO_SEARCH_IN', 'Fields to search in'),
                    $this->getAvailableSearchFields()
                )
            ]
        );
        return $fields;
    }

    /**
     * Handle writing
     */
    public function onBeforeWrite() {
        parent::onBeforeWrite();
        if($this->isInDB() && $this->isChanged('CollectionID', DataObject::CHANGE_VALUE)) {
            // if the collection changes, remove the search fields
            $this->SearchFields = '';
        }
    }

}
