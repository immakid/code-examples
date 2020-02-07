<?php

namespace App\Models\Translations;

use App\Models\Tag;
use Illuminate\Support\Arr;
use App\Models\Content\BlogPost;
use App\Acme\Interfaces\Eloquent\Sluggable;
use App\Acme\Interfaces\Eloquent\Translation;
use App\Acme\Libraries\Traits\Eloquent\Slugger;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use App\Acme\Libraries\Traits\Eloquent\Languages\Polyglot;

/**
 * App\Models\Translations\BlogPostTranslation
 *
 * @property int $id
 * @property int $parent_id
 * @property int $language_id
 * @property mixed $title
 * @property mixed $content
 * @property mixed|null $excerpt
 * @property-read \App\Models\Language $language
 * @property-read \App\Models\Content\BlogPost $parent
 * @property-read \App\Models\Slug $slug
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\BlogPostTranslation forLanguage(\App\Models\Language $language)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Acme\Extensions\Database\Eloquent\Model ordered()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\BlogPostTranslation whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\BlogPostTranslation whereExcerpt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\BlogPostTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\BlogPostTranslation whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\BlogPostTranslation whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Translations\BlogPostTranslation whereTitle($value)
 * @mixin \Eloquent
 */
class BlogPostTranslation extends Model implements Translation, Sluggable {

    use Slugger,
        Polyglot,
        RelationManager;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $with = ['slug', 'tags'];

    /**
     * @var array
     */
    protected $fillable = ['title', 'content', 'excerpt'];

    /**
     * @var string
     */
    public static $parentClass = BlogPost::class;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function tags() {
        return $this->morphMany(Tag::class, 'taggable');
    }

    /**
     * @param array $items
     * @return $this
     */
    public function saveTags(array $items) {

        $ids = [];
        $existing = Arr::pluck($this->tags->toArray(), 'id');
        foreach (array_filter($items, 'strlen') as $item) {

            if (!$tag = $this->tags()->string($item)->first()) {
                $tag = $this->tags()->create(['string' => $item]);
            }

            array_push($ids, $tag->id);
        }

        foreach (array_diff($existing, $ids) as $id) {
            $this->tags->find($id)->delete();
        }

        return $this;
    }
}
