<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 24/01/13
 * Time: 09:28
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Package\Common;


use Arrow\Models\View;
use Arrow\RequestContext;

class BreadcrumbGenerator {

    private $defaults ;
    private $paths;
    private $additionalObject;

    function __construct( array $paths, array $defaults = array())
    {
        $this->paths = $paths;
        $this->defaults = $defaults;
    }

    function setAdditionalObject( $object ){
        $this->additionalObject = $object;
    }

    private function parseStr($str, $request){
        $str = preg_replace_callback( "/([a-zA-Z0-9]+?)\((.+?)\|(.+?)\)/", function($matches) use ($request){
            if( $request[$matches[1]] )
                return $matches[3];
            else{
                return $matches[1];
            }
        }, $str );

        return $str;
    }

    public function generate( Action $view ){

        $curPath = $view->getPath();

        $str ='<ul class="breadcrumb">';

        $stack = array( array("Start", "./admin", "home") );


        foreach( $this->paths as $path => $name ){
            if( strpos($curPath, $path) === 0 ){
                $stack[] = array($name);
            }
        }


        $request = RequestContext::getDefault();
        foreach( $this->defaults as $path => $name ){
            $tmp = explode("/",$curPath);
            $last = end($tmp);


            if($last == $path)
                $stack[] = array($this->parseStr($name, $request));


        }


        $count = count($stack);
        foreach($stack as $key => $el){
            $icon = "";
            if(isset($el[2]))
                $icon = '<i class="icon-'.$el[2].'"></i>';


            if(isset($el[1]))
                $str.='<li><a href="'.$el[1].'"'.'>'.$icon.$el[0].'</a>'.(($key+1<$count)?'<span class="divider">/</span>':'').'</li>';
            else
                $str.='<li>'.$icon.$el[0].(($key+1<$count)?'<span class="divider">/</span>':'').'</li>';
        }


        $str.='</ul>';




        return $str;
    }


}