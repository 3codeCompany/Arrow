<?php

namespace Arrow\Models;

use Arrow\ViewManager;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 11.09.12
 * Time: 20:16
 * To change this template use File | Settings | File Templates.
 */
abstract class AbstractLayout
{

    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var string
     */
    protected $template;

    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }

    public function setTemplate(string $template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }


    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }


    abstract public function render();


}
