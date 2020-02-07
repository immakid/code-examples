<?php

namespace App\Http\Controllers\App;

use Image;
use Exception;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class MediaController extends Controller {

	public function show($path) {

		try {

			$quality = 85;
			$file = new File(public_path(sprintf("uploads/%s", $path)));
			$instance = Image::make($file);

			switch($file->getExtension()) {
				case 'png':
					$quality = 50;
					$instance =  Image::canvas($instance->width(), $instance->height(), '#ffffff')->insert($instance);
					break;
			}

			return $instance->stream('jpg', $quality);
		} catch (FileNotFoundException $e) {
			return response(null, 404);
//			return redirect()->route('app.home');
		} catch (Exception $e) {
			return response(null, 404);
//			return redirect()->route('app.home');
		}
	}
}
