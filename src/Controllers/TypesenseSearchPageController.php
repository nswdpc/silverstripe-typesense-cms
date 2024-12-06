<?php

namespace NSWDPC\Typesense\CMS\Controllers;

use NSWDPC\Search\Forms\Forms\AdvancedSearchForm;
use NSWDPC\Search\Forms\Forms\SearchForm;
use NSWDPC\Typesense\CMS\Models\TypesenseSearchPage;
use NSWDPC\Search\Typesense\Services\FormCreator;
use NSWDPC\Search\Typesense\Services\SearchHandler;
use ElliotSawyer\SilverstripeTypesense\Collection;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;


/**
 * Typesense search page controller
 */
class TypesenseSearchPageController extends \PageController {

    private static $allowed_actions = [
        'Form',
        'SearchForm'
    ];

    /**
     * {$Form} support in templates
     */
    public function Form(): SearchForm|AdvancedSearchForm {
        return $this->SearchForm();
    }

    /**
     * Return the search form
     */
    public function SearchForm(): SearchForm|AdvancedSearchForm|null {
        $model  = $this->data();
        $collection = $model->Collection();
        if(!$collection) {
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
        return $this->redirect( $this->Link('?q=' . $term));
    }

    /**
     * Results, currently only against one collection
     */
    public function index(HTTPRequest $request) {
        // handle incoming  'Search'  query (BC)
        $getVars = $request->getVars();
        $search = trim($getVars['Search'] ?? '');
        if($search !== '') {
            // Ensure using  'q' search term
            $query = $getVars;
            $query['q'] = $search;
            unset($query['Search']);
            return $this->redirect( $this->Link('?' . http_build_query($query)));
        }

        $term = trim(strip_tags($request->getVar('q') ?? ''));
        if($term === '') {
            // no search taking place
            return $this->renderSearchResults(ArrayData::create());
        }
        $handler = SearchHandler::create();
        $model  = $this->data();
        $collection = $model->Collection();
        $results = null;
        if($collection) {
            $results = $handler->doSearch($collection, $term);
        }
        $templateData = ArrayData::create([
            'Results' => $results, // all results as an ArrayList
            'SearchQuery' => $term
        ]);
        return $this->renderSearchResults($templateData);
    }

    /**
     * Return the result page using the defined layout and template data provided
     */
    protected function renderSearchResults(ArrayData $templateData): \SilverStripe\ORM\FieldType\DBHTMLText {
        $result = $this->customise([
            'Layout' => $this->customise($templateData)
                ->renderWith(['NSWDPC/Typesense/CMS/Models/Layout/TypesenseSearchPage'])
        ])->renderWith([\TypesenseSearchPage::class, \Page::class]);// with these templates
        return $result;
    }

}
