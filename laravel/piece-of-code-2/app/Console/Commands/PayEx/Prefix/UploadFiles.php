<?php

namespace App\Console\Commands\PayEx\Prefix;

use Artisan;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use App\Acme\Repositories\Interfaces\StoreInterface;

class UploadFiles extends Command {

	/**
	 * @var string
	 */
	protected $signature = 'payex:sync';

	/**
	 * @var string
	 */
	protected $description = 'Sync queued Prefix files with PayEx server.';

	/**
	 * @var \Illuminate\Filesystem\FilesystemAdapter
	 */
	protected $ftp;

	/**
	 * @var StoreInterface
	 */
	protected $store;

	public function __construct(StoreInterface $store) {
		parent::__construct();

		$this->store = $store;
		$this->ftp = Storage::disk('ftp.payex');
	}

	/**
	 * @return mixed
	 */
	public function handle() {

		$files = new Finder();
		$dir_sent = config('cms.paths.payex.sent');
		$dir_queue = config('cms.paths.payex.queue');
		$stores = $this->store->ignoreDefaultCriteria()->all();

		foreach ($files->name("*.xml")->in($dir_queue)->files() as $file) {
			if (!$instance = $this->handleFile($file)) {

				$this->error("[!] Sync failed, freak out...");

				return 1;
			}

			$name = $instance->getBasename();
			if ($instance->move($dir_sent)) {

				$this->handleStores($stores, $name);
				$this->line(sprintf("[+] Synced: %s", $name));

				Artisan::call('cache:clear-specific', ['--group' => 'queries', '--table' => [
					'stores'
				]]);
			}
		}
	}

	/**
	 * @param Collection $stores
	 * @param string $fileName
	 */
	protected function handleStores(Collection $stores, $fileName) {

		foreach ($stores as $store) {

			if (
				!$store->enabled &&
				$store->canBeEnabled &&
				$store->data('payex.xml') == $fileName &&
				$store->data('payex.cron.activate')
			) {

                if($store->sync){
                    $this->line("[-] PayEx Sync Disabled #$store->id: $store->name");
                }else{
                    $store->enabled = true;
                    $store->dataUpdate([
                        'payex' => [
                            'synced' => true
                        ]
                    ])->save();

                    $this->line(sprintf("[i] Enabled store %s (%d)", $store->name, $store->id));
                }
			}
		}
	}

	/**
	 * @param SplFileInfo $file
	 * @return bool|File
	 */
	protected function handleFile(SplFileInfo $file) {

		$file = new File($file->getRealPath());
		if (config('app.env') !== 'production') {

			$this->warn("ENV !== production - file has not been uploaded.");
			return $file;
		}

		try {

			if (!$this->ftp->put(sprintf("incoming/%s", $file->getBasename()), file_get_contents($file->getRealPath()))) {
				return false;
			}

			return $file;
		} catch (Exception $e) {
			return false;
		}
	}
}
