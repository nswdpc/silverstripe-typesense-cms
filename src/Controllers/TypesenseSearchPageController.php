<?php

namespace NSWDPC\Typesense\CMS\Controllers;

use NSWDPC\Search\Forms\Forms\AdvancedSearchForm;
use NSWDPC\Search\Forms\Forms\SearchForm;
use NSWDPC\Search\Typesense\Services\ScopedSearch;
use NSWDPC\Search\Typesense\Services\FormCreator;
use NSWDPC\Search\Typesense\Services\Logger;
use NSWDPC\Search\Typesense\Services\SearchHandler;
use ElliotSawyer\SilverstripeTypesense\Collection;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\View\ArrayData;

/**
 * Typesense search page controller
 * @extends \PageController<\NSWDPC\Typesense\CMS\Models\TypesenseSearchPage>
 */
class TypesenseSearchPageController extends \PageController
{
    private static array $allowed_actions = [
        'Form',
        'SearchForm'
    ];

    /**
     * {$Form} support in templates
     */
    public function Form(): SearchForm|AdvancedSearchForm
    {
        return $this->SearchForm();
    }

    /**
     * Return the search form
     */
    public function SearchForm(): SearchForm|AdvancedSearchForm|null
    {
        $model  = $this->data();
        $collection = $model->Collection();
        if (!$collection) {
            return null;
        }

        $useAdvancedSearch = $model->hasField('UseAdvancedSearch') && $model->UseAdvancedSearch == 1;
        return FormCreator::createForCollection($this, $collection, "SearchForm", $useAdvancedSearch);
    }

    /**
     * Process a typesense search and redirect to results
     */
    public function doSearch(array $data, Form $form): \SilverStripe\Control\HTTPResponse
    {
        $term = $data['Search'] ?? '';
        $term = strip_tags(trim((string)$term));
        return $this->redirect($this->Link('?q=' . $term));
    }

    /**
     * Results, currently only against one collection
     */
    public function index(HTTPRequest $request): \SilverStripe\Control\HTTPResponse|\SilverStripe\ORM\FieldType\DBHTMLText
    {
        // handle incoming  'Search'  query (BC)
        $getVars = $request->getVars();
        $search = trim($getVars['Search'] ?? '');
        if ($search !== '') {
            // Ensure using  'q' search term
            $query = $getVars;
            $query['q'] = $search;
            unset($query['Search']);
            return $this->redirect($this->Link('?' . http_build_query($query)));
        }

        $term = trim(strip_tags($request->getVar('q') ?? ''));
        if ($term === '') {
            // no search taking place
            return $this->renderSearchResults(ArrayData::create());
        }

        $model = $this->data();
        $collection = $model->Collection();
        $paginatedList = null;
        if ($collection) {
            try {
                $handler = SearchHandler::create('start');
                $perPage = $model->ResultsPerPage ?? SearchHandler::DEFAULT_PER_PAGE;
                $pageStart = $request->getVar($handler->getStartVarName()) ?? 0;
                // an option search scope, provided as JSON
                $searchScope = ScopedSearch::getDecodedSearchScope($model->SearchScope ?? '');
                if (!is_array($searchScope)) {
                    // no scope
                    $searchScope = [];
                }
                $searchKey = $model->getTypesenseSearchOnlyKey();
                $paginatedList = $handler->doSearch($collection, $term, $pageStart, $perPage, $searchScope, $searchKey);
            } catch (\JsonException $jsonException) {
                Logger::log("Typesense JsonException: " . $jsonException->getCode() . "/" . $jsonException->getMessage(), "NOTICE");
            } catch (\Typesense\Exceptions\TypesenseClientError $typesenseClientError) {
                Logger::log("Typesense TypesenseClientError: " . $typesenseClientError->getMessage() . " of type: " . $typesenseClientError::class, "NOTICE");
            } catch (\Exception $exception) {
                Logger::log("Typesense Exception: " . $exception->getMessage(), "NOTICE");
            }
        }

        $templateData = ArrayData::create([
            'Results' => $paginatedList, // results as an PaginatedList or null
            'SearchQuery' => $term
        ]);
        return $this->renderSearchResults($templateData);
    }

    /**
     * Return the result page using the defined layout and template data provided
     */
    protected function renderSearchResults(ArrayData $templateData): \SilverStripe\ORM\FieldType\DBHTMLText
    {
        // with these templates
        return $this->customise([
            'Layout' => $this->customise($templateData)
                ->renderWith(['NSWDPC/Typesense/CMS/Models/Layout/TypesenseSearchPage'])
        ])->renderWith([\NSWDPC\Typesense\CMS\Models\TypesenseSearchPage::class, \Page::class]);
    }
}
