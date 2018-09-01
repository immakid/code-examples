<?php

function normolizeString($str)
{
    $cyr = [
        'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
        'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
        'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
        'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'
    ];
    $lat = [
        'a', 'b', 'v', 'g', 'd', 'e', 'io', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
        'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sht', 'a', 'i', 'y', 'e', 'yu', 'ya',
        'A', 'B', 'V', 'G', 'D', 'E', 'Io', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P',
        'R', 'S', 'T', 'U', 'F', 'H', 'Ts', 'Ch', 'Sh', 'Sht', 'A', 'I', 'Y', 'e', 'Yu', 'Ya'
    ];
    $str = str_replace($cyr, $lat, $str);

    $str = preg_replace("/[^A-Za-z0-9]/", '', $str);
    return $str;
}

function storeNewTags($tags)
{
    db::where_in('name', $tags, 'and', 1);
    $tagsRows = db::get('cl_tags');
    $existsTags = dblist__map_col($tagsRows, 'name');
    $newTags = array_diff($tags, $existsTags);
    foreach ($newTags as $newTag) {
        db::insert('cl_tags', ['name' => $newTag]);
    }
}

/**
 * Список из бд => простой хэш
 *
 * @param $list
 * @param $k
 * @param $v
 * @return array
 */
function dblist__simplhash($list, $k, $v, $val_name = '')
{
    $ret = array();
    foreach ($list as $row) {
        if ($val_name) {
            $ret[$row[$k]] = array($val_name => $row[$v]);
        } else {
            $ret[$row[$k]] = $row[$v];
        }
    }

    return $ret;
}

/**
 * Список из бд  => хэш из указанного ключа, а значение вся строка бд
 *
 * @param $list
 * @param $k
 * @return array
 */
function dblist__hash($list, $k)
{
    $ret = array();
    foreach ($list as $row) {
        $key = $row[$k];

        $ret[$key] = $row;
    }

    return $ret;
}

/**
 * @param $list
 * @param $k
 * @return array
 */
function dblist__grouped_hash($list, $k)
{
    $ret = array();
    foreach ($list as $row) {
        $key = $row[$k];

        $ret[$key][] = $row;
    }

    return $ret;
}

/**
 * Список из бд  => map колонки
 *
 * @param $list
 * @param $col_name
 * @return array
 */
function dblist__map_col($list, $col_name)
{
    $ret = array();

    foreach ($list as $row) {
        $ret[] = $row[$col_name];
    }

    return $ret;
}

function renderTpl($tpl, $par)
{
    global $app;
    return $app['twig']->render($tpl, $par);
}

function renderPage($tpl, $par)
{

    $ret = renderTpl('header.twig', array());

    $ret .= renderTpl($tpl, $par);


    $ret .= renderTpl('footer.twig', array());

    return $ret;
}

function renderPage2($tpl, $par)
{

    $ret = renderTpl($tpl, $par);

    $ret .= renderTpl('footer.twig', array());

    return $ret;
}


function ve($s = '', $f = false)
{
    return var_export($s, $f);
}

function v($str)
{
    var_dump($str);
}

function eN($v)
{
    return $v === null;
}

function eF($v)
{
    return $v === false;
}

function eFN($v)
{
    return ($v === false) or ($v === NULL);
}

function sp($arr, $key, $def = null)
{
    return isset($arr[$key]) ? $arr[$key] : $def;
}

function api__err_ajax($mess, $data = array())
{
    global $app;

    return $app->json(array(
        'product' => array(
            'message' => $mess,
            'data' => $data
        ),

        'ok' => false,
    ), 200);
}


function api_ajax($data, $code = 200)
{
    global $app;

    return $app->json(array(
        'product' => $data,
        'ok' => true
    ), $code);
}


/**
 * Генерируем исключение
 *
 * @param string $e
 * @throws Exception
 */
function crit($e = '')
{
    if ($e == '') {
        $e = 'empty exception';
    }

    throw new Exception($e);
}

;

function js_modules__handlebar_tmpls($modules)
{
    $ret = '';
    $basePath = BASEPATH . 'src/views/js_modules/';

    foreach ($modules as $module) {
        foreach (new DirectoryIterator($basePath . $module) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            //-=-=-=-=-=-

            list($baseName,) = explode('.', $fileInfo->getFilename());

            $tmplName = implode('_', array($module, $baseName));
            $content = file_get_contents($fileInfo->getPathname());

            $ret .= renderTpl('utils\handlebars_tmpl.twig', array(
                'tmplName' => $tmplName,
                'content' => $content
            ));
        }
    }

    return $ret;
}


function bnok($m = '', $data = array())
{
    return new \App\Bar\BarNok($m, $data);
}

function bok($d = array(), $m = '')
{
    return new \App\Bar\BarOk($d, $m);
}

/**
 * Подключить сервис на страницу
 *
 * @param $service
 * @return array
 */
function includeService($service)
{
    $folder = SRC . 'services/' . $service . '/';
    $files = scandir($folder);

    // load cnt, api
    foreach ($files as $file) {

        if (strpos($file, 'api_') === 0) {
            include_once $folder . $file;
        }

        if (strpos($file, 'cnt_') === 0) {
            include_once $folder . $file;
        }
    }

    if (file_exists($folder . 'helpers.php')) {
        include_once($folder . 'helpers.php');
    }

    $twig = '';

    return [
        'twig' => $twig
    ];
}


/**
 * Получить все файлы из каталога
 * @param $path
 * @return array
 */
