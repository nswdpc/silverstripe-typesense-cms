<?php

namespace NSWDPC\Typesense\CMS\Extensions;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use NSWDPC\Search\Typesense\Traits\TypesenseDefaultFields;
use NSWDPC\Search\Typesense\Models\TypesenseSearchResult;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\TagField\StringTagField;

/**
 * Provides Typesense search result support for the SiteTree data model
 */
class SiteTreeSearchResult extends DataExtension {

    use TypesenseDefaultFields;

    private static array $db = [
        'SearchResultSubTitle' => 'Varchar(255)',
        'SearchResultLabel' => 'Varchar(255)',
        'SearchResultLabels' => 'Text',
    ];

    private static array $has_one = [
        'SearchResultImage' => Image::class
    ];

    private static array $owns = [
        'SearchResultImage'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        $fileTypes = ['jpg','jpeg'];
        $fields->addFieldsToTab("Root.SearchResults", [
            TextField::create(
                "SearchResultSubTitle",
                _t(static::class . ".SUBTITLE_FOR_SEARCH_RESULT", "Subtitle for search result")
            ),
            TextField::create(
                "SearchResultLabel",
                _t(static::class . ".PRIMARY_LABEL_FOR_SEARCH_RESULT", "Primary label/category for search result")
            ),
            StringTagField::create(
                "SearchResultLabels",
                _t(static::class . ".LABELS_TAGS_FOR_SEARCH_RESULT", "Tags/labels for search result"),
                [],
                $owner->SearchResultLabels
            ),
            UploadField::create(
                "SearchResultImage",
                _t(static::class . ".SEARCH_RESULT_IMAGE_FOR_LISTINGS", "Image used for search result")
            )
            ->setAllowedExtensions($fileTypes)
            ->setIsMultiUpload(false)
            ->setFolderName('Pages/SearchResultImages/' . ($owner->ID ?? ''))
            ->setDescription(
                _t(
                    "nswds.ALLOWED_FILE_TYPES",
                    "Allowed file types: {types}",
                    [
                        'types' => implode(",", $fileTypes)
                    ]
                )
            )
        ]);
    }

    /**
     * Create a Typesense search result for indexing
     */
    public function getTypesenseSearchResult(): TypesenseSearchResult {
        $owner = $this->getOwner();

        // Allow custom project-level decoration of the search result
        $data = [];
        $owner->extend('beforeGetTypesenseSearchResult', $data);
        if($data !== []) {
            return $data;
        }

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

        // images, if provided
        $imageURL = '';
        $imageAlt = '';
        if(($image = $owner->SearchResultImage()) && $image->exists()) {
            $imageURL = $image->Link();
            $imageAlt = $image->hasField('AltText') ? ($image->AltText ?? '') : '';
        }

        $data = [
            'Title' => $owner->Title ?? '',
            'Date' => $owner->dbObject('LastEdited')->Format('d MMMM y'),
            'Link' => $owner->Link() ?? '',
            'ImageURL' => $imageURL,
            'ImageAlt' => $imageAlt,
            'Label' => $owner->SearchResultLabel ?? '',
            'Labels' => explode(",", $owner->SearchResultLabels ?? ''),
            'Abstract' => strip_tags(trim($abstract)),
            'Info' => $this->SearchResultSubTitle ?? ''
        ];

        $owner->extend('afterGetTypesenseSearchResult', $data);

        return TypesenseSearchResult::create($data);
    }

}
