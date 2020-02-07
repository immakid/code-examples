<?php

namespace App\Acme\Libraries;

use Request;
use LogicException;
use InvalidArgumentException;

class Assets {

    /**
     *
     * @var string
     */
    protected $prefix;

    /**
     *
     * @var string
     */
    protected $group;

    /**
     *
     * @var array
     */
    protected $groups = [];

    /**
     *
     * @var array
     */
    protected $items = [
        'js' => [],
        'css' => []
    ];

    public function __construct() {

        $this->groups = array_keys(config('assets'));

        if ($this->groups) {
            $this->setEnvironment($this->groups[key($this->groups)], false);
        }
    }

    /**
     * @param string $type
     * @param bool $implementations
     * @return mixed
     */
    public function get($type = 'css', $implementations = true) {

        if ($implementations) {
            $this->inject(config(sprintf("assets.%s.%s.custom", $this->group, $type), []), $type);
        }

        if (isset($this->items[$type]) && $this->items[$type]) {
            return $this->items[$type];
        }
    }

    /**
     *
     * @param array|string $assets
     * @param string $type
     * @throws LogicException
     * @return Assets
     */
    public function inject($assets, $type = 'css') {

        if (!isset($this->items[$type])) {
            throw new InvalidArgumentException("Asset type $type is not supported.");
        }

        foreach ((is_array($assets) ? $assets : [$assets]) as $asset) {
            $path = public_path(trim(sprintf("%s/%s", $this->prefix, $asset), '/'));

            if (strpos($asset, '://') === false) {

                $format = "%s/%s/%s?ver=%d";
                $ver = file_exists($path) ? filemtime($path) : rand(111, 999);
                $asset = sprintf($format, Request::root(), $this->prefix, $asset, $ver);
            }

            if (!in_array($asset, $this->items[$type])) {
                array_push($this->items[$type], $asset);
            }
        }

        return $this;
    }

    /**
     *
     * @param string|array $reference
     * @return \App\Acme\Libraries\Assets
     */
    public function injectPlugin($reference) {

        foreach ((is_array($reference) ? $reference : [$reference]) as $plugin) {

            if (!$items = config(sprintf("assets.%s._plugins.$plugin", $this->group))) {
                continue;
            }

            foreach ($items as $type => $assets) {
                $this->inject($assets, $type);
            }
        }

        return $this;
    }

    /**
     *
     * @return \App\Acme\Libraries\Assets
     */
    public function injectDefaults() {

        foreach (['js', 'css'] as $type) {
            if (!$assets = config(sprintf("assets.%s.%s.files", $this->group, $type))) {
                continue;
            }

            $this->inject($assets, $type);
        }

        // Plugins
        $this->injectPlugin(config(sprintf('assets.%s.plugins._default', $this->group), []));

        return $this;
    }

    /**
     * @param string $group
     * @param bool $defaults
     * @return $this
     */
    public function setEnvironment($group, $defaults = true) {

        $key = array_search($group, $this->groups);

        if ($key === false) {
            throw new InvalidArgumentException("Group $group is not defined.");
        }

        $this->truncateItems();

        $this->group = $this->groups[$key];
        $this->prefix = config(sprintf("assets.%s._prefix", $this->group));

        if ($defaults) {
            $this->injectDefaults();
        }

        return $this;
    }

    /**
     * In case we are not targeting /assets/ folder.
     *
     * @param string $prefix
     * @return \App\Acme\Libraries\Assets
     */
    public function setPrefix($prefix) {

        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return $this
     */
    protected function truncateItems() {

        foreach (array_keys($this->items) as $key) {
            $this->items[$key] = [];
        }

        return $this;
    }
}
