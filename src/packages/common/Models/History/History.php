<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 09.12.13
 * Time: 07:16
 */

namespace Arrow\Common\Models\History;


use Arrow\Controls\api\common\Modal;
use Arrow\Controls\api\Filters\DateFilter;
use Arrow\Controls\API\FiltersPresenter;
use Arrow\Controls\API\Table\Columns\Simple;
use Arrow\Controls\API\Table\Table;
use Arrow\Controls\api\WidgetsSet;
use Arrow\ORM\ORM_Arrow_Common_Models_History;
use Arrow\ORM\ORM_Arrow_Common_Models_History_History;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\ORM_Arrow_Application_History;
use Arrow\ORM\ORM_Arrow_CRM_History;
use Arrow\ORM\Persistent\PersistentObject;
use Arrow\Access\Models\Auth;
use Arrow\Access\Models\User;
use Arrow\Common\Models\Wigets\Table\TableDataSource;

class History extends ORM_Arrow_Common_Models_History_History {

    /**
     * @param PersistentObject $object
     * @return History[]
     */
    public static function getObjectHistory(PersistentObject $object){
        return self::get()
            ->c(self::F_ELEMENT_ID, $object->getPKey())
            ->c(self::F_CLASS, $object->getClass())
            ->find();

    }

    public static function getObjectHistoryCriteria(PersistentObject $object){
        return self::get()
            ->c(self::F_ELEMENT_ID, $object->getPKey())
            ->c(self::F_CLASS, $object->getClass())
            ;
    }

    /**
     * @param PersistentObject $object
     * @param $description
     * @param bool $addData1
     * @param bool $addData2
     * @return static
     */
    public static function  createEntry( PersistentObject $object, $description, $addData1 = false, $addData2 = false){
        $user = Auth::getDefault()->getUser();
        $base =  [
            History::F_ELEMENT_ID => $object->getPKey(),
            History::F_CLASS => $object::getClass(),
            History::F_CREATED => date("Y-m-d H:i:s"),
            History::F_USER_ID => $user?$user->_id():"-1",
            History::F_DESCRIPTION => $description,
            History::F_ADD_DATA_1 => $addData1,
            History::F_ADD_DATA_2 => $addData2,
        ];
        return self::create($base);
    }



    public static function getModalWidget( $class ){

        $modal =  (new Modal("history-modal", "Historia", function (Modal $modal) use ($class) {
            $el = $class::get()->findByKey($modal->getState("key"));
            if ($el){
                return History::getTableWidget($el)->generate();
            }
        }));

        $modal->getBodyNode()->setWidth(900)->setHeight(500);

        return $modal;

    }

    /**
     * @param $handle
     * @return WidgetsSet
     */
    public static  function getTableWidget( $handle ){
        $ds = TableDataSource::fromClass( self::getClass())
            ->_join(User::getClass(),[self::F_USER_ID => "id"], "U", ["login"] );


        if($handle instanceof PersistentObject){
            if($handle->getClass() == "Arrow\Shop\Models\Persistent\Order"){
                $ds->c(self::F_ELEMENT_ID, $handle->getPKey())
                    ->startGroup()
                    ->c(self::F_CLASS, $handle->getClass())
                    ->_or()
                    ->c(self::F_CLASS, "App\Models\Shop\Order")
                    ->endGroup();
            }else{
                $ds->c(self::F_ELEMENT_ID, $handle->getPKey())
                    ->c(self::F_CLASS, $handle->getClass());
            }
        }else{
            $ds->c(self::F_HASH, $handle);
        }

        $modal = (new Modal( "history-details", "Dodatkowe dane", function( Modal $modal){
            $his = self::get()->findByKey($modal->getState("id"));
            if(strpos($his->_addData2(),'<?xml version="1.0"?>') === false )
                return $his->_addData2();
            else
                return "<pre>".htmlentities($his->_addData2())."</pre>";

            if(file_exists($modal->getState("file"))){
                $content = file_get_contents($modal->getState("file"));
                $matches = [];
                if(preg_match_all('/<body>(.+?)<\/body>/ms', $content, $matches)){
                    return $matches[1][0];

                }else{
                    return $content;
                }
            }

        }));
        $modal->getBodyNode()->setWidth(900);


        $table = Table::create("history-table", $ds)
            ->addOrder(self::F_CREATED, Criteria::O_DESC, "Data")
            //->setGenerateWhenVisible(true)
            ->setDataColumns([History::F_ADD_DATA_2])
            //->setDebug(true)
        ;
        $table->getColumnsList()
            ->addColumn(Simple::_new("created","Data")->setFilter((new DateFilter("created", "Data"))->setWithTime(true)))
            ->addColumn(Simple::_new("U:login","Użytkownik"))
            ->addColumn(Simple::_new(self::F_DESCRIPTION,"Akcja"))
            ->addColumn(Simple::_new(self::F_ADD_DATA_1,"Podstawowe informacje"))
            ->addColumn(\Arrow\Controls\API\Table\Columns\Template::_new(function( History $context){

                /*$tmp = explode("-", $context["date"]);
                $file  = self::SAVE_DATA_DIR.$tmp[0]."-".$tmp[1]."/".$context["id"].".txt";
                if(file_exists($file))*/
                if($context[History::F_ADD_DATA_2])
                    return '<button class="btn btn-sm btn-default" onclick="Serenity.get(\'history-details\').open({},{id:'.$context["id"].', file: \''.'xx'.'\'}); return false;">więcej</button>';
            }));

        $ds->setGlobalSearchFields(["created","action", "info" ]);
        $table->prependWidged(FiltersPresenter::create([$table]));

        return new WidgetsSet([$table,$modal]);
    }

} 