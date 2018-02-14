<?php

namespace Arrow\Common\Models\Panel;

use Arrow\Access\Models\AccessAPI;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 16.08.12
 * Time: 13:01
 * To change this template use File | Settings | File Templates.
 */
abstract class AdministrationExtensionPoint
{
    abstract protected function getMenuElements();

    abstract protected function getDashboardElements();

    public function getPreparedData()
    {
        $elements = $this->getMenuElements();

        //removing not active elemets
        foreach ($elements as $key => $element) {
            if (!$element["active"]) {
                unset($elements[$key]);
                continue;
            }
        }
        //gathering all routes info
        $routes = [];
        foreach ($elements as $key => $element) {
            foreach ($element["elements"] as $subElementKey => $subElement) {
                $routes[] = $subElement["route"];
            }
        }




        $accessData = AccessAPI::checkAccessToPoints($routes);


        foreach ($elements as $key => $element) {
            foreach ($element["elements"] as $subElementKey => &$subElement) {
                if (!$accessData[$subElement["route"]]) {
                    //deleting subelement
                    unset($elements[$key]["elements"][$subElementKey]);
                }
            }
            //if all elements gone removing section
            if (empty($elements[$key]["elements"])) {
                unset($elements[$key]);
            }else{
                //reseting indexes
                $elements[$key]["elements"] = array_reverse(array_reverse($elements[$key]["elements"]));
            }
        }




        return [
            "menu" => array_reverse(array_reverse($elements)),
            "dashboard" => [],
        ];

    }
}
