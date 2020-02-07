<?php

namespace App\Acme\Libraries\Http;

use InvalidArgumentException;

class Flash {

    /**
     *
     * @var string
     */
    protected $messages = [
        'info' => [],
        'error' => [],
        'success' => [],
        'warning' => []
    ];

    /**
     * @var string
     */
    protected $iconsFramework = 'font-awesome';

    /**
     * @var array
     */
    protected static $icons = [
        'font-awesome' => [
            'info' => 'information',
            'error' => 'exclamation-triangle',
            'success' => 'checkmark',
            'warning' => 'exclamation-triangle'
        ]
    ];

    /**
     * @param bool $type
     * @return string
     */
    public function getMessages($type = false) {

        if ($type) {
            if (!isset($this->messages[$type])) {
                throw new InvalidArgumentException("Unsupported message type $type.");
            }

            return $this->messages[$type];
        }

        return $this->messages;
    }

    /**
     * @param string $message
     * @param string|bool $title
     * @param string $type
     * @return $this
     */
    public function setMessage($message, $title = false, $type = 'info') {

        if (!isset($this->messages[$type])) {
            throw new InvalidArgumentException("Unsupported message type $type.");
        }

        array_push($this->messages[$type], [
            'text' => $message,
            'title' => $title ? $title : ucfirst($type),
            'icon' => self::$icons[$this->iconsFramework][$type]
        ]);

        session()->flash('messages', $this->messages);

        return $this;
    }

    /**
     *
     * @param string $message
     * @param string|boolean $title
     * @return Flash
     */
    public function info($message, $title = false) {
        return $this->setMessage($message, $title);
    }

    /**
     *
     * @param string $message
     * @param string|boolean $title
     * @return Flash
     */
    public function error($message, $title = false) {
        return $this->setMessage($message, $title, 'error');
    }

    /**
     *
     * @param string $message
     * @param string|boolean $title
     * @return Flash
     */
    public function success($message, $title = false) {
        return $this->setMessage($message, $title, 'success');
    }

    /**
     *
     * @param string $message
     * @param string|boolean $title
     * @return Flash
     */
    public function warning($message, $title = false) {
        return $this->setMessage($message, $title, 'warning');
    }
}
