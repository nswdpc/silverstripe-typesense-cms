<?php

namespace NSWDPC\Typesense\CMS\Models;

use ElliotSawyer\SilverstripeTypesense\Collection;
use SilverStripe\Forms\DropdownField;

/**
 * Typesense search page
 */
class TypesenseSearchPage extends \Page {

    private static string $table_name = 'TypesenseSearchPage';

    private static string $singular_name = 'Typesense search page';

    private static string $plural_name = 'Typesense search pages';

    private static array $has_one = [
        'Collection' => Collection::class
    ];

    /**
     * Return CMS fields with typesense configuration fields
     */
    public function getCmsFields() {
        $fields = parent::getCmsFields();
        $fields->addFieldToTab(
            'Root.Typesense',
            DropdownField::create(
                'CollectionID',
                'Collection',
                Collection::get()->sort('Name')->map('ID','Name')
            )->setEmptyString('')
        );
        return $fields;
    }

}
