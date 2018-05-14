<?php namespace Arrow\Models;

class ExceptionContent
{
    private $message;
    private $args;

    function __construct()
    {
        $args = func_get_args();
        $this->message = $args[0];
        unset($args[0]);
        $this->args = $args;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getParameters()
    {
        return $this->args;
    }

}