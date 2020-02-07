<?php

namespace App\Http\Controllers\Backend\Content\Blog;

use App\Models\Region;
use Illuminate\Support\Arr;
use App\Models\Content\BlogPost as Post;
use App\Http\Controllers\BackendController;
use App\Models\Translations\BlogPostTranslation;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Http\Requests\Content\SubmitBlogPostFormRequest;

class PostsController extends BackendController {

	use Holocaust;

	/**
	 * @var string
	 */
	protected static $holocaustModel = Post::class;

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {

		$region = $this->request->getRegion(true);
		return view('backend.content.blog.posts.index', [
			'items' => $region->blogPosts,
			'selected' => ['region' => $region],
			'selectors' => ['region' => Region::all()],
		]);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function create() {
		assets()->injectPlugin(['bs-fileupload', 'bs-tagsinput', 'summernote']);

		return view('backend.content.blog.posts.create', [
			'selectors' => ['region' => Region::all()],
			'selected' => ['region' => $this->request->getRegion(true)]
		]);
	}

	/**
	 * @param Post $post
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function show(Post $post) {
		return redirect()->route('admin.content.blog.posts.edit', [$post->id]);
	}

	/**
	 * @param Post $post
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function edit(Post $post) {
		assets()->injectPlugin(['bs-fileupload', 'bs-tagsinput', 'summernote']);

		return view('backend.content.blog.posts.edit', ['item' => $post]);
	}

	/**
	 * @param SubmitBlogPostFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(SubmitBlogPostFormRequest $request) {

		$post = Post::createFromMultilingualRequest($request, null, function (BlogPostTranslation $model, $attributes) {
			$model->saveTags(explode(',', Arr::get($attributes, 'tags', [])));
		});

		if ($post) {

			$post->savePhotoFromRequest($request, array_values(config('cms.sizes.thumbs.blog_post')));

			flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.blog_post')]));
			return redirect()->route('admin.content.blog.posts.edit', [$post->id]);
		}

		flash()->error(__t('messages.error.saving'));
		return redirect()->back();
	}

	/**
	 * @param SubmitBlogPostFormRequest $request
	 * @param Post $post
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update(SubmitBlogPostFormRequest $request, Post $post) {

		if ($post->updateFromMultilingualRequest($request, null, function (BlogPostTranslation $model, $attributes) {
			$model->saveTags(explode(',', Arr::get($attributes, 'tags', [])));
		})
		) {

			$storeId = $request->input('store_id');
			$categoryIds = $request->input('category_ids', []);
			$post->categories()->sync($categoryIds);

			if (!$storeId && $post->store) {
				$post->store()->dissociate();
			}

			$post->savePhotoFromRequest($request, array_values(config('cms.sizes.thumbs.blog_post')));
			flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.blog_post')]));
		} else {
			flash()->error(__t('messages.error.saving'));
		}

		return redirect()->back();
	}
}
