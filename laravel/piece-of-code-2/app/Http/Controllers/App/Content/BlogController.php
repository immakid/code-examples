<?php

namespace App\Http\Controllers\App\Content;

use NornixCache;
use App\Models\Category;
use Illuminate\Support\Arr;
use App\Acme\Repositories\Criteria\In;
use App\Models\Content\BlogPost as Post;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Http\Controllers\FrontendController;
use App\Acme\Repositories\Criteria\Paginate;

class BlogController extends FrontendController {

	/**
	 * @var int
	 */
	protected $per_page;

	public function __construct() {
		parent::__construct();

		$this->per_page = config('cms.limits.blog');
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {

		$ids = $this->getIds();
		return view('app.blog.new-index', [
			'body_class' => 'blog_index',
			'items' => $this->blogPostRepository
				->setCriteria([
					new In($this->getIds()),
					new Paginate($this->per_page, $this->request->getCurrentPage())
				])
				->all(),
			'pagination' => [
				'total' => ceil(count($ids) / $this->per_page),
				'total_items' => count($ids)
			]
		]);
	}

	/**
	 * @param Category $category
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function indexCategory(Category $category) {

		$ids = $this->getIds($category);
		$items = $this->blogPostRepository->setCriteria([
			new In($ids),
			new OrderBy('created_at', 'DESC'),
			new Paginate($this->per_page, $this->request->getCurrentPage()),
		])->all();

		if ($items->isEmpty()) {
			return redirect()->route('app.blog.index');
		}

		return view('app.blog.new-index', [
			'body_class' => 'blog_index',
			'items' => $items,
			'category' => $category,
			'pagination' => [
				'total' => ceil(count($ids) / $this->per_page),
                'total_items' => count($ids)
			],
			'title' => (
				$category->parent ?
					sprintf("%s | %s", $category->translate('name'), $category->parent->translate('name')) :
					$category->translate('name')
				)
				. sprintf(" | %s", __t("titles.blog.indexCategory"))
		]);
	}

	/**
	 * @param Post $post
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show(Post $post) {

		if (!$category = $post->categories()->has('parent')->first()) {
			$category = $post->categories->first();
		}

		$ids = [
			'all' => $this->getIds(),
			'category' => $this->getIds($category)
		];

		return view('app.blog.new-show', [
			'body_class' => 'blog_single',
			'item' => $post,
			'comments' => $post->comments()->approved()->get(),
			'items' => [
				'related' => $this->blogPostRepository->setCriteria([
					new In(Arr::except($ids['category'], $post->id)),
					new Paginate($this->per_page)
				])->all(),
				'latest' => $this->blogPostRepository->setCriteria([
					new In(Arr::except($ids['all'], $post->id)),
					new OrderBy('updated_at', 'DESC'),
					new Paginate($this->per_page)
				])->all()
			],
			'title' => $post->translate('title') . (
				$category->parent ?
					sprintf(" | %s | %s", $category->translate('name'), $category->parent->translate('name')) :
					$category->translate('name')
				)
				. sprintf(" | %s", __t("titles.blog.index"))
		]);
	}

	/**
	 * @param Post $post
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function commentCreate(Post $post) {

		return view('app.blog.comments.new-create', [
			'item' => $post
		]);
	}

	/**
	 * @param Post $post
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function commentStore(Post $post) {

		$this->validate($this->request, ['text' => 'required']);

		if ($this->blogPostRepository->writeComment($post, $this->request->input('text'))) {
			flash()->success(__t('messages.success.comment_submitted'));
		} else {
			flash()->error(__t('messages.error.general'));
		}

		return redirect()->route('app.blog.show', [$post->translate('slug.string')]);
	}

	/**
	 * @param Category|null $category
	 * @return array
	 */
	protected function getIds(Category $category = null) {

		if (!$category) {

			$mappings = NornixCache::region($this->request->getRegion(), 'categories', 'mapping_blog_posts')->readRaw();
			return array_unique(array_collapse($mappings));
		}

		return NornixCache::helpMeWithidsWithinCategory($this->request->getRegion(), $category, 'mapping_blog_posts');
	}
}
