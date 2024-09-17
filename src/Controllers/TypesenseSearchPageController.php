<?php

namespace NSWDPC\Typesense\CMS\Controllers;

use NSWDPC\SearchForms\Forms\AdvancedSearchForm;
use NSWDPC\SearchForms\Forms\SearchForm;
use NSWDPC\Typesense\CMS\Models\TypesenseSearchPage;
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
    public function SearchForm(): SearchForm|AdvancedSearchForm {
        $model  = $this->data();
        if($model->hasField('UseAdvancedSearch') && $model->UseAdvancedSearch == 1) {
            // TODO: add fields as per configuration of collection
            return AdvancedSearchForm::create(
                $this,
                'SearchForm',
                FieldList::create(
                    TextField::create(
                        'Search',
                        'Term'
                    )
                ),
                FieldList::create(
                    FormAction::create(
                        'doSearch',
                        'Search'
                    )
                )
            );
        } else {
            return SearchForm::create(
                $this,
                'SearchForm',
                FieldList::create(
                    TextField::create(
                        'Search',
                        'Term'
                    )
                ),
                FieldList::create(
                    FormAction::create(
                        'doSearch',
                        'Search'
                    )
                )
            );
        }
    }

    /**
     * Process a typesense search and redirect to results
     */
    public function doSearch(array $data, Form $form): \SilverStripe\Control\HTTPResponse
    {
        $term = $data['q'] ?? '';
        if($term === '') {
            $term = $data['Search'] ?? '';
        }
        $term = strip_tags(trim((string)$term));
        return $this->redirect( $this->Link('?q=' . $term));
    }

    /**
     * Return the result page using the defined layout and template data provided
     */
    protected function renderResult(ArrayData $templateData): \SilverStripe\ORM\FieldType\DBHTMLText {
        $result = $this->customise([
            'Layout' => $this->customise($templateData)->renderWith([TypesenseSearchPage::class])
        ])->renderWith([\Page::class]);
        return $result;
    }

    /**
     * Results, currently only against one collection
     */
    public function index(HTTPRequest $request) {
        $term = trim((string)$request->getVar('q') ?? '');

        if($term === '') {
            return $this->renderResult(ArrayData::create());
        }

        $client = \ElliotSawyer\SilverstripeTypesense\Typesense::client();
        $collectionName = '';
        $model = $this->data();
        $collection = $model->Collection();
        if($collection instanceof Collection) {
            $collectionName = (string)$collection->Name;
        }
        $results = ArrayList::create();
        if($collectionName === '') {
            return $this->renderResult(ArrayData::create());
        }

        $fieldsForSearch = $model->getFieldsForSearch();
        if($fieldsForSearch === []) {
            return $this->renderResult(ArrayData::create());
        }

        $searchParameters = [
            'q' => $term,
            'query_by' => implode(",", $fieldsForSearch)
        ];
        $search = $client->collections[$collectionName]->documents->search($searchParameters);
        foreach($search['hits'] as $hit) {
            $result = [];
            $result = array_merge($result, (array)$hit['document']);
            $results->push(
                ArrayData::create($result)
            );
        }
        $templateData = ArrayData::create([
            'Results' => $results
        ]);
        return $this->renderResult($templateData);
    }

}
