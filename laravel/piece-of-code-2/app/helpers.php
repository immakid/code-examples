<?php
use App\Models\Users\UserGroup as Group;

if (!function_exists('flash')) {

	/**
	 * Flash messages helper.
	 *
	 * @param mixed $message
	 * @param mixed $title
	 * @return App\Acme\Libraries\Http\Flash
	 */
	function flash($message = false, $title = false) {

		$flash = app('flash');

		if (!func_num_args()) {
			return $flash;
		}

		return $flash->info($message, $title);
	}

}

if (!function_exists('assets')) {

	/**
	 * Assets helper. Can be used as injector
	 * or as assets printer.
	 *
	 * @param string|null $type
	 * @return string
	 */
	function assets($type = 'css') {

		if (!func_num_args()) {
			return app('assets');
		}

		$assets = [];
		foreach ((array)app('assets')->get($type) as $asset) {

			$html = ($type === 'css') ?
				'<link href="' . $asset . '" rel="stylesheet" />' :
				'<script type="text/javascript" src="' . $asset . '"></script>';

			array_push($assets, $html);
		}

		return implode("\n\t\t", $assets);
	}
}

if (!function_exists('user_can')) {

	/**
	 * @param string $permission
	 * @return bool
	 */
	function user_can($permission) {
		return app('acl')->hasPermission($permission);
	}
}

if (!function_exists('user_belongs_to')) {

	/**
	 * @param string|array $group
	 * @return bool
	 */
	function user_belongs_to($group) {

		if (is_array($group)) {
			return app('acl')->belongsToOneOf($group);
		}

		return app('acl')->belongsTo($group);
	}
}

if (!function_exists('fStr')) {

	/**
	 * @param string $string
	 * @param int $flags
	 * @return string
	 */
	function fStr($string, $flags = ENT_QUOTES) {
		return htmlspecialchars($string, $flags);
	}
}

if (!function_exists('__t')) {

	/**
	 * @param string $key
	 * @param array $replace
	 * @param bool $default
	 * @param string|null $env
	 * @return bool|string
	 */
	function __t(string $key, array $replace = [], $default = false, string $env = null) {

		$translation = (!$env) ?
			translate($key, $replace) :
			translate(sprintf("%s::%s", $env, $key), $replace, true);

		return $translation ?: ($default ?: false);

//		if (!$translation = translate($key, $replace)) {
//			return $default ? $default : false;
//		}
//
//		return $translation;
	}
}

if (!function_exists('__tF')) {

	/**
	 * @param string $key
	 * @param array $replace
	 * @param bool $default
	 * @return bool|string
	 */
	function __tF(string $key, array $replace = [], $default = false) {
		return __t($key, $replace, $default, 'frontend');
	}
}

if (!function_exists('__tB')) {

	/**
	 * @param string $key
	 * @param array $replace
	 * @param bool $default
	 * @return bool|string
	 */
	function __tB(string $key, array $replace = [], $default = false) {
		return __t($key, $replace, $default, 'backend');
	}
}

if (!function_exists('translate')) {

	/**
	 * @param string $key
	 * @param array $replace
	 * @param bool $strict
	 * @return array|bool|null|string
	 */
	function translate(string $key, array $replace = [], bool $strict = false) {

		$translator = app('translator');
		$env = config('environment', 'frontend');

		if (!$strict && substr($key, 0, strpos($key, '.')) !== $env) {
			$key = sprintf("%s::%s", $env, $key);
		}

		$translation = $translator->trans($key, $replace);

		return ($translation === $key) ? false : $translation;
	}
}

if (!function_exists('base_url')) {

	/**
	 *
	 * @param boolean $addon
	 * @return string
	 */
	function base_url($addon = false) {

		$root = request()->root();

		return $addon ? sprintf("%s/%s", $root, ltrim($addon, '/')) : $root;
	}

}

if (!function_exists('str2camel')) {

	/**
	 * @param string $input
	 * @param string|array $separators
	 * @return mixed
	 */
	function str2camel($input, $separators = '_') {

		$replace = array_fill(0, count((array)$separators), '');

		return str_replace((array)$separators, $replace, ucwords($input, implode((array)$separators)));
	}
}

