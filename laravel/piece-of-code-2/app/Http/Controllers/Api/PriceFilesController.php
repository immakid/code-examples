<?php

namespace App\Http\Controllers\Api;
use Log;
use App\Acme\Repositories\Criteria\Limit;
use App\Acme\Repositories\Criteria\Where;
use App\Acme\Repositories\Criteria\In;
use App\Models\PriceFiles\PriceFileImage;
use App\Jobs\PriceFiles\RefreshCache;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Jobs\PriceFiles\Unzip;
use App\Jobs\PriceFiles\Cleanup;
use App\Models\PriceFiles\PriceFile;
use App\Acme\Repositories\Criteria\Status;
use App\Acme\Repositories\Criteria\OrderBy;
use App\Jobs\PriceFiles\Job as PriceFileJob;
use App\Acme\Repositories\Interfaces\PriceFiles\PriceFileInterface;

class PriceFilesController extends ApiController {

	/**
	 * @var PriceFileInterface
	 */
	protected $priceFile;

	/**
	 * @var integer
	 */
	protected $maxTimeToResetPrisfileStatus = 4; // Time in hours

	public function __construct(PriceFileInterface $priceFile) {
		$this->priceFile = $priceFile;
	}

	/**
	 * @return array
	 */
	public function index($prisfile_id = null, $is_check_crc = null, PriceFile $priceFile = null) {

		
		$query = $this->priceFile->setCriteria([
			new Status(['new', 'active', 'in_progress', 'disabled_api', 'active_error']),
			new OrderBy('data_touched_at', 'ASC'),
		]);

		if($prisfile_id){
			
			$query->setCriteria(new In($prisfile_id));
			
			if ($is_check_crc == "false") {

				print_logs_app("is_check_crc is false for Prisfile_ID:".$prisfile_id);
				
				$delete_pending_images = PriceFileImage::where('price_file_id', $prisfile_id)->delete();
				
				dispatch(new RefreshCache($priceFile, array()));
			} else {
				print_logs_app("is_check_crc is true for Prisfile_ID:".$prisfile_id);
			}
		}


		$results = [];
		
		foreach ($query->all() as $item) {

			if (!$prisfile_id){
			
				if (!$file = $this->checkFile($item)) {
					continue;
				}
				$results[$item->id] = $file->prepareApiData();
			
			} else {
			
				$results[$item->id] = $item->prepareApiData();
			
			}

		}

		return $results;
	}

	/**
	 * Download local files
	 *
	 * @param PriceFile $priceFile
	 * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function show(PriceFile $priceFile) {

		$path = $priceFile->localFileName;
		$name = sprintf("%s-%s", $priceFile->store->domain, basename($path));

		if (file_exists($path)) {

			return response()->download($path, $name, [
				'X-Sendfile' => $path,
				'Content-Disposition' => sprintf("attachment; filename=%s", sprintf('"%s"', addcslashes($name, '"\\')))
			]);
		}

		return response(null, 404);
	}

	/**
	 * @param Request $request
	 * @param PriceFile $priceFile
	 */
	public function update(Request $request, PriceFile $priceFile) {

		$actions = $request->input('actions');

		if (isset($actions['on_demand_pricefile'])) {

			return $this->index($priceFile->id, $actions['is_check_crc'], $priceFile);
		}

		$priceFile->writeApiLog("Update request received", $request->all(), 'debug', 'APIController');

		print_logs_app("Prisfile-ID-".$priceFile->id."- Prisfile Sync - update before jobs");

		if (($jobs = $this->gatherRequestedJobs($request, $priceFile))) {
			print_logs_app("Jobs Count:".sizeof($jobs));
			Unzip::withChain($jobs)->dispatch($priceFile)->onConnection($priceFile->queue);
		}

		print_logs_app("Prisfile-ID-".$priceFile->id."- Prisfile Sync - update after jobs");

		foreach ($request->input('actions', []) as $action => $data) {

			$method = sprintf("handle%sAction", str2camel($action, '-'));

			print_logs_app("Prisfile-ID-".$priceFile->id."- Prisfile Sync Action - ".$method);

			if (method_exists($this, $method)) {
				$this->{$method}($priceFile, $data, $request);
			}
		}
	}

