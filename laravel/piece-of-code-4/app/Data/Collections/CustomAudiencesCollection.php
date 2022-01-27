<?php

namespace App\Data\Collections;

use App\Data\Entities\CustomAudience;
use Createvo\Support\AbstractTypedCollection;

/**
 * Class CustomAudiencesCollection
 *
 * @author Illia Balia <illia@vinelab.com>
 */
class CustomAudiencesCollection extends AbstractTypedCollection
{
    protected $type = CustomAudience::class;
}
