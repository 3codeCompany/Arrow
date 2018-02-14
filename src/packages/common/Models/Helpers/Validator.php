<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 03.11.13
 * Time: 16:58
 */

namespace Arrow\Common\Models\Helpers;


use Arrow\RequestContext;

class Validator
{

    public static $communicates = [
        "required" => "To pole jest wymagane",
        "int" => "To pole musi zawierać liczbę całkowitą",
        "numeric" => "To pole musi zawierać liczbę",
        "email" => "Błędny format adresu email",
        "date" => "Błędny format daty",
        "url" => "Błędny adres URL",
    ];

    protected $validators = [];


    protected $errors = [];
    protected $formErrors = [];


    protected $toCheck = [];


    protected $input;

    function __construct($input)
    {
        $this->input = $input;

        $this->validators = [
            "required" => function ($input, $field) {
                return isset($input[$field]) && (!empty($input[$field]) || $input[$field] == "0");
            },
            "int" => function ($input, $field) {
                return ctype_digit($input[$field]) || empty($input[$field]);
            },
            "numeric" => function ($input, $field) {
                return is_numeric($input[$field]) || empty($input[$field]);
            },
            "email" => function ($input, $field) {
                return (filter_var($input[$field], FILTER_VALIDATE_EMAIL)) || empty($input[$field]);
            },
            "date" => function ($input, $field) {
                throw new \Exception("to implement");
            },
            "url" => function ($input, $field) {
                return (filter_var($input[$field], FILTER_VALIDATE_URL)) || empty($input[$field]);
            },
        ];
    }


    /**
     * @return Validator
     */
    public static function create($input)
    {
        return new Validator($input);


    }

    /**
     * @return Boolean
     */
    public function check(): bool
    {
        $ok = true;
        foreach ($this->toCheck as $type => $checkArr) {

            foreach ($checkArr as $index => $value) {
                if (is_int($index)) {
                    $field = $value;
                    $error = self::$communicates[$type];
                } else {
                    $field = $index;
                    $error = $value;
                }
                if (!$this->validators[$type]($this->input, $field)) {
                    $ok = false;
                    if (!isset($this->errors[$field])) {
                        $this->errors[$field] = [];
                    }
                    $this->errors[$field][] = $error;
                }
            }
        }

        if (!empty($this->errors) || !empty($this->formErrors)) {
            return false;
        }

        return $ok;

    }

    public function fails(): bool
    {
        return !$this->check();
    }

    public function responseAndEnd()
    {
        $formated = $this->response();
        if (RequestContext::getDefault()->isXHR()) {

        }
    }

    public function response()
    {
        return ["errors" => $this->formErrors, "fieldErrors" => $this->errors];
    }


    /**
     * @param $field
     * @return $this Validator
     */
    public function required($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }
        if (!isset($this->toCheck["required"])) {
            $this->toCheck["required"] = [];
        }
        $this->toCheck["required"] = array_merge($this->toCheck["required"], $field);

        return $this;
    }

    /**
     * @param $field
     * @return $this Validator
     */
    public function int($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }
        if (!isset($this->toCheck["int"])) {
            $this->toCheck["int"] = [];
        }
        $this->toCheck["int"] = array_merge($this->toCheck["int"], $field);
        return $this;
    }

    /**
     * @param $field
     * @return $this Validator
     */
    public function numeric($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }
        if (!isset($this->toCheck["numeric"])) {
            $this->toCheck["numeric"] = [];
        }
        $this->toCheck["numeric"] = array_merge($this->toCheck["numeric"], $field);
        return $this;
    }

    /**
     * @param $field
     * @return $this Validator
     */
    public function date($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }
        if (!isset($this->toCheck["date"])) {
            $this->toCheck["date"] = [];
        }
        $this->toCheck["date"] = array_merge($this->toCheck["date"], $field);
        return $this;
    }

    /**
     * @param $field
     * @return $this Validator
     */
    public function email($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }
        if (!isset($this->toCheck["email"])) {
            $this->toCheck["email"] = [];
        }
        $this->toCheck["email"] = array_merge($this->toCheck["email"], $field);
        return $this;
    }

    /**
     * @param $field
     * @return $this Validator
     */
    public function url($field)
    {
        if (!is_array($field)) {
            $field = [$field];
        }
        if (!isset($this->toCheck["url"])) {
            $this->toCheck["url"] = [];
        }
        $this->toCheck["url"] = array_merge($this->toCheck["url"], $field);
        return $this;
    }

    /**
     * @param $field
     * @return $this Validator
     */
    public function custom($name, $error, $field, callable $function)
    {
        self::$communicates[$name] = $error;
        $this->validators[$name] = $function;
        if (!is_array($field)) {
            $field = [$field];
        }
        if (!isset($this->toCheck[$name])) {
            $this->toCheck[$name] = [];
        }
        $this->toCheck[$name] = array_merge($this->toCheck[$name], $field);
        return $this;

    }

    public function addFieldError($field, $error)
    {
        $this->errors[$field][] = $error;
    }

    public function addError($error)
    {
        $this->formErrors[] = $error;
    }
}
