<?php

namespace App\Jobs\PriceFiles;

use Illuminate\Support\Arr;
use App\Models\Media;
use App\Models\MediaRelations;
use App\Models\PriceFiles\PriceFileImage;
use Symfony\Component\HttpFoundation\File\File;

class ImportImages extends Job {

	public function handle() {

		$this->handleProxy(function () {

			$images = [];
			
			foreach ($this->getImageDirectories() as $file) {

				$product_id = basename($file->getPathname());

				if(!$model = PriceFileImage::where('product_id', $product_id)->first()) {

					print_logs_app("No Prisfile Image found for this folder - ".$product_id);
					continue;
				}
				else {
					
					//$media_realtions = MediaRelations::where("related_id",$product_id)->get();
					$media_realtions = MediaRelations::where("related_id",$product_id)->where("key","products")->get();

					$media_ids = Arr::pluck($media_realtions,'media_id');
					
					print_logs_app("media_ids - ".print_r($media_ids,true));

					#MediaRelations::where("related_id",$product_id)->delete();
					MediaRelations::where("related_id",$product_id)->where("key","products")->delete();
					
					foreach ($media_ids as $media_id) {
						
						if ($media_id) {
						
							Media::where(function ($query) use($media_id) {
							    	$query->where('id', $media_id)
							        ->orWhere('parent_id',$media_id)
							        ->delete();
							});
						}
					}
					//@unlink($source);
				}
				$dirname = dirname($file->getPathname());
				$source = $dirname."/".$product_id;
				$destination = config('cms.paths.uploads')."/products/".$product_id;
				
				wg_rrmdir($destination);
				
				$original_image_array = preg_grep('~^'.$product_id.'-.*~', scandir($source));
				$original_image = NULL;
				
				// print_logs_app("original_image_array - ".print_r($original_image_array,true));

				$label = 1;

				foreach ($original_image_array as $key => $name) {
					
					if(!empty($name))
						$original_image = $name;
					
					if ($original_image == NULL) 
						continue;
					
					// $model->delete();

					$media = Media::fromFile("$source/$original_image");
					$media->setDesignator(sprintf("products/%s", $product_id));
					
					if ($label > 1) {
						$media->label = (string)$label;
						// print_logs_app("---------------->Initialised label with ".print_r($media->label,true) . " label - ".$label);
					} else{
						$media->label = NULL;
					}
					$media->save();

					$parent_id = $media->id;
					
					wg_copydir($source, $destination, false);

					$files = scandir ( $destination );

					$files = preg_grep('~^.*'.$original_image.'.*~', $files); // Getting only current image's files
					
			        foreach ( $files as $file ){
			        
			            if ($file != "." && $file != ".." && !(preg_grep('~^'.$original_image.'.*~', [$file])) )
			        	{
		        			$child_media = Media::fromFile("$destination/$file",$media->label);
		        			
		        			if ( !isset($child_media->data->requested) ) {
			        			$child_media->parent_id = $parent_id;
						        $child_media->save();
		        			} else {
		        				print_logs_app("Not a resized image");
		        			}
						}
					}

					$update_media_relations = MediaRelations::updateOrCreate(['media_id' => $parent_id], ['media_id' => $parent_id, 'related_id' => $product_id, 'key' => 'products']);

					$original_image_name = explode("-", $original_image);

					if(isset($original_image_name[1]) && $original_image_name[1]){

						$price_file_image_id = explode("_", $original_image_name[1]);

						if (isset($price_file_image_id[0]) && $price_file_image_id[0]) {

							$delete_pending_image = PriceFileImage::where('id', $price_file_image_id[0])->delete();

						} else{

							print_logs_app("Not Found price_file_image_id[0] - ".print_r($price_file_image_id,true));
						}
					
					} else{

						print_logs_app("Not Found original_image_name[1] - ".print_r($original_image_name,true));
					}

					$label++;
				}
				
				foreach(PriceFileImage::where('product_id', $product_id)->get() as $image) {
					array_push($images, $image->url);
					$image->delete();
				}
			}
			if (isset($dirname)) {
				wg_rrmdir($dirname);
				@unlink($dirname);
			}


			if($images) {
				$this->file->writeLocalLog(sprintf("Failed to download %d images", count($images)), $images, 'warning');
			}

		}, [], ['media']);
	}
}
