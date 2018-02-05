<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 30.01.2018
 * Time: 18:33
 */

namespace Arrow;


use Symfony\Component\HttpFoundation\Request;

class StateProvider
{

    const ARROW_DEV_MODE = "ARROW_DEV_MODE";
    const ARROW_DEV_MODE_FRONT = "ARROW_DEV_MODE_FRONT";


    private $state = [];
    private $request = null;

    /**
     * StateProvider constructor.
     * @param array $state Request context
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function registerState(string $name, $default = null)
    {
        if (isset($_SESSION["state"][$name])) {
            $input = $this->request->get($name);
            if ($input !== null) {
                $_SESSION["state"][$name] = $input;
            }
            $this->state[$name] = $_SESSION["state"][$name];
        } else {
            $this->state[$name] = $this->request->get($name, $default);
            $_SESSION["state"][$name] = $this->state[$name];
        }
    }

    public function get(string $name)
    {
        return $this->state[$name];
    }

    public function set($name, $value)
    {
        $this->state[$name] = $value;
    }


}