<?php

namespace App\Http\Controllers\Backend\System;

use GuzzleHttp\Client;
use App\Models\Media;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use Symfony\Component\Finder\Finder;
use App\Models\PriceFiles\PriceFile;
use App\Models\PriceFiles\PriceFileImage;
use App\Models\PriceFiles\PriceFileColumn;
use App\Jobs\PriceFiles\RefreshCache;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Http\Requests\System\PriceFiles\SubmitPriceFileFormRequest;
use App\Http\Requests\System\PriceFiles\SubmitPriceFileOnDemandImportFormRequest;
use App\Http\Requests\System\PriceFiles\SubmitPriceFileMappingsFormRequest;

class PriceFilesController extends BackendController {

	use Holocaust;

	/**
	 * @var array
	 */
	protected $queues = [];

	/**
	 * @var string
	 */
	protected static $holocaustModel = PriceFile::class;

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {

		return view('backend.system.price-files.index', [
			'items' => PriceFile::with([
				'store' => function (Relation $relation) {
					$relation->withCount('products');
				}
			])->get()
		]);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function create() {
		assets()->injectPlugin('bs-fileupload');

		return view('backend.system.price-files.create', [
			'formats' => config('cms.price_files.formats'),
			'sources' => config('cms.price_files.sources'),
			'stores' => Store::doesnthave('priceFile')->get(),
			'queues' => config('queue.list', [])
		]);
	}

	/**
	 * @param PriceFile $priceFile
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function edit(PriceFile $priceFile) {
		assets()->injectPlugin('bs-fileupload');

		$logsApi = $priceFile->logs()->comingFrom('api')->orderBy('created_at', 'DESC')->get();
		$logsLocal = $priceFile->logs()->comingFrom('local')->orderBy('created_at', 'DESC')->get();

		// print_logs_app("PriceFileColumn all ".print_r(PriceFileColumn::all(),true));

		return view('backend.system.price-files.edit', [
			'item' => $priceFile,
			'columns' => PriceFileColumn::all(),
			'formats' => config('cms.price_files.formats'),
			'sources' => config('cms.price_files.sources'),
			'local' => (!$priceFile->isRemote && file_exists($priceFile->localFileName)) ? Media::fromFile($priceFile->localFileName) : false,
			'logs' => [
				'api' => $logsApi->isEmpty() ? [] : $logsApi,
				'local' => $logsLocal->isEmpty() ? [] : $logsLocal
			],
			'reports' => $this->gatherApiReports($priceFile),
			'images' => $priceFile->pendingImages,
			'queues' => config('queue.list', [])
		]);
	}

	/**
	 * @param SubmitPriceFileFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(SubmitPriceFileFormRequest $request) {
		$file = new PriceFile($request->all());
		if ($file->saveRelationsFromRequest($request)->save()) {

			if (!$file->url) {
				if (!$this->saveLocalFile($request, $file)) {
					flash()->error(__t('messages.error.saving'));
				}
			}

			flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.price_file')]));
			return redirect()->route('admin.system.price-files.edit', [$file->id]);
		}

		flash()->error(__t('messages.error.saving'));
		return redirect()->back();
	}

	/**
	 * @param SubmitPriceFileFormRequest $request
	 * @param PriceFile $priceFile
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \Exception
	 */
	public function update(SubmitPriceFileFormRequest $request, PriceFile $priceFile) {

        $data = $request->input('data', []);
        $data['extra']['missing_columns'] = (!isset($data['extra']['missing_columns'])) ? 0 : $data['extra']['missing_columns'];
        $data['extra']['pricefiles_handle_row_sep_field'] = (!isset($data['extra']['pricefiles_handle_row_sep_field'])) ? 0 : $data['extra']['pricefiles_handle_row_sep_field'];
        
		$instance = $priceFile->setBooleanRelationsFromRequest($request);
		$instance = $instance->dataUpdate($data);

		if ($instance->update(Arr::except($request->all(), 'data'))) {
			$priceFile->{($request->input('enabled') ? 'enable' : 'disable')}();

			if (!$priceFile->url) {
				$this->saveLocalFile($request, $priceFile);
			}

			foreach (array_keys($request->input('delete', [])) as $key) {
				switch ($key) {
					case 'maps':
						foreach ($priceFile->maps as $item) {
							$item->delete();
						}
						
						print_logs_app("Came to delete maps");
						print_logs_app("Prisfile-ID-".$priceFile->id);

						$delete_pending_images = PriceFileImage::where('price_file_id', $priceFile->id)->delete();
						
						dispatch(new RefreshCache($priceFile, array()));
						print_logs_app("RefreshCache for Prisfile-ID-".$priceFile->id);
		                $priceFile->setStatus('new');
						flash()->success(__t('messages.success.deleted', ['object' => __t('messages.objects.column_mappings')]));
						break;
					case 'logs':

						foreach ($priceFile->logs as $item) {
							$item->delete();
						}

						$this->deleteApiReports($priceFile);

						flash()->success(__t('messages.success.deleted', ['object' => __t('messages.objects.logs')]));
						break;
				}
			}

			flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.price_file')]));
		} else {
			flash()->error(__t('messages.error.saving'));
		}

		return redirect()->back();
	}

	/**
	 * @param SubmitPriceFileOnDemandImportFormRequest $request
	 * @param PriceFile $priceFile
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function OnDemandImport(SubmitPriceFileOnDemandImportFormRequest $request, PriceFile $priceFile) {
		
		$is_check_crc = "true";
		
		foreach ($request->input('import_data', []) as $key => $value) {
			if ($key == "on_demand_ignore_check_crc") $is_check_crc = "false";
		}
		
		$api_uri = getenv("API_URL");
		
		if( $api_uri && $priceFile->id){

			$client = new Client();
			$api_uri = $api_uri.config('cms.api.on_demand_prisfile_import');
			
			$response = $client->post($api_uri,
			    array(
			    	'verify' => false,
			       	'form_params' => array(
			            'prisfile_id' => $priceFile->id,
			            'is_check_crc' => $is_check_crc
			        )
			    )
			);
			
			// print_logs_app("Status code -".$response->getStatusCode()); // 200
			// print_logs_app("API Body - ".$response->getBody());
			
			if($response->getStatusCode() == 200){
				flash()->success("On Demand Prisfile import is successfully initiated");
			} else {
				flash()->error("On Demand Prisfile import is not able connect to scraping server");
			}

		} else {
			flash()->error("On Demand Prisfile import can not initiated API URL or PrisFile ID is invalid");
		}
		
		return redirect()->back();
	}

	/**
	 * @param SubmitPriceFileMappingsFormRequest $request
	 * @param PriceFile $priceFile
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function updateMappings(SubmitPriceFileMappingsFormRequest $request, PriceFile $priceFile) {

		$ids = [];
		$existing = Arr::pluck($priceFile->maps->toArray(), 'id');

		foreach ($request->input('maps', []) as $key => $value) {
			if (!$id = Arr::get($value, 'id')) {
				continue;
			} else if (!$map = $priceFile->maps->find($id)) {
				continue;
			} else {

				if (is_array($id)) {
					foreach ($id as $id_value) {
						$map = $priceFile->maps->find($id_value);
						array_push($ids, $id_value);
						$map->column()->associate(PriceFileColumn::find($key));
						$map->update(Arr::except($value, 'id'));
					}
				} else {
					array_push($ids, $id);
					$map->column()->associate(PriceFileColumn::find($key));
					$map->update(Arr::except($value, 'id'));
				}
			}
			// print_logs_app("Key - ".$key." id got is ". print_r($id,true));
		}

		$diff = array_diff($existing, $ids);
		foreach ($priceFile->maps()->has('column')->whereIn('id', $diff)->get() as $map) {

			$map->column()->dissociate();
			$map->update();
		}

		if ($priceFile->isEnabled) {
			$priceFile->setStatus($priceFile->determineEnabledStatus());
		}

		flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.column_mappings')]));
		return redirect()->back();
	}

	/**
	 * @param SubmitPriceFileFormRequest $request
	 * @param PriceFile $priceFile
	 * @return bool|int
	 */
	protected function saveLocalFile(SubmitPriceFileFormRequest $request, PriceFile $priceFile) {

		if (!$upload = $request->file('media.file')) {
			return false;
		}

		$data = file_get_contents($upload->getPathname());
		return file_put_contents($priceFile->localFileName, $data);
	}

	/**
	 * @param PriceFile $priceFile
	 * @return array
	 */
	protected function gatherApiReports(PriceFile $priceFile): array {

		$results = [];
		$dir = sprintf("%s/%d", rtrim(config('cms.paths.price_files.queue')), $priceFile->id);

		if (is_dir($dir)) {

			foreach ((new Finder())->files()->name("*.log")->in($dir)->getIterator() as $file) {
				$path = $file->getPathName();

				$name = basename($path);
				$results[substr($name, 0, strrpos($name, '.'))] = file_get_contents($path);
			}
		}

		return array_reverse($results, true);
	}

	/**
	 * @param PriceFile $priceFile
	 */
	protected function deleteApiReports(PriceFile $priceFile): void {

		$dir = sprintf("%s/%d", rtrim(config('cms.paths.price_files.queue')), $priceFile->id);

		if (is_dir($dir)) {
			foreach ((new Finder())->files()->name("*.log")->in($dir)->getIterator() as $file) {
				@unlink($file->getPathName());
			}
		}
	}
}
