<?php

namespace App\Data\Models;

/**
 * @author Kinane Domloje <kinane@vinelab.com>
 */
class Collaboration
{
    /**
     * @var string
     */
    public $handle;

    /**
     * @var string|null
     */
    public $platformId;

    /**
     * @var string|null
     */
    public $graphPlatformId;

    /**
     * @var string|null
     */
    public $accessToken;

    /**
     * @var bool|null
     */
    public $isPrivate;

    /**
     * @var array
     */
    public $posts;

    /**
     * Collaboration constructor.
     * @param  string  $handle
     * @param  string|null  $platformId
     * @param  string|null  $graphPlatformId
     * @param  array  $posts
     * @param  string|null  $accessToken
     */
    public function __construct(string $handle, ?string $platformId, ?string $graphPlatformId, array $posts, ?string $accessToken = null, ?bool $isPrivate = null)
    {
        $this->handle = $handle;
        $this->accessToken = $accessToken;
        $this->platformId = $platformId;
        $this->graphPlatformId = $graphPlatformId;
        $this->isPrivate = $isPrivate;
        $this->setPosts($posts);
    }

    private function setPosts($posts)
    {
        $this->posts = [];
        foreach ($posts as $post) {
            $this->posts [] = new Post($post['post_id'], $post['type'], $post['shortcode']);
        }
    }

    public function replacePosts(array $posts)
    {
        $this->posts = $posts;
    }

    public function getPostsByType($type)
    {
        $posts = [];
        foreach ($this->posts as $post) {
            if($post->type == $type) {
                $posts[] = $post;
            }
        }

        return $posts;
    }

    public function getPostsIds()
    {
        $postsIds = [];

        foreach ($this->posts as $post) {
            $postsIds [] = $post->id;
        }

        return $postsIds;
    }

    public function getPostsShortcodes()
    {
        $postsShortcodes = [];

        foreach ($this->posts as $post) {
            $postsShortcodes [] = $post->shortcode;
        }

        return $postsShortcodes;
    }

    public function getPostsTypes()
    {
        $postsTypes = [];

        foreach ($this->posts as $post) {
            $postsTypes [] = $post->type;
        }

        return $postsTypes;
    }

    public function toArray()
    {
        $posts = [];
        foreach ($this->posts as $post) {
            $posts [] = $post->toArray();
        }

        return [
            'handle' => $this->handle,
            'platform_id' => $this->platformId,
            'graph_platform_id' => $this->graphPlatformId,
            'access_token' => $this->accessToken,
            'is_private' => $this->isPrivate,
            'posts' => $posts
        ];
    }
}
