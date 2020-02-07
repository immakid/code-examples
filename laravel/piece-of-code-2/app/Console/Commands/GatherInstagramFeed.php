<?php

namespace App\Console\Commands;

use finfo;
use Illuminate\Console\Command;
use Vinkla\Instagram\Instagram;

class GatherInstagramFeed extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'instagram:gather';

	/**
	 * @var string
	 */
	protected $description = 'Gather photos from Instagram feed.';

	/**
	 * http://instagram.pixelunion.net/
	 *
	 * @var string
	 */
	protected static $token = '1834850994.1677ed0.9a6a8d167c264ba3a2a2999890bc57b8';

	/**
	 * @return mixed
	 */
	public function handle() {

		$results = [];
		foreach ((new Instagram(self::$token))->get() as $item) {

			$data = file_get_contents($item->images->thumbnail->url);
			$mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($data);

			array_push($results, sprintf("data: %s;base64,%s",$mime, base64_encode($data)));
		}

		file_put_contents(config('cms.paths.instagram'), json_encode($results));
	}
}
