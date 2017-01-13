<?php
namespace Arrow\Common;
use Arrow\Controls\api\common\Icons;
use Arrow\Controls\api\common\Link;
use Arrow\Controls\api\SerenityJS;
use Arrow\Access\User;
use Arrow\RequestContext;
use Arrow\Router;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 12.08.13
 * Time: 14:36
 * To change this template use File | Settings | File Templates.
 */

class Links {





    /**
     * @param string $view
     * @param array $params
     * @return Link
     */
    public static function  edit( $view = "./edit", $params = ["key" => "id"], $vars = [] ){
        $l =  (new Link(Icons::icon(Icons::PENCIL_SQUARE) .' Edytuj'))
            ->on(Link::EVENT_CLICK, SerenityJS::hash(Router::link($view.""))." return false;")

            ->setVars($vars)
            ->setContextParams($params);

        $l->getTag()->addCSSClass(" btn btn-default");

        return $l;
    }
    /**
     * @param string $view
     * @param array $params
     * @return Link
     */
    public static function  add( $view = "./edit", $params = [ ], $vars = []  ){
        $l = (new Link(Icons::icon(Icons::PLUS_SQUARE_O)." Dodaj"))

            ->on(Link::EVENT_CLICK, SerenityJS::hash(Router::link($view)))
            ->setContextParams($params)
            ->setVars($vars);

            $l->getTag()->addCSSClass("admin-dialog btn btn-primary");
        return $l;


            //@todo remove
            //->addCSSStyle("float:left;font-size: 16px;  margin-left: 20px;");
    }

    /**
     * @return Link
     */
    public static function  delete( $class ){
        return (new Link("Usuń",Router::link("common/data/operation"), true))
            ->prepend(Icons::icon(Icons::TIMES_CIRCLE))
            ->setContextParams(["key" => "id", ])
            ->setVars(["model" => $class, "action" => "delete" ])

            ->addLinkCSSStyle('color: darkred')
            ->confirm('Czy napewno usunąć?')
            ->on(Link::EVENT_SUCCESS, [
                'Serenity.get($(this)).getParent().refresh();',
                SerenityJS::info(Icons::icon(Icons::TIMES)." Obiekt został usunięty")
            ]);

    }


    /**
     * @return Link
     */
    public static function  history(){
        return (new Link("Historia"))
            ->prepend(Icons::icon(Icons::TIME));

    }

    /**
     * @param $title
     * @param $operation
     * @return Link
     */
    public static function  execute($title, $operation){
        return (new Link($title))
            ->setAction( Router::link($operation))
            ->setConfirm('Czy jesteś pewien?')
            ->setOperationDoneInfo(str_replace( "\"", "\\'",Icons::icon(Icons::CHECK_SQUARE)). " Wykonano")
            ->setJsOperationDone("Serenity.get(this).refresh()");
            ;
    }


    /**
     * @return Link
     */
    public static function  filter( $name, $data, $target = false  ){
        $rq = RequestContext::getDefault();
        //check selection
        $selected = true;
        foreach( $data as $key => $val ){
            if($rq[$key] != $val)
                $selected = false;
        }

        if($selected){
            foreach( $data as &$val ){
                $val = null;
            }
        }

        if(!$target){
            $target = '$(this)';
        }else{
            $target = "'{$target}'";
        }

        $info = "";
        if(!$selected)
            $info = "function(){ SerenityCommon.info( '".str_replace( "\"", "\\'",(Icons::icon(Icons::FILTER)))." Zastosowano filtr \'".$name."\'' )}";
        else
            $info = "function(){ SerenityCommon.info( '".str_replace( "\"", "\\'",(Icons::icon(Icons::REMOVE)))." Wycofano filtr \'".$name."\'' )}";
        $link = (new Link($name))
            ->prepend(Icons::icon(Icons::FILTER))
            ->setAction("")
            ->setJSOnClick('Serenity.get('.$target.').refreshBody( '.str_replace( "\"", "'",json_encode($data)).', {page: 1}, '.$info.' ); return false;');


        if($selected)
            $link->setCSSStyle('background-color: #0088cc; color: white; padding: 4px; border-radius: 3px;');

        //@todo remove
        $link->addCSSStyle("float:left;font-size: 16px;  margin-left: 20px;");


        return $link;
    }

    public static function getTopToolbar(){

    }




}