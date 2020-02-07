<?php

namespace App\Console\Commands\PayEx\Prefix;

use Artisan;
use Exception;
use DOMDocument;
use SimpleXMLElement;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use App\Acme\Repositories\Interfaces\StoreInterface;

class GenerateFile extends Command {

    /**
     * @var string
     */
    protected $signature = 'payex:generate';

    /**
     * @var string
     */
    protected $description = 'Generate PayEx Prefix file if change(s) has been detected';

    /**
     * @var StoreInterface
     */
    protected $store;

    public function __construct(StoreInterface $store) {
        parent::__construct();

        $this->store = $store;
    }

    /**
     * @return void
     */
    public function handle() {

        $items = $hashes = $stores = [];
        foreach ($this->store->ignoreDefaultCriteria()->all() as $store) {

            if (!$data = $this->store->getPayExData($store)) {

                $this->line("[-] Missing PayEx Data #$store->id: $store->name");
                continue;
            }

            if($store->sync){
                $this->line("[-] PayEx Sync Disabled #$store->id: $store->name");
                continue;
            }else{

                list($fields, $hash) = $data;

                array_push($hashes, $hash);
                array_push($items, $fields);
                array_push($stores, $store);
            }
        }

        $hash = sha1(implode($hashes));
        $hash_file = config('cms.paths.payex.hash_file');

        if (file_exists($hash_file) && file_get_contents($hash_file) === $hash) {

            $this->line("[i] No changes detected, no sync needed.");
            return;
        }

        if (!$items) {

            $this->line("[i] Nothing to do :(");
            return;
        }

        try {

            $this->line("[i] Generating XML...");
            $name = $this->generateXmlFile($items);

            foreach ($stores as $store) {

                $store->dataUpdate([
                    'payex' => [
                        'xml' => $name, // latest version of XML containing store's info
                    ]
                ])->save();
            }

            Artisan::call('cache:clear-specific', ['--group' => 'queries', '--table' => [
                'stores'
            ]]);

            file_put_contents($hash_file, $hash);
            $this->line("[i] Writing new hash $hash");
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

    }

    /**
     * @param array $items
     */
    protected function generateXmlFile(array $items) {

        $finder = new Finder();
        $dir_sent = config('cms.paths.payex.sent');
        $dir_queue = config('cms.paths.payex.queue');

        /**
         * Generate XML Data
         */

        $xml = new SimpleXMLElement(
            file_get_contents(
                config('cms.paths.payex.xml-skeleton_file')
            )
        );

        $this->populateSimpleXmlElement($xml->Organizations, $items);

        /**
         * Compute the name & save
         */

        $number = $finder->in([$dir_sent, $dir_queue])->name("*.xml")->count();
        $name = sprintf("Gggg_15522_%s_%03d.xml", date('YmdHi'), $number);
        $path = sprintf("%s/%s", $dir_queue, $name);

        $dom = new DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        file_put_contents($path, $dom->saveXML());
        $this->line("[+] XML Written to $path");

        return $name;
    }


    /**
     * @param SimpleXMLElement $element
     * @param array $items
     */
    protected function populateSimpleXmlElement(SimpleXMLElement $element, array $items) {

        foreach ($items as $fields) {

            $holder = $element->addChild('Organization');
            foreach ($fields as $key => $value) {
                $holder->addChild($key, htmlspecialchars($value, ENT_QUOTES));
            }

            $this->line("[+] " . implode('; ', $fields));
        }
    }
}
