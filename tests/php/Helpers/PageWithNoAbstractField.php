<?php

declare(strict_types=1);

namespace NSWDPC\Typesense\CMS\Tests\Helpers;

use Page;
use SilverStripe\Dev\TestOnly;

/**
 * A page without an "Abstract" field, to test Content.FirstSentence for abstract handling
 * fallback chain in NSWDPC\Typesense\CMS\Extensions\SiteTreeSearchResult::getTypesenseSearchResultAbstract()
 */
class PageWithNoAbstractField extends Page implements TestOnly
{
    private static string $table_name = 'NSWDPC_Tests_PageWithNoAbstractField';

    public function hasField($field)
    {

        if($field == "Abstract") {
            return false;
        }

        return parent::hasField($field);

    }
}
