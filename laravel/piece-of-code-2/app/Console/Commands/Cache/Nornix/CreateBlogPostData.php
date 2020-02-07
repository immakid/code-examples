<?php

namespace App\Console\Commands\Cache\Nornix;

use NornixCache;
use App\Models\Region;
use Illuminate\Support\Arr;
use App\Acme\Extensions\Console\Command;
use App\Acme\Repositories\Criteria\Featured;
use App\Acme\Repositories\Criteria\WithinRelation;
use App\Acme\Repositories\Interfaces\RegionInterface;
use App\Acme\Repositories\Interfaces\BlogPostInterface;

class CreateBlogPostData extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'n-cache:blog-post-data';

	/**
	 * @var string
	 */
	protected $description = 'Load list of featured blog posts';

	/**
	 * @var BlogPostInterface
	 */
	protected $post;

	/**
	 * @var RegionInterface
	 */
	protected $region;

	public function __construct(BlogPostInterface $post, RegionInterface $region) {
		parent::__construct();

		$this->post = $post;
		$this->region = $region;
	}

	/**
	 * @return mixed
	 */
	public function handle() {

		return $this->handleProxy(function () {

			foreach ($this->region->all() as $region) {

				$this->createCategoryMappings($region);

				$featured = $this->post->setCriteria([new Featured()])
					->without(['translations', 'media', 'store', 'categories'])
					->all();

				NornixCache::region($region, 'blog_posts', 'listing_featured')->write($featured->toArray(), true);
			}

			return 0;
		});
	}

	/**
	 * @param Region $region
	 * @return array
	 */
	protected function createCategoryMappings(Region $region) {

		$tree = NornixCache::region($region, 'categories', 'tree')->readRaw();
		$items = array_fill_keys(array_keys_all($tree), []);

		foreach (array_keys($items) as $key) {

			$posts = $this->post->setCriteria([new WithinRelation('categories', [$key])])->all();
			$items[$key] = array_unique(Arr::pluck($posts, 'id'));
		}

		NornixCache::region($region, 'categories', 'mapping_blog_posts')->write($items);
	}
}
