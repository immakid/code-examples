<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Acme\Interfaces\Parsers\CsvPriceFileParserInterface;
use App\Acme\Interfaces\Parsers\XmlPriceFileParserInterface;
use App\Acme\Libraries\Parsers\PriceFiles\CsvPriceFileParser;
use App\Acme\Libraries\Parsers\PriceFiles\XmlPriceFileParser;

class PriceFileParsersProvider extends ServiceProvider {

    /**
     * @return void
     */
    public function boot() {
        //
    }

    /**
     * @return void
     */
    public function register() {

	    $this->app->bind(CsvPriceFileParserInterface::class, CsvPriceFileParser::class);
    	$this->app->bind(XmlPriceFileParserInterface::class, XmlPriceFileParser::class);
    }
}
