<?php

namespace App\Http\Controllers\Backend\Content\Faq;

use App\Models\Language;
use Illuminate\Support\Arr;
use App\Models\Content\Faq\FaqSection;
use App\Http\Controllers\BackendController;
use App\Http\Requests\Content\Faq\SubmitFaqItemFormRequest;

class FaqItemsController extends BackendController {

    /**
     * @param FaqSection $faq
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(FaqSection $faq) {

        return view('backend.content.faq.items.index', [
            'section' => $faq
        ]);
    }

    /**
     * @param FaqSection $faq
     * @param SubmitFaqItemFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(FaqSection $faq, SubmitFaqItemFormRequest $request) {

        $ids = Arr::get(Arr::first($request->input($request->getTranslationsInputKey(), [])), '_ids', []);
        foreach ($request->input($request->getTranslationsInputKey(), []) as $language_id => $items) {

            $language = Language::find($language_id);
            foreach ($items['questions'] as $key => $question) {

                $featured = (bool)in_array($key, array_keys(Arr::get($items, 'featured', [])));

                if (!$answer = Arr::get($items, "answers.$key")) {
                    continue;
                } else if (!$id = Arr::get($items, "_ids.$key")) {

                    /**
                     * New entry
                     */
                    $item = $faq->items()->create(['featured' => $featured]);
                    $item->saveTranslation($language, [
                        'question' => $question,
                        'answer' => $answer
                    ]);

                    array_push($ids, $item->id);
                    continue;
                }

                if (!$item = $faq->items->find($id)) {
                    continue;
                } else if (!$translation = $item->translations()->forLanguage($language)->first()) {

                    /**
                     * New translation
                     */
                    $item->saveTranslation($language, [
                        'question' => $question,
                        'answer' => $answer
                    ]);
                } else {

                    /**
                     * Update
                     */
                    $item->updateTranslation($translation, [
                        'question' => $question,
                        'answer' => $answer
                    ]);
                }

                $item->update(['featured' => $featured]);
            }
        }

        // Delete non-existing
        foreach (array_diff(Arr::pluck($faq->load('items')->items->toArray(), 'id'), $ids) as $id) {
            $faq->items->find($id)->delete();
        }

        flash()->success(__t('messages.success.saved', ['object' => 'faq_item']));
        return redirect()->back();
    }
}