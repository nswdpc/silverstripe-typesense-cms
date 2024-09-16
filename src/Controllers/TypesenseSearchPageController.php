<?php

namespace NSWDPC\Typesense\CMS\Controllers;

use NSWDPC\SearchForms\Forms\SearchForm as TypesenseSearchForm;
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
        'SearchForm',
        'results'
    ];

    public function Form(): TypesenseSearchForm {
        return $this->SearchForm();
    }

    public function SearchForm(): TypesenseSearchForm {
        return TypesenseSearchForm::create(
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
        return $this->redirect( $this->Link('results/?q=' . $term));
    }

    /**
     * Results, currently only against one collection
     */
    public function results(HTTPRequest $request) {
        $client = \ElliotSawyer\SilverstripeTypesense\Typesense::client();
        $collectionName = '';
        $model = $this->data();
        $collection = $model->Collection();
        if($collection instanceof Collection) {
            $collectionName = (string)$collection->Name;
        }
        $term = (string)$request->getVar('q') ?? '*';
        $results = ArrayList::create();
        if($collectionName !== '' && $term !== '') {
            $searchParameters = [
                'q' => $term,
                'query_by' => 'Title, Content',// @todo from collection or page configuration
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
            return $this->customise($templateData);
        }
    }

}
