<?php

namespace NSWDPC\Typesense\CMS\Extensions;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use NSWDPC\Search\Typesense\Traits\TypesenseDefaultFields;
use NSWDPC\Search\Typesense\Models\TypesenseSearchResult;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBText;

/**
 * Provides Typesense search result for SiteTree
 */
class SiteTreeSearchResult extends Extension {

    use TypesenseDefaultFields;

    /**
     * Create a Typesense search result for indexing
     */
    public function getTypesenseSearchResult(): TypesenseSearchResult {
        $owner = $this->getOwner();
        $supportsElemental = class_exists(ElementalPageExtension::class) && $owner->supportsElemental();

        // try to determine the search abstract
        if($owner->hasMethod('getSearchResultAbstract')) {
            $abstract = (string)$owner->getSearchResultAbstract();
        } else if($owner->hasField('Abstract')) {
            // maybe the model provides a search abstract
            $abstract = $owner->dbObject('Abstract');
        } else if($supportsElemental) {
            // if elemental is supported
            $abstract = DBField::create_field(
                DBHTMLText::class,
                $owner->getElementsForSearch()
            )->setProcessShortcodes(false)
            ->FirstSentence();
        } else {
            // use the first sentence of the content
            $content = $owner->dbObject('Content');
            if($content instanceof DBHTMLText) {
                $content = $content->setProcessShortcodes(false);
            }
            $content = $content->FirstSentence();
        }

        $data = [
            'Title' => $owner->Title ?? '',
            'Date' => $owner->dbObject('LastEdited')->Format('d MMMM y'),
            'Link' => $owner->Link() ?? '',
            'ImageURL' => '',
            'ImageAlt' => '',
            'Label' => '',
            'Labels' => [],
            'Abstract' => strip_tags(trim($abstract)),
            'Info' => ''
        ];

        if($owner->hasMethod('decorateTypesenseSearchResult')) {
            // Allow project-level decoration of the search result
            $owner->decorateTypesenseSearchResult($data);
        }

        return TypesenseSearchResult::create($data);
    }

}
