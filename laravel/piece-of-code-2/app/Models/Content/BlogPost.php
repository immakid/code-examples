<?php

namespace App\Models\Content;

use Auth;
use App\Models\Tag;
use App\Models\Region;
use App\Models\Comment;
use App\Models\Category;
use App\Models\Language;
use App\Models\Users\User;
use App\Models\Stores\Store;
use App\Events\BlogPost\Deleted;
use App\Acme\Interfaces\Eloquent\Crumbly;
use App\Acme\Interfaces\Eloquent\Mediable;
use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Interfaces\Eloquent\Categorizable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Models\Translations\BlogPostTranslation;
use App\Acme\Libraries\Traits\Eloquent\Categorizer;
use App\Acme\Libraries\Traits\Eloquent\MediaManager;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;

/**
 * App\Models\Content\BlogPost
 *
 * @property int $id
 * @property int $author_id
 * @property int $region_id
 * @property int|null $store_id
 * @property string $status
 * @property bool $featured
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Users\User $author
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $categories
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $media
 * @property-read \App\Models\Region $region
 * @property-read \App\Models\Stores\Store|null $store
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Translations\BlogPostTranslation[] $translations
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost whereFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost whereRegionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost whereStoreId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost withinCategories($ids)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Content\BlogPost withinRegion(\App\Models\Region $region)
 * @mixin \Eloquent
 */
class BlogPost extends Model implements Translatable, Mediable, Categorizable, Crumbly {

    use Translator,
        Categorizer,
        MediaManager,
        RelationManager;

    /**
     * @var array
     */
    protected $fillable = ['featured'];

    /**
     * @var array
     */
    protected $with = ['translations', 'media', 'categories', 'store'];

    /**
     * @var array
     */
    protected $casts = [
        'featured' => 'bool'
    ];

    /**
     * @var string
     */
    protected $translatorClass = BlogPostTranslation::class;

    /**
     * @var array
     */
    protected $translatorColumns = ['title', 'content', 'excerpt'];

    /**
     * @var array
     */
    protected $requestRelations = [
        'store' => 'store_id',
        'region' => 'region_id',
        'categories' => 'category_ids'
    ];

    /**
     * @var string
     */
    protected static $mediaKey = 'blog-posts';

    public static function boot() {
        parent::boot();

        static::saving(function (BlogPost $model) {

            if (!$model->author) {
                $model->author()->associate(Auth::user());
            }
        });

        static::deleting(function (BlogPost $model) {
            event(new Deleted($model));
        });
    }

    /**
     * @param QueryBuilder $builder
     * @param Region $region
     * @return QueryBuilder
     */
    public function scopeWithinRegion(QueryBuilder $builder, Region $region) {
        return $builder->whereHas('region', function (QueryBuilder $builder) use ($region) {
            return $builder->where(get_table_column_name($builder->getModel(), 'id'), '=', $region->id);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author() {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function region() {
        return $this->belongsTo(Region::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function store() {
        return $this->belongsTo(Store::class)->enabled();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function tags() {
        return $this->morphMany(Tag::class, 'taggable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories() {
        return $this->belongsToMany(Category::class, 'blog_post_category_relations');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments() {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * @param Language $language
     * @return array
     */
    public function getBreadCrumbUrl(Language $language, $route = null) {

        $category = $this->categories->first();
        return array_merge(
            [route('app.blog.index')],
            $category->getBreadCrumbUrl($language, 'app.blog.indexCategory'),
            [route('app.blog.show', [$this->translate('slug.string', $language)])]
        );
    }

    /**
     * @param Language $language
     * @return array
     */
    public function getBreadCrumbTitle(Language $language) {

        $category = $this->categories->first();
        return array_merge(
            [__t('titles.blog.index')],
            $category->getBreadCrumbTitle($language),
            [$this->translate('title', $language)]
        );
    }

    /**
     * @return string
     */
    public function getSingleBackendBreadCrumbIdentifier() {
        return $this->translate('title');
    }
}
