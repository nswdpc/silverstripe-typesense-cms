<?php

declare(strict_types=1);

namespace NSWDPC\Typesense\CMS\Tests\Helpers;

use NSWDPC\Search\Typesense\Models\SearchResults;
use NSWDPC\Search\Typesense\Models\TypesenseSearchCollection as Collection;
use NSWDPC\Search\Typesense\Services\SearchHandler;
use SilverStripe\Dev\TestOnly;

/**
 * A SearchHandler double, bound in place of the real service via Injector so that
 * TypesenseSearchPageController::index() can be forced down a specific exception's catch branch without
 * needing a live Typesense server.
 */
class ThrowingSearchHandler extends SearchHandler implements TestOnly
{
    public static ?\Throwable $exceptionToThrow = null;

    #[\Override]
    public function doSearch(Collection $collection, array|string $searchQuery, int $pageStart = 0, int $perPage = 10, array $searchScope = [], string $searchOnlyApiKey = ''): ?SearchResults
    {
        if (static::$exceptionToThrow instanceof \Throwable) {
            throw static::$exceptionToThrow;
        }

        return null;
    }
}
