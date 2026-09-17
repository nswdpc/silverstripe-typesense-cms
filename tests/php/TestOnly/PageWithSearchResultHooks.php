<?php

namespace NSWDPC\Typesense\CMS\Tests\TestOnly;

use Page;
use SilverStripe\Dev\TestOnly;

/**
 * A page with SearchResultHookExtension applied, so tests can exercise the
 * beforeGetTypesenseSearchResult / afterGetTypesenseSearchResult extension points.
 */
class PageWithSearchResultHooks extends Page implements TestOnly
{
    private static string $table_name = 'NSWDPC_Tests_PageWithSearchResultHooks';

    private static array $extensions = [
        SearchResultHookExtension::class,
    ];
}
