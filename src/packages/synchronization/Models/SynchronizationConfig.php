<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 16.03.2018
 * Time: 09:18
 */

namespace Arrow\Synchronization\Models;


use App\Models\Common\AdministrationExtensionPoint;
use App\Models\ERP\Synchronizer;


class SynchronizationConfig
{

    protected $config;


    public function __construct()
    {
        $this->config = AdministrationExtensionPoint::getSynchronizationConfig();

    }


    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $actionName
     * @return SynchronizationAction
     */
    public function getActionByName($actionName)
    {
        foreach ($this->config as $main) {
            if ($main->actionName == $actionName) {
                return $main;
            }
            foreach ($main->subTasks as $sub) {
                if ($sub->actionName == $actionName) {
                    return $sub;
                }
            }
        }

    }

    public function getFlatActionNameLabel()
    {
        foreach ($this->config as $main) {
            if ($main->actionName == $actionName) {
                return $main;
            }
            foreach ($main->subTasks as $sub) {
                if ($sub->actionName == $actionName) {
                    return $sub;
                }
            }
        }


    }

}
