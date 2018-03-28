<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 21.01.2018
 * Time: 22:05
 */

namespace Arrow\Synchronization\Controllers;

use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Shop\Models\Esotiq\Synchronization\SynchLog;
use Arrow\Shop\Models\Esotiq\Synchronization\SynchronizationConfig;
use Arrow\Shop\Models\Esotiq\Synchronization\SynchronizationRunner;
use Arrow\Shop\Models\Esotiq\Synchronization\SynrronizationRunner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/synchronization")
 */
class SynchronizationController
{


    /**
     * @return array
     * @Route("/log")
     */
    public function index()
    {
        $config = new SynchronizationConfig();
        return [
            "options" => $config->getConfig()
        ];
    }

    /**
     * @return array
     * @throws \Arrow\ORM\Exception
     * @Route("/runSynch/{configAction}")
     * @Route("/runSynch/{configAction}/{disableOutputCache}")
     */
    public function rynSynch($configAction, $disableOutputCache = 0)
    {


        $synch = new SynchronizationRunner();
        $synch->outputBufferEnabled = ($disableOutputCache == 0);


        $config = new SynchronizationConfig();

        $synch->runConfig($config->getActionByName($configAction));

        return [true];

    }


    /**
     * @return array
     * @throws \Arrow\ORM\Exception
     * @Route("/asyncLog")
     */
    public function asyncLog()
    {
        $criteria = SynchLog::get();

        $helper = new TableListORMHelper();
        $helper->addDefaultOrder("id", Criteria::O_DESC);

        return $helper->getListData($criteria);
    }


}