if (!function_exists('reverse_camel')) {

	/**
	 * @param string $string
	 * @return string
	 */
	function reverse_camel($string) {
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
	}
}

if (!function_exists('url_title')) {

	/**
	 * @param string $string
	 * @return string
	 */
	function url_title($string) {

		$result = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $string));

		return strtolower(preg_replace('/-+/', '-', $result));
	}
}

if (!function_exists('force_return_to')) {

	/**
	 * @param string $url
	 * @param bool $force
	 */
	function force_return_to($url, $force = true) {

		if (!session('url.intended') || $force) {
			session()->flash('url.intended', $url);
		}
	}
}

if (!function_exists('force_return_back')) {

	/**
	 * @param bool $force
	 */
	function force_return_back($force = true) {
		force_return_to(app('request')->header('referer'), $force);
	}
}

if (!function_exists('intended_field')) {

	/**
	 * @return \Illuminate\Support\HtmlString|null
	 */
	function intended_field() {

		if (!$url = session('url.intended')) {
			return null;
		}

		return new \Illuminate\Support\HtmlString('<input type="hidden" name="_intended" value="' . base64_encode($url) . '">');
	}
}

if (!function_exists('selected')) {

	/**
	 * @param mixed $var1
	 * @param mixed $var2
	 * @param string $type
	 * @param bool $output
	 * @return bool|string
	 */
	function selected($var1, $var2, $type = 'select', $output = true) {

		switch ($type) {
			case 'checkbox':
				$output = 'checked="checked"';
				break;
			default:
				$output = 'selected="selected"';
		}

		if (
			(is_array($var1) && in_array($var2, $var1)) ||
			$var1 === $var2
		) {

			if (!$output) {
				return $output;
			}

			echo $output;
		}

		return false;
	}
}

if (!function_exists('cond_substr')) {

	/**
	 * Append "read more" mark (such as "..." or ">>") only if
	 * string is longer than $length.
	 *
	 * @param string $string
	 * @param int $length
	 * @param string $append
	 * @return string
	 */
	function cond_substr($string, $length = 200, $append = '...') {

		if (strlen(mb_convert_encoding($string, 'UTF-8', 'UTF-8')) > $length) {
			return sprintf("%s%s", rtrim(iconv_substr(mb_convert_encoding($string, 'UTF-8', 'UTF-8'), 0, $length), '.'), $append);
		}

		return $string;
	}
}

if (!function_exists('convert')) {

	/**
	 * @param mixed $size
	 * @return string
	 */
	function convert($size) {

		$i = floor(log($size, 1024));
		$unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
		return @round($size / pow(1024, $i), 2) . ' ' . strtoupper($unit[$i]);
	}
}

if (!function_exists('json_message')) {

	/**
	 * @param string $message
	 * @param string $type
	 * @param array $callbacks
	 * @return array
	 */
	function json_message($message, $type = 'success', array $callbacks = []) {

		$result = [
			'callbacks' => [],
			'messages' => [$type => $message]
		];

		if ($callbacks) {
			foreach ($callbacks as $method => $data) {
				$result['callbacks'][$method] = $data;
			}
		}

		return $result;
	}
}

if (!function_exists('array_prefix')) {

	/**
	 * @param array $array
	 * @param array $prefixes
	 * @return array
	 */
	function array_prefix(array $array, array $prefixes) {

		$results = [];
		foreach ($prefixes as $prefix) {
			$results[$prefix] = $array;
		}

		return array_dot($results);
	}

}

if (!function_exists('array_merge_internal')) {

	/**
	 * Merge array keys with it's values.
	 *
	 * @param array $array
	 * @param bool $reverse
	 * @param string $glue
	 * @return array
	 */
	function array_merge_internal(array $array, $reverse = false, $glue = ' ') {

		return array_map(function ($key, $value) use ($reverse, $glue) {

			if ($reverse) {
				return sprintf("%s%s%s", $value, $glue, $key);
			}

			return sprintf("%s%s%s", $key, $glue, $value);
		}, array_keys($array), array_values($array));
	}
}

