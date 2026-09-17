<?php

namespace NSWDPC\Typesense\CMS\Tests\Controllers;

use NSWDPC\Search\Typesense\Models\TypesenseSearchCollection as Collection;
use NSWDPC\Search\Typesense\Services\SearchHandler;
use NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController;
use NSWDPC\Typesense\CMS\Models\TypesenseSearchPage;
use NSWDPC\Typesense\CMS\Tests\TestOnly\SpyLogger;
use NSWDPC\Typesense\CMS\Tests\TestOnly\ThrowingSearchHandler;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;

class TypesenseSearchPageControllerTest extends SapphireTest
{
    protected $usesDatabase = true;

    private SpyLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new SpyLogger();
        Injector::inst()->registerService($this->logger, LoggerInterface::class);

        // Ensure a consistent "no Typesense server configured" baseline for every test in this file,
        // regardless of the host environment's own env vars.
        Environment::setEnv('TYPESENSE_SERVER', null);
        Environment::setEnv('TYPESENSE_API_KEY', null);
    }

    private function makePage(array $fields = []): TypesenseSearchPage
    {
        return TypesenseSearchPage::create(array_merge([
            'Title' => 'Search',
            'URLSegment' => 'search',
        ], $fields));
    }

    private function makeRequest(array $getVars): HTTPRequest
    {
        $request = new HTTPRequest('GET', '/', $getVars);
        $request->setSession(new Session([]));

        return $request;
    }

    public function testIndexWithNoQueryRendersWithoutSearching(): void
    {
        $controller = \NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController::create($this->makePage());
        $response = $controller->index($this->makeRequest([]));

        $this->assertStringContainsString('No results', (string) $response);
    }

    public function testIndexRedirectsLegacySearchVarToQ(): void
    {
        $controller = \NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController::create($this->makePage());
        $response = $controller->index($this->makeRequest(['Search' => 'hello']));

        $location = $response->getHeader('Location');
        $this->assertStringContainsString('q=hello', $location);
        $this->assertStringNotContainsString('Search=', $location);
    }

    public function testIndexRedirectPreservesOtherQueryVars(): void
    {
        $controller = \NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController::create($this->makePage());
        $response = $controller->index($this->makeRequest(['Search' => 'hello', 'foo' => 'bar']));

        $location = $response->getHeader('Location');
        $this->assertStringContainsString('q=hello', $location);
        $this->assertStringContainsString('foo=bar', $location);
    }

    public function testIndexCatchesJsonExceptionFromInvalidSearchScope(): void
    {
        $collection = Collection::create(['Name' => 'Test collection']);
        $collection->write();

        $page = $this->makePage([
            'CollectionID' => $collection->ID,
            'SearchScope' => '{not valid json',
        ]);

        $controller = \NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController::create($page);
        $response = $controller->index($this->makeRequest(['q' => 'term']));

        // Renders without a fatal error, with no results
        $this->assertStringContainsString('No results', (string) $response);
        $this->assertTrue($this->logger->hasMessageContaining('JsonException'), 'Expected a JsonException to be logged');
    }

    public function testIndexCatchesTypesenseClientErrorWhenServerNotConfigured(): void
    {
        $collection = Collection::create(['Name' => 'Test collection']);
        $collection->write();

        $page = $this->makePage(['CollectionID' => $collection->ID]);

        $controller = \NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController::create($page);
        $response = $controller->index($this->makeRequest(['q' => 'term']));

        // No TYPESENSE_SERVER configured means the Typesense client itself refuses to
        // build (ConfigError, a TypesenseClientError) rather than attempting a network call.
        $this->assertStringContainsString('No results', (string) $response);
        $this->assertTrue($this->logger->hasMessageContaining('TypesenseClientError'), 'Expected a TypesenseClientError to be logged');
    }

    public function testIndexCatchesGenericExceptionFromSearchHandler(): void
    {
        Injector::inst()->load([
            SearchHandler::class => [
                'class' => ThrowingSearchHandler::class,
            ],
        ]);
        ThrowingSearchHandler::$exceptionToThrow = new \RuntimeException('boom');

        $collection = Collection::create(['Name' => 'Test collection']);
        $collection->write();

        $page = $this->makePage(['CollectionID' => $collection->ID]);

        $controller = \NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController::create($page);
        $response = $controller->index($this->makeRequest(['q' => 'term']));

        $this->assertStringContainsString('No results', (string) $response);
        $this->assertTrue($this->logger->hasMessageContaining('boom'), 'Expected the generic exception message to be logged');
    }

    public function testDoSearchStripsTagsAndEncodesTermInRedirect(): void
    {
        $controller = \NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController::create($this->makePage());
        $form = Form::create($controller, 'SearchForm', FieldList::create(), FieldList::create());

        $response = $controller->doSearch(['Search' => '<b>hello</b> & world'], $form);

        $location = $response->getHeader('Location');
        $this->assertStringContainsString('q=' . urlencode('hello & world'), $location);
        // Regression check: the term must not split into extra query params
        $this->assertStringNotContainsString('world=', $location);
    }

    public function testDoSearchDefaultsToEmptyStringWhenSearchMissing(): void
    {
        $controller = \NSWDPC\Typesense\CMS\Controllers\TypesenseSearchPageController::create($this->makePage());
        $form = Form::create($controller, 'SearchForm', FieldList::create(), FieldList::create());

        $response = $controller->doSearch([], $form);

        $this->assertStringContainsString('q=', $response->getHeader('Location'));
    }
}
