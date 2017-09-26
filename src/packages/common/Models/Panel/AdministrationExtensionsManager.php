<?php
namespace Arrow\Common\Models\Panel;
use Arrow\Controls\api\common\Icons;
use Arrow\Access\Models\AccessAPI;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 16.08.12
 * Time: 12:59
 * To change this template use File | Settings | File Templates.
 */
class AdministrationExtensionsManager
{
    private static $menu = array();
    private static $ignored = array();


    public static function registerElements($elements)
    {
        self::$menu = array_merge(self::$menu, $elements);
    }

    private static function getExtensionPoints()
    {
        $classes = array();


        $class = '\App\Models\Common\AdministrationExtensionPoint';
        if (class_exists($class) && in_array('Arrow\Common\AdministrationExtensionPoint', class_parents($class)))
            $classes[] = $class;


        return $classes;
    }

    private static function registerElementsFromPackages()
    {

        $class = '\App\Models\Common\AdministrationExtensionPoint';
        if (class_exists($class)) {
            self::registerElements(call_user_func(array($class, "getElements")));
            $toSection = call_user_func(array($class, "addToSection"));
            if (!empty($toSection)) {

                foreach (self::$menu as $index => $el) {
                    foreach ($toSection as $section => $elements) {
                        if (isset($el["id"]) && $el["id"] == $section ) {
                            self::$menu[$index]["elements"] = array_merge(self::$menu[$index]["elements"], $elements["elements"]);
                        }
                    }
                }

            }

            $ignored = call_user_func(array($class, "setIgnoredElements"));
            if ($ignored)
                self::$ignored = array_merge(self::$ignored, $ignored);
        }

    }

    public static function getData(){
        self::registerElementsFromPackages();
        return self::$menu;
    }

    public static function generateUl()
    {

        self::registerElementsFromPackages();
        $all_str = '';

        foreach (self::$menu as $index => $section) {
            if ( (!isset($section["id"]) || !in_array($section["id"], self::$ignored) ) && $section["active"]) {
                $elements = false;

                $section["icon"] = isset($section["icon"])?$section["icon"]:Icons::DROPBOX;

                $str = PHP_EOL.'<li '.($index==0?'class="open"':'').'>';
                if(!empty($section["title"]))
                $str.='<a href="'.(isset($section["link"])?$section["link"]:$section["title"]).'" >'.($section['icon']?'<i class=" menu-icon fa '.$section['icon'].'"></i> ':'').'<span class="menu-text">'. $section["title"].' </span><b class="arrow fa fa-angle-down"></b></a>'.PHP_EOL;/*<b class="caret"></b>*/

                $last = "divider";
                if(isset($section["elements"])){
                    $str.='<ul '.((isset($section["opened"]) && $section["opened"])||empty($section["title"])?'style="display: block;"':'').' >';

                    foreach ($section["elements"] as $key => $element) {
                        if($element == "---" && $last != "divider"){
                            //$str.='<li class="divider"></li>';
                            $last = "divider";
                            continue;
                        }elseif($element == "---") continue;

                        if (!isset($element["id"]) || !in_array($element["id"], self::$ignored)){

                            $icon = '';//'<i class="fa fa-caret-right"> </i>';

                            $icon = ($element['icon']?'<i class="fa '.$element['icon'].'" ></i> ':'');

                            if(isset($element["link"])){

                                $str.= '<li><a  class=" ' . (isset($element['class']) ? $element['class'] : '') . '" href="' .  $element["link"] .(isset($element["params"])?'?'.$element["params"]:'') . '"><i class="menu-icon fa fa-caret-right"></i>'/*.$icon*/ . ' '.$element['title'].'</a></li>';
                                $last = "element";
                                $elements = true;
                                continue;
                            }

                            $view = \Arrow\Models\Dispatcher::getDefault()->get($element["template"]);


                            if($view->isAccessible()){
                                $last = "element";
                                $str.= '<li><a class=" ' . (isset($element['class']) ? $element['class'] : '') . '" href="#' . \Arrow\Router::link( $element["template"] ).(isset($element["params"])?'?'.$element["params"]:'') . '"><i class="menu-icon fa fa-caret-right"></i>'/*.$icon */. ' '.$element['title'].'</a></li>';
                                $elements = true;
                            }

                        }
                    }
                    if($last == "divider"){
                        $len = strlen('<li class="divider"></li>');
                        $str = substr($str,0, -$len);
                    }

                    $str .= "</ul></li>".PHP_EOL;
                }



                if($elements || !isset($section["elements"]))
                    $all_str.=$str;
            }
        }

        return $all_str;
    }

    public static function generate()
    {

        self::registerElementsFromPackages();
        $all_str = '';
        foreach (self::$menu as $section) {
            if (!isset($section["id"]) || !in_array($section["id"], self::$ignored)) {
                $elements = false;



                $str = '<div><h3>'.$section["title"] .  Icons::icon(Icons::ANGLE_DOWN).' </h3>';/*<b class="caret"></b>*/

                $last = "divider";
                foreach ($section["elements"] as $key => $element) {
                    if($element == "---" && $last != "divider"){
                        //$str.='<li class="divider"></li>';
                        $last = "divider";
                        continue;
                    }elseif($element == "---") continue;

                    if (!isset($element["id"]) || !in_array($element["id"], self::$ignored)){

                        $icon = "";
                        if(isset($element["icon"])){
                            $icon = '<i class="fa '.$element["icon"].'"> </i>';
                        }

                        if(isset($element["link"])){

                            $str.= '<a  class=" ' . (isset($element['class']) ? $element['class'] : '') . '" href="' .  $element["link"] .(isset($element["params"])?'?'.$element["params"]:'') . '">'.$icon. $element['title'] . '</a>';
                            $last = "element";
                            $elements = true;
                            continue;
                        }

                        $view = \Arrow\Models\Dispatcher::getDefault()->get($element["template"]);




                        if($view->isAccessible()){
                            $last = "element";
                            $str.= '<a class=" ' . (isset($element['class']) ? $element['class'] : '') . '" href="' . \Arrow\Router::link( $element["template"] ).(isset($element["params"])?'?'.$element["params"]:'') . '">'.$icon. $element['title'] . '</a>';
                            $elements = true;
                        }

                    }
                }
                if($last == "divider"){
                    $len = strlen('<li class="divider"></li>');
                    $str = substr($str,0, -$len);
                }


                $str .= "</div>";
                if($elements)
                    $all_str.=$str;
            }
        }

        return $all_str;
    }

    public static function generateDashboard()
    {
        $class = '\Arrow\Package\application\AdministrationExtensionPoint';

        $elements = $class::getDashboardElements();
        foreach ($elements as $element) {
            $view = \Arrow\Models\Dispatcher::getDefault()->get($element);
            if( AccessAPI::checkAccessToView($view) )
                print  $view->fetch();
        }

    }
}
