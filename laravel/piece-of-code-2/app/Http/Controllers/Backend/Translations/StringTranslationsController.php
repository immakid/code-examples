<?php

namespace App\Http\Controllers\Backend\Translations;

use App\Http\Requests\SubmitStringTranslationsFormRequest;
use Artisan;
use App\Models\Language;
use Illuminate\Support\Arr;
use App\Http\Controllers\BackendController;
use App\Http\Requests\Translations\SubmitNewStringTranslationsFormRequest;
use App\Http\Requests\Translations\StringTranslationsDeleteRequest;
use App\Models\Translations\StringTranslation;


class StringTranslationsController extends BackendController {

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {

        $data = [
            'selectors' => [
                'language' => Language::orderBy('name')->get(),
                'section' => config('cms.translations.sections')
            ],
            'selected' => []
        ];

        $filters = $this->request->all(array_keys($data['selectors']));
        $default_language = Language::where('default', '=', true)->first();

        array_walk($filters, function (&$item, $key) use ($data, $default_language) {
            switch ($key) {
                case 'language':
                    $item = is_null($item) ?
                        Language::find(config('cms.defaults.language_id')) :
                        $data['selectors'][$key]->find($item);
                    break;
                case 'section':
                    $section = array_search($item, $data['selectors'][$key]);
                    $item = ($section === false) ? $data['selectors'][$key][0] : $data['selectors'][$key][$section];
            }
        });

        $data['selected'] = array_combine(array_keys($data['selectors']), $filters);
        $data['items'] = [
            'default' => $this->getFormattedStrings($default_language, $data['selected']['section']),
            'translations' => $this->getFormattedStrings($data['selected']['language'], $data['selected']['section']),
        ];

        return view('backend.translations.strings.index', $data);
    }

    /**
     * @param SubmitStringTranslationsFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitStringTranslationsFormRequest $request) {

        $section = $request->input('section');
        $selected_key = $request->input('selected_key');
        $language = Language::find($request->input('language_id'));

        foreach ($request->input('strings') as $key => $value) {
            if ($value === false || is_null($value)) {
                continue;
            }

            if (!$string = $language->strings()->filter($section, $key)->first()) {

                $language->strings()->create([
                    'key' => $key,
                    'value' => $value,
                    'section' => $section
                ]);

                continue;
            }

            $string->update(['value' => $value]);
        }

        $this->exportStrings($language, $section, $selected_key);

        flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.translations')]));

        return redirect()->back();
    }

    /**
     * @param Language $language
     */
    protected function exportStrings(Language $language, $section, $tag) {

        $command = 'translations:export';
        $result = Artisan::call($command, ['code' => $language->code, 'section' => $section, 'tag' => $tag]);

        if ($result === 1) {
            flash()->error(__t('messages.error.general'));
        }
    }

    /**
     * @param Language $language
     * @param string $section
     * @return array
     */
    protected function getFormattedStrings(Language $language, $section) {

        $results = [];
        $strings = $language->strings()->forSection($section)->get();
        $items = Arr::pluck($strings->toArray(), 'value', 'key');

        foreach (array_keys($items) as $key) {
            Arr::set($results, $key, $items[$key]);
        }

        return $results;
    }


    /**
     * @param SubmitStringTranslationsFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create() {

        $data = [
            'selectors' => [
                'language' => Language::orderBy('name')->get(),
                'section' => config('cms.translations.sections')
            ],
            'selected' => []
        ];

        $data['selected']['language'] = Language::find(config('cms.defaults.language_id'));
        $data['types'] = config('cms.translation_tabs');

        return view('backend.translations.strings.create', $data);
    }


    /**
     * @param SubmitStringTranslationsFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addNewKey(SubmitNewStringTranslationsFormRequest $request) {
        
        $language = Language::find($request->input('language'));
        $default_language = Language::where('default', '=', true)->first();

        $type = $request->input('type');
        $keyword= $request->input('keyword');
        $value = $request->input('value');
        $section = $request->input('section');


        $newSring = new StringTranslation([
            'language_id' => $request->input('language'),
            'key' => $type.".".$keyword,
            'value' => $value,
            'section' => $section
        ]);

        if($newSring->save()) {
            //Default language save function
            $newDefaultSring = new StringTranslation([
                'language_id' => $default_language->id,
                'key' => $type. "." . $keyword,
                'value' => $request->input('default_value'),
                'section' => $section
            ]);

            $newDefaultSring->save();
        }

        $this->exportStrings($language, $section, $type);
        $this->exportStrings($default_language, $section, $type);


        flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.translations')]));

        return redirect()->back();
    }


    public function destroy(StringTranslationsDeleteRequest $request) {

        $key = $request->input('key');
        $section = $request->input('section');
        $language = $request->input('lang_id');
        $tag = explode(".",$key);

        \DB::beginTransaction();

        try{

            $translations = StringTranslation::where('key', $key)
            ->where('section', $section)
            ->get();

            foreach ($translations as $translation){
                $translation->delete();
            }

            \DB::commit();
            $language = Language::find($language);
            $default_language = Language::where('default', '=', true)->first();
            $this->exportStrings($language, $section, $tag[0]);
            $this->exportStrings($default_language, $section, $tag[0]);
            flash()->success(__t('messages.success.deleted', ['object' => __t('messages.objects.item')]));

        }catch (\Exception $e) {
                \DB::rollback();
                flash()->error(__t('messages.error.deleting'));
            }

        return redirect()->back();
    }
}
