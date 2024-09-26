<?php

namespace NSWDPC\Typesense\CMS\Extensions;

use NSWDPC\Search\Typesense\Traits\TypesenseDefaultFields;
use NSWDPC\Search\Typesense\Models\TypesenseSearchResult;
use SilverStripe\Core\Extension;

/**
 * Provides Typesense search result for SiteTree
 */
class SiteTreeSearchResult extends Extension {

    use TypesenseDefaultFields;

    /**
     * Create a Typesense search result for indexing
     */
    public function getTypesenseSearchResult(): TypesenseSearchResult {
        $owner = $this->getOwner();
        $abstract = trim($owner->dbObject('Content')->FirstParagraph() ?? '');
        // TODO: elemental support.. if a Page has that

        return TypesenseSearchResult::create([
            'Title' => $owner->Title ?? '',
            'Date' => null,
            'Link' => $owner->Link() ?? '',
            'ImageURL' => '',
            'ImageAlt' => '',
            'Label' => '',
            'Labels' => [],
            'Abstract' => $abstract,
            'Info' => ''
        ]);
    }

}