	/**
	 * @param PriceFile $file
	 * @return PriceFile|bool
	 */
	protected function checkFile(PriceFile $file) {
		
		//print_logs_app("Prisfile_ID:".$file->id." hrStatus:".$file->hrStatus);
		
		switch ($file->hrStatus) {
			
			case 'active':

				$interval = $file->interval * 60; // seconds
				$time = $file->api_touched_at ? $file->api_touched_at->format('U') : false;

				if ($time && (time() - $time < $interval) && $file->pendingImages->isEmpty()) {
					return false;
				}

				break;

			case 'in_progress':
				
				//print_logs_app("strtotime ------>".strtotime(substr($file->api_touched_at,0,10)));
				//print_logs_app("current -------->".strtotime(date("Y-m-d")));
				//print_logs_app("greater than -------->".strtotime(substr($file->api_touched_at,0,10))>strtotime(date("Y-m-d")));

				// print_logs_app("Prisfile_ID:".$file->id." Prisfile substr:".substr($file->api_touched_at,11));
				
				$api_touched_at = strtotime(substr($file->api_touched_at,11));
				
				$current_time = strtotime(date("H:i:s"));
				
				$difference_in_hours = round(abs($current_time - $api_touched_at) / 3600,2);
				
				print_logs_app("Prisfile_ID:".$file->id." Prisfile difference_in_hours:".$difference_in_hours);
				
				if ( ( $difference_in_hours >= $this->maxTimeToResetPrisfileStatus &&  $file->api_touched_at ) || 
					( strtotime(substr($file->api_touched_at,0,10)) < strtotime(date("Y-m-d"))) ) {

					print_logs_app("Prisfile_ID:".$file->id." status is RESETED");
				
					$this->handleUpdateStatusAction($file , array('status' => "_previous"), false);
				
				} else {
					print_logs_app("Prisfile_ID:".$file->id." is IGNORED");
					return false;
				}

				break;
		}

		return $file;
	}

	/**
	 * @param Request $request
	 * @param PriceFile $priceFile
	 * @return array
	 */
	protected function gatherRequestedJobs(Request $request, PriceFile $priceFile) {

		$jobs = $sections = [];

		print_logs_app("Prisfile-ID-".$priceFile->id."- Prisfile Sync - Start - gatherRequestedJobs()");

		print_logs_app("Prisfile-ID-".$priceFile->id."- Prisfile Sections- ".print_r($request->input('sections'), true));

		foreach ($request->input('sections', []) as $section) {
			if (!$job = config(sprintf("cms.price_files.jobs.%s", $section))) {
				continue;
			}

			print_logs_app("Prisfile-ID-".$priceFile->id."- Section");

			$job = new $job($priceFile);
			if ($job instanceof PriceFileJob) {
				$job->onConnection($priceFile->queue);
			}

			array_push($jobs, $job);
			array_push($sections, $section);
		}

		print_logs_app("Prisfile-ID-".$priceFile->id."- Jobs are pushed");

		if ($jobs) {
			array_push($jobs, (new Cleanup($priceFile, $sections))->onConnection($priceFile->queue));
		}

		print_logs_app("Prisfile-ID-".$priceFile->id."- End - gatherRequestedJobs()");
		return $jobs;
	}

	/**
	 * @param PriceFile $priceFile
	 * @param array $data
	 * @return bool
	 */
	protected function handleUpdateStatusAction(PriceFile $priceFile, array $data = [], $touch = true) {
		// Log::debug("In handleUpdateStatusAction");
		// Log::debug("Status ---->".Arr::get($data, 'status'));
		
		// error_log("In handleUpdateStatusAction");
		// error_log("Status ---->".Arr::get($data, 'status'));
		$current_status=Arr::get($data, 'status');
		
		print_logs_app("Prisfile-ID-".$priceFile->id."- Start - handleUpdateStatusAction()");
		print_logs_app("Prisfile-ID-".$priceFile->id."- Status1 ---->".$current_status);
		
		if (!$status = Arr::get($data, 'status')) {
			return false;
		} else if ($status === '_previous') {
			$status = $priceFile->determineEnabledStatus();
		}

		$priceFile->setStatus($status);
		if ( $touch )
			$priceFile->touchTs('api');
		print_logs_app("Prisfile-ID-".$priceFile->id." - END -  handleUpdateStatusAction()");

	}

	/**
	 * @param PriceFile $priceFile
	 * @param array $data
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	protected function handleLogEventAction(PriceFile $priceFile, array $data = []) {
		return $priceFile->logs()->create($data);
	}
}
