<?php

namespace App\Acme\Libraries\Http;

use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use App\Acme\Interfaces\MultilingualRequest;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class FormRequest extends BaseFormRequest {

    /**
     * @return array
     */
    public function rules() {
        return [];
    }

    /**
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * @return string
     */
    public function getTranslationsInputKey() {
        return 'translations';
    }

    /**
     * @param ValidationFactory $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(ValidationFactory $factory) {

        $data = [
            'messages' => Arr::dot($this->messages()),
            'rules' => Arr::dot($this->container->call([$this, 'rules'])),
        ];

        if ($this instanceof MultilingualRequest) {

            $this->prependMultilingualPrefix($data);
            if (method_exists($this, 'rulesStatic')) {
                $data['rules'] += $this->container->call([$this, 'rulesStatic']);
            }
        }

        $this->setRulesMessages($data['rules'], $data['messages']);
        return $factory->make($this->validationData(), $data['rules'], $data['messages'], $this->attributes());
    }

    /**
     * @param array $items
     * @param array $messages
     */
    protected function setRulesMessages(array $items, array &$messages) {

        $key_translation = $this->getTranslationsInputKey();

        foreach ($items as $attribute => $rules) {

            $conditions = [];
            $attribute_prefix = null;
            $attribute_original = $attribute;
            if (strpos($attribute, $key_translation) !== false) {

                /**
                 * Remove translation prefix from attribute name
                 */
                $attribute = substr($attribute, strpos($attribute, '.', strlen($key_translation) + 1) + 1);
            } elseif (strpos($attribute, '.') !== false) {

                /**
                 * We have array as attribute (dotted), so
                 * take only key (last "part")
                 */
                $attribute = substr($attribute, strrpos($attribute, '.') + 1);

                /**
                 * Ok, this one is beta currently, but the point is to support
                 * array of items which needs to be validated.
                 *
                 * Example (careers): items.0 -> rules, items.1, etc...
                 */
                foreach (explode('.', $attribute_original) as $index => $part) {
                    if (is_numeric($part)) {

                        $attribute_prefix = implode('.', array_slice(explode('.', $attribute_original), 0, $index + 1));
                        $attribute_original = implode('.', array_slice(explode('.', $attribute_original), $index + 1));
                        break;
                    }
                }
            }

            $vars = [
                'attribute' => __t(
                    sprintf("validation.attributes.%s", $attribute), [], // try to translate attribute
                    __t(sprintf("validation.attributes.%s", $attribute_original), [], $attribute)) // try with original
            ];

            /**
             * Gather conditions and vars
             * ex. "required|max:255":
             *
             *  - conditions: required, max
             *  - vars: max=255
             */
            foreach (explode('|', $rules) as $rule) {

                if (strpos($rule, ':') !== false) {
                    list($var, $value) = explode(':', $rule);

                    $vars[$var] = $value;
                    array_push($conditions, $var);
                    continue;
                }

                array_push($conditions, $rule);
            }

            /**
             * Get translations for each $condition
             */
            foreach ($conditions as $condition) {

                if ($attribute_prefix) {
                    $key = sprintf("%s.%s.%s", $attribute_prefix, $attribute_original, $condition);
                } else {
                    $key = sprintf("%s.%s", $attribute_original, $condition);
                }

                $message = __t(sprintf("validation.%s", $condition), $vars);

                if (is_array($message)) {

                    /**
                     * Handle different translations based
                     * on expected var type.
                     */

                    $key_type = key($message);
                    foreach (array_keys($message) as $type) {
                        if (in_array($type, $conditions)) {

                            $key_type = $type;
                            break;
                        }
                    }

                    $message = __t(sprintf("validation.%s.%s", $condition, $key_type), $vars);
                }

                $messages[$key] = $message;
            }
        }
    }

    public function response(array $items) {

        if ($this->expectsJson()) {

            $messages = [];
            foreach ($items as $field => $errors) {
                foreach ((array)$errors as $error) {

                    if (!is_string($error)) {
                        continue;
                    }

                    array_push($messages, $error);
                }
            }

            return new JsonResponse(['messages' => [
                'error' => $messages
            ]], 422);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($items, $this->errorBag);
    }

    /**
     * @param array $items
     */
    protected function prependMultilingualPrefix(array &$items) {

        $key = $this->getTranslationsInputKey();
        $prefixes = array_filter(array_keys($this->input($key, [])), 'is_numeric');
        array_walk($prefixes, function (&$item) use ($key) {
            $item = sprintf("%s.%s", $key, $item);
        });

        foreach ($items as $key => $item) {
            if (!$item) {
                continue;
            }

            $items[$key] = array_prefix($item, $prefixes);
        }
    }

    /**
     * @return string
     */
    public function method() {
        return strtolower($this->getMethod());
    }
}