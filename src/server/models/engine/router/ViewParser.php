<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 03.07.13
 * Time: 15:01
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Models;


class ViewParser {

    private $regularExpression;
    private $callback;

    function __construct( $regularExpression, $callback )
    {
        $this->regularExpression = $regularExpression;
        $this->callback = $callback;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return mixed
     */
    public function getRegularExpression()
    {
        return $this->regularExpression;
    }



}