<?php

namespace App\Acme\Libraries\Parsers\PriceFiles;

use DOMElement;
use SimpleXMLElement;
use Illuminate\Support\Arr;
use App\Acme\Interfaces\Parsers\PriceFileParserInterface;
use App\Acme\Interfaces\Parsers\XmlPriceFileParserInterface;

class XmlPriceFileParser implements PriceFileParserInterface, XmlPriceFileParserInterface {

    /**
     * @var string
     */
    protected $identifierItem = 'item';

    /**
     * @param string $data
     * @param bool $limit
     * @return array
     */
    public function getRows($data, $limit = false) {

        $results = [];
        $columns = $this->getColumns($data);
        $xml = dom_import_simplexml(new SimpleXMLElement($data));
        $items = $xml->getElementsByTagName($this->identifierItem);

        if ($items->length) {
            for ($i = 1; $i < $items->length; $i++) {

                $item = $items->item($i);
                if (!$item instanceof DOMElement) { //ignore
                    continue;
                }

                $index = $i - 1;
                $results[$index] = array_fill_keys($columns, null);

                foreach ($item->childNodes as $child) {

                    if (!$child instanceof DOMElement) { //ignore
                        continue;
                    }

                    $key = trim($child->nodeName);
                    if (!$this->elementHasChildren($child)) { // single item
                        if (Arr::get($results[$index], $key, false) !== false) {

                            if (is_array($results[$index][$key])) { // keep pushing...
                                array_push($results[$index][$key], trim($child->nodeValue));
                            } else if (!is_null($results[$index][$key])) { // second occurrence, create an array

                                $results[$index][$key] = [
                                    $results[$index][$key],
                                    trim($child->nodeValue)
                                ];
                            } else { // single item, first occurrence
                                $results[$index][$key] = trim($child->nodeValue);
                            }
                        }

                        continue;
                    }

                    $results[$index] = array_replace_recursive($results[$index], $this->getElementChildren($child));
                }

                if ($limit && $i === $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * @param string $data
     * @return array|bool
     */
    public function getColumns($data) {

        $xml = dom_import_simplexml(new SimpleXMLElement($data));
        $items = $xml->getElementsByTagName($this->identifierItem);

        if ($items->length) {

            $columns = [];
            $row = $items->item(0)->childNodes;
            for ($i = 0; $i < $row->length; $i++) {

                $item = $row->item($i);
                if (!$item instanceof DOMElement) { //ignore
                    continue;
                } else if (!$this->elementHasChildren($item)) { // single item

                    array_push($columns, trim($item->nodeName));
                    continue;
                }

                // children
                foreach (array_keys($this->getElementChildren($item)) as $key) {
                    array_push($columns, $key);
                }
            }

            return array_unique($columns);
        }

        return false;
    }

    /**
     * @param $identifier
     * @return $this
     */
    public function setItemIdentifier($identifier) {

        $this->identifierItem = $identifier;

        return $this;
    }

    /**
     * @param DOMElement $element
     * @return array
     */
    protected function getElementChildren(DOMElement $element) {

        $results = [];
        foreach ($element->childNodes as $item) {

            $key = trim($item->nodeName);
            if (substr($key, 0, 1) === '#') {
                continue;
            } else if (Arr::get($results, $key, false) === false) {
                $results[$key] = trim($item->nodeValue);
            } else {

                $results[$key] = [trim($item->nodeValue)];
                array_push($results[$key], trim($item->nodeValue));
            }
        }

        return Arr::dot([$element->nodeName => Arr::dot($results)]);
    }

    /**
     * @param DOMElement $element
     * @return bool
     */
    protected function elementHasChildren(DOMElement $element) {

        if ($element->childNodes->length) {
            foreach ($element->childNodes as $item) {

                if (substr(trim($item->nodeName), 0, 1) !== '#') {
                    return true;
                }
            }
        }

        return false;
    }
}