function read_folder($path, $params = array())
{

    $ret = array();

    $folder_files = @scandir($path);

    if (is_array($folder_files)) {
        foreach ($folder_files as $file) {
            if ($file != '.' and $file != '..' and $file != '.svn') {
                $file_name = $path . '/' . $file;

                if (!sp($params, 'skip_recursion')) {
                    if (is_dir($file_name)) {
                        $ret = array_merge($ret, read_folder($file_name, $params));
                    } else {
                        $ret[] = $file_name;
                    }
                } else {
                    $isAdd = true;

                    if (is_dir($file_name)) {
                        if (sp($params, 'skip_folders')) {
                            $isAdd = false;
                        }
                    }

                    if ($isAdd) {
                        $ret[] = $file_name;
                    }
                }

            }
        }
    }

    return $ret;
}


/**
 * Копировать содержимое папки
 *
 * @param $source
 * @param $dest
 */
function copyr($source, $dest, $ignoreFolders = [])
{
    $ret = ['exclude' => []];

    foreach (
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST) as $item
    ) {
        $path = $iterator->getSubPathName();

        $isCopy = 1;
        foreach ($ignoreFolders as $folder) {
            if (strstr($path, $folder) !== false) {
                $isCopy = false;
                $ret['exclude'][] = $path;
                break;
            }
        }

        if ($isCopy) {
            if ($item->isDir()) {
                mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

    return $ret;
}


/**
 * Создает путь по строке что зададан
 *
 * folder1/folder2/folder3
 *
 * @param $folderPath
 */
function create_folder_tree_by_pathname($folderPath)
{
    $folderPath = str_replace('\\', '/', $folderPath);
    $folders = explode('/', $folderPath);
    if ($folderPath[0] == '/') {
        $path = '/';
    } else {
        $path = '';
    }

    foreach ($folders as $folder) {
        if (strlen($folder)) {
            $path .= $folder . '/';

            if (!file_exists($path)) {
                mkdir($path);
            }
        }
    }

}

function sprintf2($s, $data = array())
{
    $loop = 3;
    $ret = $s;

    $keys = array_map(function ($v) {
        return '[' . $v . ']';
    }, array_keys($data));

    do {
        $ret = str_replace($keys, $data, $ret);
        $is_need_again = !eF(strpos($ret, '['));

    } while ($loop-- && $is_need_again);

    return $ret;
}

function html_cut($text, $max_length)
{
    $tags = array();
    $result = "";

    $is_open = false;
    $grab_open = false;
    $is_close = false;
    $in_double_quotes = false;
    $in_single_quotes = false;
    $tag = "";

    $i = 0;
    $stripped = 0;

    $stripped_text = strip_tags($text);

    while ($i < strlen($text) && $stripped < strlen($stripped_text) && $stripped < $max_length) {
        $symbol = $text{$i};
        $result .= $symbol;

        switch ($symbol) {
            case '<':
                $is_open = true;
                $grab_open = true;
                break;

            case '"':
                if ($in_double_quotes)
                    $in_double_quotes = false;
                else
                    $in_double_quotes = true;

                break;

            case "'":
                if ($in_single_quotes)
                    $in_single_quotes = false;
                else
                    $in_single_quotes = true;

                break;

            case '/':
                if ($is_open && !$in_double_quotes && !$in_single_quotes) {
                    $is_close = true;
                    $is_open = false;
                    $grab_open = false;
                }

                break;

            case ' ':
                if ($is_open)
                    $grab_open = false;
                else
                    $stripped++;

                break;

            case '>':
                if ($is_open) {
                    $is_open = false;
                    $grab_open = false;
                    array_push($tags, $tag);
                    $tag = "";
                } else if ($is_close) {
                    $is_close = false;
                    array_pop($tags);
                    $tag = "";
                }

                break;

            default:
                if ($grab_open || $is_close)
                    $tag .= $symbol;

                if (!$is_open && !$is_close)
                    $stripped++;
        }

        $i++;
    }

    while ($tags)
        $result .= "</" . array_pop($tags) . ">";

    return $result;
}

/**
 * Простой список из бб
 * @param $bbIds
 * @return mixed
 */
function bbs__render_simple_list($bbIds)
{
    $bbs = [];

    foreach ($bbIds as $bbId) {
        $BB = new ClBBlock();
        $BB->BuildFromId($bbId);
        $bbs[$bbId] = $BB->toArray();
    }

    return renderTpl('BbExplorer/twig/simple_bb_list.twig', array(
        'bbs' => $bbs
    ));
}

function transliterate($string)
{
    $string = str_replace(' ', '-', $string);

    $roman = array("i", "i", "i", "i", "g", "g", "e", "e", "Sch", "sch", 'Yo', 'Zh', 'Kh', 'Ts', 'Ch', 'Sh', 'Yu', 'Ya', 'yo', 'zh', 'kh', 'ts', 'ch', 'sh', 'yu', 'ya', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', '', 'Y', '', 'E', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', '', 'y', '', 'e');
    $cyrillic = array("Ї", "ї", "І", "і", "Ґ", "ґ", "є", "Є",
        "Щ", "щ", 'Ё', 'Ж', 'Х', 'Ц', 'Ч', 'Ш', 'Ю', 'Я', 'ё', 'ж', 'х', 'ц', 'ч', 'ш', 'ю', 'я', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Ь', 'Ы', 'Ъ', 'Э', 'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'ь', 'ы', 'ъ', 'э');

    $ret = strtolower(str_replace($cyrillic, $roman, $string));
    $ret = preg_replace('/[^\w-]+/u', '', $ret);

    return $ret;
}
