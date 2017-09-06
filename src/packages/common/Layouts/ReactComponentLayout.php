<?php

namespace Arrow\Common\Layouts;

use Arrow\Models\Action;
use Arrow\RequestContext, Arrow\Access\Models\AccessAPI, Arrow\Access\Models\Auth, Arrow\View;
use Arrow\ViewManager;
use function str_replace;

class ReactComponentLayout extends \Arrow\Models\AbstractLayout
{
    public function createLayout(ViewManager $viewM)
    {
        $viewM->get()->assign("path", $viewM->get()->getPath());

        //$view->assign("path",$view->getPath());


    }

    public function getLayoutFile()
    {
        return __DIR__ . "/ReactComponentLayout.phtml";
    }

    public function getFileName($path)
    {
        return $path . ".component.js";
    }

    public function getFirstTemplateContent(Action $action)
    {
        $action = $action->getPackage() . "_" . str_replace("\\", "_", trim($action->getPath(), "\\"));
        $code = <<<CODE
import React, {Component} from 'react';

//$action
export default class ArrowViewComponent extends Component{
    constructor(props){
        super(props);
        this.state = {};
    }
    render(){
        return (
            <div>It is  $action comp</div>
        )
    }
}
CODE;
        return $code;
    }
}
