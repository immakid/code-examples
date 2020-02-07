<?php

namespace App\Console\Commands\Translations;

use App;
use App\Models\Language;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Translations\StringTranslation;

class ExportStrings extends Command
{

    /**
     * @var string
     */
    protected $signature = 'translations:export {code} {section?} {tag?}';

    /**
     * @var string
     */
    protected $description = 'Export saved translations into language files.';

    /**
     * @return mixed
     */
    public function handle()
    {
        try {
            $code = $this->argument('code');
            $section_key = $this->argument('section');
            $tag = $this->argument('tag');

            $language = Language::code($code)->firstOrFail();
            $default_language = Language::where('default', '=', true)->first();

            $all_sections = config('cms.translations.sections');

            if ($section_key && in_array($section_key, $all_sections)) {
                $all_sections = [$section_key];
            }


            foreach ($all_sections as $section) {
                $results = [];
                $strings = null;
                if ($section && $tag) {
                    $strings = StringTranslation::where('section', $section)
                        ->where('key', 'LIKE', $tag . '.%')
                        ->where('language_id', $language->id)
                        ->get();
                } else {
                    $strings = $language->strings()->forSection($section)->get();
                }


                foreach (Arr::pluck($strings->toArray(), 'value', 'key') as $key => $value) {
                    Arr::set($results, $key, stripslashes($value));
                }

                foreach (array_keys($results) as $key) {
                    $directory_name = ($language->code == $default_language->code)? '_default' : $language->code;

                    $directory = sprintf("%s/%s/%s", config('cms.paths.languages'), $section, $directory_name);
                    if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
                        $this->error("[-] Failed to create directory $directory");
                        break;
                    }

                    $file = sprintf("%s/%s.php", $directory, $key);
                    $data = sprintf("<?php\n\n return %s;", var_export($results[$key], true));

                    file_put_contents($file, $data);
                    foreach ([$directory, $file] as $path) {
                        chown($path, config('cms.system.apache_user'));
                        chgrp($path, config('cms.system.apache_group'));
                    }

                    if (App::runningInConsole()) {
                        $this->line("[+] Written to file $file");
                    }
                }
            }

            return 0;
        } catch (ModelNotFoundException $e) {
            if (App::runningInConsole()) {
                $this->error($e->getMessage());
            }

            return 1;
        }
    }
}
