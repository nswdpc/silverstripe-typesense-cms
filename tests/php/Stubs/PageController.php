<?php

declare(strict_types=1);

use SilverStripe\CMS\Controllers\ContentController;

/**
 * See Page.php in this directory for why this fixture exists, and why it is not `implements TestOnly`
 * (TypesenseSearchPageController extends this class).
 * @template T of \Page
 * @extends \SilverStripe\CMS\Controllers\ContentController<T> @phpstan-ignore generics.notSupportedBound
 */
class PageController extends ContentController
{
}
