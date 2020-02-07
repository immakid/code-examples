<?php

namespace App\Listeners\Subscribers;

use App\Events\BlogPost\Deleted;
use Illuminate\Events\Dispatcher;
use App\Acme\Interfaces\Events\BlogPostEventInterface;

class BlogPostEventSubscriber {

    /**
     * @param BlogPostEventInterface $event
     */
    public function onDelete(BlogPostEventInterface $event) {

        foreach ($event->getBlogPost()->translations as $translation) {
            $translation->delete();
        }
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events) {

        $events->listen(
            Deleted::class,
            'App\Listeners\Subscribers\BlogPostEventSubscriber@onDelete'
        );
    }

}