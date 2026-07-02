<?php

namespace NSWDPC\Typesense\CMS\Extensions;

use NSWDPC\Search\Typesense\Traits\TypesenseDefaultFields;
use NSWDPC\Search\Typesense\Models\TypesenseSearchResult;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\TagField\StringTagField;

/**
 * Provides Typesense search result support for the SiteTree data model
 * @property ?string $SearchResultSubTitle
 * @property ?string $SearchResultLabel
 * @property ?string $SearchResultLabels
 * @property int $SearchResultImageID
 * @method \SilverStripe\Assets\Image SearchResultImage()
 * @extends \SilverStripe\Core\Extension<(\SilverStripe\CMS\Model\SiteTree & static)>
 */
class SiteTreeSearchResult extends Extension
{
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
    public function getTypesenseSearchResult(): TypesenseSearchResult
    {
        $owner = $this->getOwner();

        // Allow custom project-level decoration of the search result
        $data = [];
        $owner->extend('beforeGetTypesenseSearchResult', $data);
        // @phpstan-ignore function.alreadyNarrowedType
        if (!is_array($data)) {
            throw new \RuntimeException("beforeGetTypesenseSearchResult has modified the search result type. An array is expected.");
        }

        // @phpstan-ignore notIdentical.alwaysFalse
        if ($data !== []) {
            // custom handling: beforeGetTypesenseSearchResult has provided its own result data
            return $data;
        }

        // search result abstract
        $abstract = $this->getTypesenseSearchResultAbstract();

        // images, if provided
        $imageURL = '';
        $imageAlt = '';
        if (($image = $owner->SearchResultImage()) && $image->exists()) {
            $imageURL = $image->Link();
            $imageAlt = $image->hasField('AltText') ? ($image->AltText ?? '') : '';
        }

        /** @var \SilverStripe\ORM\FieldType\DBDatetime $lastEdited */
        $lastEdited = $owner->dbObject('LastEdited');
        $data = [
            'Title' => $owner->Title ?? '',
            'Date' => $lastEdited->Format('d MMMM y'),
            'Link' => $owner->Link() ?? '',
            'ImageURL' => $imageURL,
            'ImageAlt' => $imageAlt,
            'Label' => $owner->SearchResultLabel ?? '',
            'Labels' => explode(",", $owner->SearchResultLabels ?? ''),
            'Abstract' => strip_tags(trim($abstract)),
            'Info' => $this->SearchResultSubTitle ?? ''
        ];

        $owner->extend('afterGetTypesenseSearchResult', $data);
        // @phpstan-ignore function.alreadyNarrowedType
        if (!is_array($data)) {
            throw new \RuntimeException("afterGetTypesenseSearchResult has modified the search result type. An array is expected.");
        }

        return TypesenseSearchResult::create($data);
    }

    /**
     * Return a search result abstract
     * If the owner record provides a method called 'getSearchResultAbstract' this will be called and a string or DBString is the expected return type
     * Otherwise, an "Abstract" field on the record will be called, followed by the Content field
     *
     * For elemental support, see the nswdpc/silverstripe-typesense-elemental module which may override this handling
     */
    protected function getTypesenseSearchResultAbstract(): string
    {
        $abstract = "";
        $owner = $this->getOwner();
        if ($owner->hasMethod('getSearchResultAbstract')) {
            // @phpstan-ignore method.notFound
            $abstract = $owner->getSearchResultAbstract();
        } elseif ($owner->hasField('Abstract')) {
            // maybe the model provides an abstract field
            $abstract = $owner->dbObject('Abstract');
        } else {
            // use the first sentence of the content
            $content = $owner->dbObject('Content');
            if ($content instanceof DBHTMLText) {
                $content = $content->setProcessShortcodes(false);
            }

            $abstract = $content->FirstSentence();
        }

        if (is_string($abstract)) {
            return $abstract;
        } elseif ($abstract instanceof DBString) {
            return $abstract->__toString();
        } else {
            // invalid, empty string
            return "";
        }
    }

}
