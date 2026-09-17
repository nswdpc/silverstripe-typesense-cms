<?php

namespace NSWDPC\Typesense\CMS\Tests\TestOnly;

use Page;
use SilverStripe\Dev\TestOnly;

/**
 * A page with an "Abstract" field, used to test the second tier of the abstract-source
 * fallback chain in NSWDPC\Typesense\CMS\Extensions\SiteTreeSearchResult::getTypesenseSearchResultAbstract()
 */
class PageWithAbstractField extends Page implements TestOnly
{
    private static string $table_name = 'NSWDPC_Tests_PageWithAbstractField';

    private static array $db = [
        'Abstract' => 'Text',
    ];
}
