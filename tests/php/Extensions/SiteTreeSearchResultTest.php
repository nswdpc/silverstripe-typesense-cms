<?php

namespace NSWDPC\Typesense\CMS\Tests\Extensions;

use NSWDPC\Search\Typesense\Models\TypesenseSearchResult;
use NSWDPC\Typesense\CMS\Tests\TestOnly\PageWithAbstractField;
use NSWDPC\Typesense\CMS\Tests\TestOnly\PageWithAbstractMethod;
use NSWDPC\Typesense\CMS\Tests\TestOnly\PageWithSearchResultHooks;
use NSWDPC\Typesense\CMS\Tests\TestOnly\SearchResultHookExtension;
use Page;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;

class SiteTreeSearchResultTest extends SapphireTest
{
    protected $usesDatabase = true;

    // Page itself is a normal (non-TestOnly) fixture and is built automatically; only the
    // TestOnly page subclasses below need to be listed here.
    protected static $extra_dataobjects = [
        PageWithAbstractField::class,
        PageWithAbstractMethod::class,
        PageWithSearchResultHooks::class,
    ];

    protected function tearDown(): void
    {
        SearchResultHookExtension::reset();
        parent::tearDown();
    }

    public function testAbstractFallsBackToContentFirstSentenceByDefault(): void
    {
        $page = Page::create([
            'Title' => 'Default abstract page',
            'Content' => '<p>First sentence here. Second sentence here.</p>',
        ]);
        $page->write();

        $result = $page->getTypesenseSearchResult();
        $this->assertInstanceOf(TypesenseSearchResult::class, $result);
        $this->assertSame('First sentence here.', $result->Abstract);
    }

    public function testAbstractUsesAbstractFieldWhenAvailable(): void
    {
        $page = PageWithAbstractField::create([
            'Title' => 'Field abstract page',
            'Abstract' => 'Abstract from field',
            'Content' => '<p>Should not be used.</p>',
        ]);
        $page->write();

        $result = $page->getTypesenseSearchResult();
        $this->assertSame('Abstract from field', $result->Abstract);
    }

    public function testAbstractUsesGetSearchResultAbstractMethodWhenAvailable(): void
    {
        $page = PageWithAbstractMethod::create([
            'Title' => 'Method abstract page',
            'Content' => '<p>Should not be used.</p>',
        ]);
        $page->abstractOverride = 'Abstract <b>from method</b>';
        $page->write();

        $result = $page->getTypesenseSearchResult();
        // stripped of tags by getTypesenseSearchResult(), same as the other abstract sources
        $this->assertSame('Abstract from method', $result->Abstract);
    }

    public function testResultDataDefaults(): void
    {
        $page = Page::create([
            'Title' => 'A result',
            'Content' => 'Plain content sentence.',
            'SearchResultLabel' => 'News',
            'SearchResultLabels' => 'one,two',
            'SearchResultSubTitle' => 'Subtitle here',
        ]);
        $page->write();

        $result = $page->getTypesenseSearchResult();

        $this->assertSame('A result', $result->Title);
        $this->assertSame($page->Link(), $result->Link);
        $this->assertSame('News', $result->Label);
        $this->assertSame(['one', 'two'], $result->Labels);
        $this->assertSame('Subtitle here', $result->Info);
        $this->assertSame('', $result->ImageURL);
        $this->assertSame('', $result->ImageAlt);
    }

    public function testResultLabelsIsSingleEmptyStringWhenNoLabelsSet(): void
    {
        $page = Page::create(['Title' => 'No labels']);
        $page->write();

        $result = $page->getTypesenseSearchResult();
        $this->assertSame([''], $result->Labels);
    }

    public function testResultIncludesImageWhenSet(): void
    {
        $image = Image::create();
        $image->setFromString('test-image-content', 'result-image.jpg');
        $image->write();

        $page = Page::create(['Title' => 'With image', 'SearchResultImageID' => $image->ID]);
        $page->write();

        $result = $page->getTypesenseSearchResult();
        $this->assertSame($image->Link(), $result->ImageURL);
    }

    public function testBeforeHookShortCircuitsDefaultBuilding(): void
    {
        SearchResultHookExtension::$beforeReturnData = ['Title' => 'From before hook'];

        $page = PageWithSearchResultHooks::create(['Title' => 'Original title']);
        $page->write();

        $result = $page->getTypesenseSearchResult();
        $this->assertInstanceOf(TypesenseSearchResult::class, $result);
        $this->assertSame(['Title' => 'From before hook'], $result->toArray());
    }

    public function testBeforeHookInvalidDataThrowsRuntimeException(): void
    {
        SearchResultHookExtension::$beforeReturnInvalid = true;

        $page = PageWithSearchResultHooks::create(['Title' => 'Original title']);
        $page->write();

        $this->expectException(\RuntimeException::class);
        $page->getTypesenseSearchResult();
    }

    public function testAfterHookCanOverrideBuiltData(): void
    {
        SearchResultHookExtension::$afterMergeData = ['Title' => 'Overridden by after hook'];

        $page = PageWithSearchResultHooks::create(['Title' => 'Original title']);
        $page->write();

        $result = $page->getTypesenseSearchResult();
        $this->assertSame('Overridden by after hook', $result->Title);
    }

    public function testAfterHookInvalidDataThrowsRuntimeException(): void
    {
        SearchResultHookExtension::$afterReturnInvalid = true;

        $page = PageWithSearchResultHooks::create(['Title' => 'Original title']);
        $page->write();

        $this->expectException(\RuntimeException::class);
        $page->getTypesenseSearchResult();
    }
}
