<?php

namespace NSWDPC\Typesense\CMS\Tests\TestOnly;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

/**
 * Test double for the beforeGetTypesenseSearchResult / afterGetTypesenseSearchResult extension points
 * documented on NSWDPC\Typesense\CMS\Extensions\SiteTreeSearchResult::getTypesenseSearchResult().
 *
 * Set the static properties before calling getTypesenseSearchResult() on an owner that has this
 * extension applied, and call reset() in tearDown() so behaviour does not leak between tests.
 */
class SearchResultHookExtension extends DataExtension implements TestOnly
{
    /**
     * When set, beforeGetTypesenseSearchResult() replaces $data with this array (short-circuiting the
     * default result building).
     */
    public static ?array $beforeReturnData = null;

    /**
     * When true, beforeGetTypesenseSearchResult() sets $data to a non-array value.
     */
    public static bool $beforeReturnInvalid = false;

    /**
     * When set, afterGetTypesenseSearchResult() merges this array into the already-built $data.
     */
    public static ?array $afterMergeData = null;

    /**
     * When true, afterGetTypesenseSearchResult() sets $data to a non-array value.
     */
    public static bool $afterReturnInvalid = false;

    public static function reset(): void
    {
        static::$beforeReturnData = null;
        static::$beforeReturnInvalid = false;
        static::$afterMergeData = null;
        static::$afterReturnInvalid = false;
    }

    public function beforeGetTypesenseSearchResult(&$data)
    {
        if (static::$beforeReturnInvalid) {
            $data = 'not-an-array';
            return;
        }

        if (static::$beforeReturnData !== null) {
            $data = static::$beforeReturnData;
        }
    }

    public function afterGetTypesenseSearchResult(&$data)
    {
        if (static::$afterReturnInvalid) {
            $data = 'not-an-array';
            return;
        }

        if (static::$afterMergeData !== null) {
            $data = array_merge($data, static::$afterMergeData);
        }
    }
}