if (!function_exists('array_keys_all')) {

	/**
	 * @param array $array
	 * @return array
	 */
	function array_keys_all(array $array) {

		$keys = [];
		foreach ($array as $key => $value) {

			array_push($keys, $key);
			if (is_array($value)) {
				$keys = array_merge($keys, array_keys_all($value));
			}
		}

		return $keys;
	}
}

if (!function_exists('array_keys_all_search')) {

	/**
	 * @param array $array
	 * @param mixed $target
	 * @param array $ignore
	 * @return mixed
	 */
	function array_keys_all_search(array $array, $target, array $ignore = []) {

		$values = [];
		foreach ($array as $key => $value) {

			if ($key === $target) {
				array_push($values, $value);
			}

			if (is_array($value)) {
				$values = array_merge($values, array_keys_all_search($value, $target, $ignore));
			}
		}

		return $values;
	}
}

if (!function_exists('array_search_key')) {

	/**
	 * @param array $array
	 * @param string $target
	 * @return array
	 */
	function array_search_key(array $array, $target) {

		foreach ($array as $key => $value) {
			if ($key === $target) {
				return $value;
			} else if (!$result = (is_array($value) ? array_search_key($value, $target) : false)) {
				continue;
			}

			return $result;
		}

		return [];
	}
}

if (!function_exists('string_strip_protocol')) {

	/**
	 * @param string $string
	 * @return mixed
	 */
	function string_strip_protocol($string) {

		static $regex = '/^https?:\/\//i';

		return preg_replace($regex, '', $string);
	}
}

if (!function_exists('get_protocol')) {

	/**
	 * @return string
	 */
	function get_protocol() {
		return app('request')->isSecure() ? 'https' : 'http';
	}
}

if (!function_exists('get_table_name')) {

	/**
	 * Return actual table name for
	 * provided model class|relation.
	 *
	 * @param mixed $model
	 * @return string
	 */
	function get_table_name($model) {

		if ($model instanceof Illuminate\Database\Eloquent\Relations\Relation) {
			return get_table_name(get_class($model->getRelated()));
		} else if ($model instanceof Illuminate\Database\Eloquent\Model) {
			return $model->getTable();
		}

		return (new $model)->getTable();
	}
}

if (!function_exists('get_table_column_name')) {

	/**
	 * Create `table`.`column` syntax (instead of just `column`)
	 * using Eloquent model.
	 *
	 * @param mixed $model
	 * @param string $column
	 * @return string
	 */
	function get_table_column_name($model, $column) {

		if (strpos($column, '.') !== false) {
			return $column;
		}

		return sprintf("%s.%s", get_table_name($model), $column);
	}
}

if (!function_exists('get_tables_from_query')) {

	/**
	 * Parse tables used in specific SQL query.
	 *
	 * @param string $sql
	 * @return array
	 */
	function get_tables_from_query($sql) {

		$matches = $matches2 = $matches3 = $matches4 = array_fill(0, 2, []);
		preg_match_all("/\s+from\s+`?([a-z\d_]+)`?/i", $sql, $matches); // FROM `table_name`
		preg_match_all("/\s+join\s+`?([a-z\d_]+)`?/i", $sql, $matches2); // JOIN `table_name`
		preg_match_all("/\s+`?([a-z\d_]+)`\.\*?/i", $sql, $matches3); // everything else under tilda
		preg_match_all("/\s+as\s+`?([a-z\d_]+)`?/i", $sql, $matches4); // AS `table_name_alias`

		if ($matches4[1]) {

			// Remove aliases
			$matches3[1] = array_filter($matches3[1], function ($value) use ($matches4) {
				return !in_array($value, $matches4[1]);
			});
		}

		return array_unique(array_filter(array_merge($matches[1], $matches2[1], $matches3[1])));
	}
}

if (!function_exists('get_class_short_name')) {

	/**
	 * Lowercase, namespace-free class name
	 *
	 * @param mixed $class
	 * @return string
	 */
	function get_class_short_name($class) {

		try {
			return strtolower((new ReflectionClass($class))->getShortName());
		} catch (Exception $e) {
			return false;
		}
	}
}

