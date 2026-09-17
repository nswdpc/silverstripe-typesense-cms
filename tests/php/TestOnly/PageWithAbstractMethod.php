<?php

declare(strict_types=1);

namespace NSWDPC\Typesense\CMS\Tests\TestOnly;

use Page;
use SilverStripe\Dev\TestOnly;

/**
 * A page providing a getSearchResultAbstract() method, used to test the highest-priority tier of the
 * abstract-source fallback chain in
 * NSWDPC\Typesense\CMS\Extensions\SiteTreeSearchResult::getTypesenseSearchResultAbstract()
 */
class PageWithAbstractMethod extends Page implements TestOnly
{
    private static string $table_name = 'NSWDPC_Tests_PageWithAbstractMethod';

    public string $abstractOverride = 'Abstract from getSearchResultAbstract()';

    public function getSearchResultAbstract(): string
    {
        return $this->abstractOverride;
    }
}
