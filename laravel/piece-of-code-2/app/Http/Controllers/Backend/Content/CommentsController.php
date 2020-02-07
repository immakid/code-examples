<?php

namespace App\Http\Controllers\Backend\Content;

use App\Models\Comment;
use Illuminate\Support\Arr;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Datatables\Datatables;
use App\Acme\Libraries\Traits\Controllers\Holocaust;

class CommentsController extends BackendController {

    use Holocaust;

    /**
     * @var string
     */
    protected static $holocaustModel = Comment::class;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {

        return view('backend.content.comments.index', [
            'types' => ['blog.post', 'product'],
            'selectors' => ['statuses' => ['pending', 'approved']],
            'selected' => ['statuses' => $this->request->query('status', 'pending')],
        ]);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve() {

        foreach ($this->request->input('ids', []) as $id) {
            if (!$comment = Comment::find($id)) {
                continue;
            }

            $comment->setStatus('approved');
        }

        flash()->success(__t('messages.success.approved', ['object' => __t('messages.objects.items')]));
        return redirect()->back();
    }

    /**
     * @return mixed
     */
    public function indexDatatables() {

        $type = $this->request->query('type');
        $status = $this->request->query('status');
        $query = Comment::status(Arr::get(Comment::getStatuses(), $status, 1))->type($type);

        return Datatables::of($query->with('commentable'))
            ->rawColumns(['checkbox', 'related'])
            ->editColumn('related', function(Comment $comment) {

                switch($comment->commentable_type) {
                    case 'blog.post':

                        $title = $comment->commentable->translate('title');
                        $url = route_region('app.blog.show', [$comment->commentable->translate('slug.string')]);
                        break;
                    case 'product':

                        $url = get_product_url($comment->commentable);
                        $title = $comment->commentable->translate('name');
                        break;
                }
                return sprintf('<a href="%s" target="_blank">%s</a>', $url, $title);
            })
            ->make(true);
    }
}