if (!function_exists('gen_random_string')) {

	/**
	 * @param int $length
	 * @param array|null $only
	 * @param array|null $except
	 * @return string
	 */
	function gen_random_string($length = 10, array $only = null, array $except = null) {

		$types = [
			'numbers' => range(0, 9),
			'lowercase' => range('a', 'z'),
			'uppercase' => range('A', 'Z')
		];

		$selected = array_filter($types, function ($key) use ($only, $except) {

			if ($only) {
				return in_array($key, $only);
			} else if ($except) {
				return !in_array($key, $except);
			}

			return true;
		}, ARRAY_FILTER_USE_KEY);

		$result = '';
		$chars = implode('', array_collapse($selected));

		for ($i = 0; $i < $length; $i++) {
			$result .= $chars[mt_rand(0, strlen($chars) - 1)];
		}

		return $result;
	}
}

if (!function_exists('gen_resource_names')) {

	/**
	 * Auto generate Route resources with
	 * custom prefix.
	 *
	 * @param string $prefix
	 * @param array $only
	 * @return array
	 */
	function gen_resource_names($prefix, $only = [], $except = []) {

		$results = [];
		$defaults = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

		if ($except) {
			$items = array_diff($defaults, $except);
		} else {
			$items = $only ? $only : $defaults;
		}

		foreach ($items as $item) {
			$results[$item] = sprintf("%s.%s", $prefix, $item);
		}

		return $results;
	}

}

if (!function_exists('gen_tmp_dir')) {

	/**
	 * Creates directory named after tempnam()
	 * (deletes created file)
	 *
	 * @return bool|string
	 */
	function gen_tmp_dir() {

		$path = tempnam(sys_get_temp_dir(), gen_random_string(3, ['lowercase']));

		unlink($path);
		mkdir($path);

		return $path;
	}
}

if (!function_exists('get_language_id')) {

    /**
     * @param string $permission
     * @return bool
     */
    function get_language_id($lang_code = null) {
        if(!$lang_code){
            $lang_code = config('app.locale');
        }
        return app('App\Models\Language')->where('code', $lang_code)->first()->id;
    }
}

if (!function_exists('print_logs_app')) {

	/**
	 * @param String $message
	 * @throws Exception
	 */
	function print_logs_app($message) {

		$message = "[".date("Y-m-d H:i:s", time()). "] DEBUG: " . $message."\n"; // Append next line 
        
        if (!file_exists("/tmp/wg_app1.logs")) {
            $file = fopen("/tmp/wg_app1.logs", "w");
            fwrite($file, $message);
        }
        file_put_contents("/tmp/wg_app1.logs", $message, FILE_APPEND);
    }
}

if (!function_exists('wg_rrmdir')) {

	function wg_rrmdir($dir) {
	    if (is_dir($dir)) {
	        $files = scandir($dir);
	        foreach ($files as $file)
	            if ($file != "." && $file != "..") wg_rrmdir("$dir/$file");
	        rmdir($dir);
	    }
	    else if (file_exists($dir)) unlink($dir);
	}

}

if (!function_exists('wg_copydir')) {
	
	function wg_copydir($src, $dst, $remove_dst = true) {
	    if (file_exists ( $dst ) && $remove_dst )
	        wg_rrmdir ( $dst );
	    if (is_dir ( $src )) {
	        if ($remove_dst) mkdir ( $dst );
	        $files = scandir ( $src );
	        foreach ( $files as $file )
	            if ($file != "." && $file != "..")
	            {
	            	wg_copydir ( "$src/$file", "$dst/$file" );
	            	// wg_rrmdir( "$src/$file" );
	            }
	    } else if (file_exists ( $src ))
	        copy ( $src, $dst );
	}

}



if (!function_exists('humanTiming')) {

	function humanTiming($time) {

		$time = time() - $time; // to get the time since that moment
		$tokens = array (
			31536000 => 'year',
			2592000 => 'month',
			604800 => 'week',
			86400 => 'day',
			3600 => 'hour',
			60 => 'minute',
			1 => 'second'
	   	);
		foreach ($tokens as $unit => $text) {
			if ($time < $unit) {
				continue;
			}

			$numberOfUnits = floor($time / $unit);
			return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
		}
	}
}

