<?php
namespace Arrow\Package\Utils;
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 01.09.12
 * Time: 18:33
 * To change this template use File | Settings | File Templates.
 */
class Developer
{
    public static function generateDeveloperPanel(){



        $links = array(
            "Go to template controller" => \Arrow\ViewManager::getCurrentView()->getTemplateDescriptor()->getPath()."_Controller.php",
            "Go to template" => \Arrow\ViewManager::getCurrentView()->getTemplateDescriptor()->getPath().".php",
            "Go to schema" => \Arrow\ViewManager::getCurrentView()->getTemplateDescriptor()->getLayout()->getLayoutFile(),

        );
        $tmp = array();
        $tmp[] = "Current template: ".\Arrow\ViewManager::getCurrentView()->getTemplateDescriptor()->getMappingPath();
        foreach($links as $key => $file)
            $tmp[] = '<a href="javascript: $.get(\'http://localhost:8091/?message='.$file.':1\'); return false;">'.$key.'</a>';

        $tmp[] = '<a href="'.\Arrow\Models\TemplateLinker::getDefault()->generateTemplateLink(array("path" => "utils::developer/clearCache")).'">Clear cache</a>';
        return implode(" | ", $tmp);

    }
}
