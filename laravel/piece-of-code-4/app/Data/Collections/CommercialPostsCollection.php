<?php

namespace App\Data\Collections;

use App\Data\Entities\Audience\CommercialPost;
use Createvo\Support\AbstractTypedCollection;

class CommercialPostsCollection extends AbstractTypedCollection
{
    protected $type = CommercialPost::class;

    public static function makeFromSocialData(array $data)
    {
        $commercialPost = collect($data)->transform(function ($item) {
            return CommercialPost::makeFromSocialData($item);
        });

        return new CommercialPostsCollection($commercialPost);
    }
}