if (!function_exists('getEndDate')) {

	function getEndDate($start_date, $months_count)
	{
	    $date = date_create($start_date);
	    date_add($date, date_interval_create_from_date_string($months_count." months"));
	    return date_format($date, "Y-m-d");
	}
}


if (!function_exists('getAgreementTermInMonths')) {

	function getAgreementTermInMonths($term_count_in_months, $term_unit)
	{
    	if($term_unit == 'year') {
    		$term_count_in_months = $term_count_in_months * 12;
    	}

    	return $term_count_in_months;
	}
}

if (!function_exists('getServerIPwithPort')) {

    function getServerIPwithPort() {

		$serverIP = 'http://';

		if ( isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") {
			$serverIP = 'https://';
		}

		if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") {
		  	$serverIP .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
		} else {
		  	$serverIP .= $_SERVER["SERVER_NAME"];
		}

	 	return $serverIP;
	}
}

if (!function_exists('getSignedAgreementPdfUrlAndDate')) {

	function getSignedAgreementPdfUrlAndDate($column_name, $column_value) {

		$data['agmt_signed_pdf_url'] = '';
		$data['agmt_signed_date'] = '';
		$adm_agreement = app('App\Models\StoreLeads\AdmAgreement')->where($column_name, $column_value)->first();

		if (isset($adm_agreement->signed_pdf_url) && !empty($adm_agreement->signed_pdf_url)) {
			$data['agmt_signed_pdf_url'] = getServerIPwithPort().'/uploads/agreements/'.$adm_agreement->signed_pdf_url;

			if (!file_exists(public_path().'/uploads/agreements/'.$adm_agreement->signed_pdf_url)) {
				$data['agmt_signed_pdf_url'] = '';
			}

			if (isset($adm_agreement->signed_date) && !empty($adm_agreement->signed_date)) {
				$data['agmt_signed_date'] =   ' ( On '.date('F j, Y',strtotime($adm_agreement->signed_date)).' )';
			}
		}

		return $data;
	}
}

if (!function_exists('getLeadsDataByLoggedInUserGroups')) {

	function getLeadsDataByLoggedInUserGroups($user_id, $user_groups)
	{
		$logged_in_user_group = array();
		$storeleads = array();

		foreach ($user_groups as $key => $group) {
			$logged_in_user_group[] = $group->key;
		}

		if(in_array('wg_admin', $logged_in_user_group) || in_array('wg_sales', $logged_in_user_group)){
			$storeleads = StoreLead::orderBy('updated_at', 'desc')->get();
		} else if (in_array('wg_sales_rep', $logged_in_user_group)) {
			$storeleads = StoreLead::where('sales_rep_id', $user_id)->orderBy('updated_at', 'desc')->get();
		}

		return $storeleads;
		
	}
}

if (!function_exists('getUserGroupsByRole')) {

	function getUserGroupsByRole($user_role_key)
	{
		$drop_down_groups = array();

		if($user_role_key == 'wg_admin'){
              $drop_down_groups = Group::all();

        } else if ($user_role_key == 'wg_sales'){
            $drop_down_groups = Group::where('key', 'wg_sales')->orWhere('key', 'wg_sales_rep')->orWhere('key', 'store_admin')->orWhere('key', 'store_user')->get();

        } else if ($user_role_key == 'wg_sales_rep') {
            $drop_down_groups = Group::where('key', 'wg_sales_rep')->get();
        }

		return $drop_down_groups;
		
	}
}


if (!function_exists('getLoggedInUserGroupKey')) {

	function getLoggedInUserGroupKey($user_groups){

		foreach ($user_groups as $key => $group) {
			$logged_in_user_group[] = $group->key;
		}

		if (in_array('wg_admin', $logged_in_user_group)) {
			return "wg_admin";
		} else if(in_array('wg_sales', $logged_in_user_group)) {
			return "wg_sales";
		} else if(in_array('wg_sales_rep', $logged_in_user_group)) {
			return "wg_sales_rep";
		} else {
			return false;
		}
	}
}
