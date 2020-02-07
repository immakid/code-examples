<?php

namespace App\Jobs\PriceFiles;

use RuntimeException;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Models\Category;
use App\Models\Products\Product;

class DisableDeletedRows extends Job {

	public function handle() {

		$this->handleProxy(function () {

			$prisfile_id = $this->file->id;

			if (!$path = $this->getSectionPath('deleted_rows')) {
				throw new RuntimeException("Missing section file for 'deleted_rows'");
			}

			$disable_deleted_products = $this->readPath($path);
			
			print_logs_app("Disable deleted products count - ".sizeof($disable_deleted_products));
			
			if ( sizeof($disable_deleted_products) > 0 ){

				if( isset( $disable_deleted_products['disable_all_products'] ) && ( $disable_deleted_products['disable_all_products'] == true ) ) {
					
					$store = $this->file->store;
					
					print_logs_app("Disable_all_products is true for Prisfile_ID:".$prisfile_id." and with store_id ".$store->id);
					
					$disable_all_products = Product::where('store_id', $store->id)
				  		->update(
				  			// ['enabled' => 0],
					    	['in_stock' => 0],
					    	['updated_at' => date('Y-m-d H:i:s')]
					    );
					$this->handleCategories($this->file->store);

				} else {
					print_logs_app("disable_all_products is not set for Prisfile_ID:".$prisfile_id);
					$this->file->writeLocalLog(
						sprintf("Found %d deleted products from updated price file", count($disable_deleted_products)),
						[], 'info', $this->getName()
					);
					
					print_logs_app("Deleted products in DisableDeletedRows -".print_r($disable_deleted_products,true));

					foreach ($disable_deleted_products as $internal_id) {

						$disable_deleted_rows = Product::where('internal_id', $internal_id)
							// ->where('enabled',1)
					  		->update(
					  			// ['enabled' => 0],
						    	['in_stock' => 0],
					    		['updated_at' => date('Y-m-d H:i:s')]
						    );
					}
				}
			}

		});
	

	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	protected function handleCategories(Store $store) {

		$categories = $store->categories()
			->without(['translations'])
			->select('id')
			->get();

		$this->file->writeLocalLog(
			sprintf("Found %d categories in database and collected %d from price file", count($categories), $this->file->id),
			[], 'debug', $this->getName()
		);
	
		$category_ids = Arr::pluck($categories, 'id');
		
		foreach ($category_ids as $id) {
				$delete_category = Category::where('id', $id)->delete();
		}

		$this->file->writeLocalLog(sprintf("Deleted %d categories", count($category_ids)), $category_ids, 'info', $this->getName());

		return $this;
	}
}