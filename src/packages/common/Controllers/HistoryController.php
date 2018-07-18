<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 18.07.2018
 * Time: 12:33
 */

namespace Arrow\Common\Controllers;

use App\Models\CRM\History;
use Arrow\Access\Models\User;
use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\ORM\Persistent\Criteria;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HistoryController
 * @package Arrow\Common\Controllers
 * @Route("/history")
 */
class HistoryController
{
    /**
     * @Route("/{model}/{key}")
     */
    public function getTableData($model, $key)
    {
        $helper = new TableListORMHelper();



        $criteria = History::get()
            ->_class($model)
            ->_elementId($key)
            ->_join(User::class, [History::F_USER_ID => "id"], "U", [User::F_LOGIN]);


        $helper->addDefaultOrder("id", Criteria::O_DESC);


        return $helper->getListData($criteria);


    }

}