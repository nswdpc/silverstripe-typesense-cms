<?php

use SilverStripe\CMS\Model\SiteTree;

/**
 * This module's page type and controller extend the global \Page and \PageController classes that a
 * host Silverstripe application is expected to provide. This module ships no such application, so this
 * fixture stands in for it during this module's own test run only (it is only ever discovered when the
 * test-only tests/ directory is scanned, so it never leaks into a real host application's build).
 *
 * Deliberately NOT `implements TestOnly`: NSWDPC\Typesense\CMS\Models\TypesenseSearchPage extends this
 * class, and PHP interfaces are inherited by subclasses, so marking this TestOnly would make the real,
 * production TypesenseSearchPage class register as TestOnly too - causing Silverstripe's schema builder
 * to silently skip creating its database table during tests. Silverstripe's own cms module fixture for
 * this exact purpose (tests/bootstrap/fixtures/Page.php.fixture) omits TestOnly for the same reason.
 */
class Page extends SiteTree
{
}
