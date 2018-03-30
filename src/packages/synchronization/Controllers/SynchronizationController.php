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
use Arrow\Synchronization\Models\SynchLog;

use Arrow\Synchronization\Models\SynchronizationConfig;
use Arrow\Synchronization\Models\SynchronizationRunner;
use Arrow\Synchronization\Models\SynrronizationRunner;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("")
 */
class SynchronizationController
{


    /**
     * @return array
     * @Route("/log")
     */
    public function log()
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
