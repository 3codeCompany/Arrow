<?php
/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 25.08.13
 * Time: 21:53
 * To change this template use File | Settings | File Templates.
 */

namespace Arrow\Package\Common;


use Arrow\Controls\api\common\Link;
use Arrow\Controls\api\common\Modal;
use Arrow\Controls\api\Filters\DateFilter;
use Arrow\Controls\API\FiltersPresenter;
use Arrow\Controls\api\Serenity;
use Arrow\Controls\API\Table\Columns\Simple;
use Arrow\Controls\API\Table\Columns\Template;
use Arrow\Controls\API\Table\Columns\ViewMore;
use Arrow\Controls\API\Table\Table;
use Arrow\Controls\api\WidgetsSet;
use Arrow\ORM\ORM_App_Models_Common_History;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\PersistentObject;
use Arrow\Package\Common\HistoryTable as H;

class History {

    const SAVE_DATA_DIR = "../data/history/";

    public static function addByHash($hash, $action, $info, $data = false){
        $dbData = [
            H::F_ACTION => $action,
            H::F_HASH => $hash,
            H::F_INFO => $info
        ];

        self::save($dbData,$data);
    }

    public static function addByObject( PersistentObject $object, $action, $info = "", $data = false ){
        $dbData = [
            H::F_ACTION => $action,
            H::F_ID_OBJECT => $object->getPKey(),
            H::F_CLASS => $object->getClass(),
            H::F_INFO => $info
        ];

        self::save($dbData,$data);
    }

    public static function get( $handle ){

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
        $ds = TableDataSource::fromClass( H::getClass());


        if($handle instanceof PersistentObject){
            $ds->c(H::F_ID_OBJECT, $handle->getPKey())
                ->c(H::F_CLASS, $handle->getClass());
        }else{
            $ds->c(H::F_HASH, $handle);
        }

        $modal = (new Modal( "history-details", "Dodatkowe dane", function( Modal $modal){
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
            ->addOrder(H::F_DATE, Criteria::O_DESC, "Data")
            //->setGenerateWhenVisible(true)

            //->setDebug(true)
            ;
        $table->getColumnsList()
            ->addColumn(Simple::_new("date","Data")->setFilter((new DateFilter("date", "Data"))->setWithTime(true)))
            ->addColumn(Simple::_new("action","Akcja"))
            ->addColumn(Simple::_new("info","Podstawowe informacje"))
            ->addColumn(Template::_new(function($context){
                $tmp = explode("-", $context["date"]);
                $file  = self::SAVE_DATA_DIR.$tmp[0]."-".$tmp[1]."/".$context["id"].".txt";
                if(file_exists($file))
                    return '<button class="btn btn-sm btn-default" onclick="Serenity.get(\'history-details\').open({},{id:'.$context["id"].', file: \''.$file.'\'}); return false;">więcej</button>';
            }));

        $table->prependWidged(FiltersPresenter::create([$table]));

        $ds->setGlobalSearchFields(["date","action", "info" ]);

        return new WidgetsSet([$table,$modal]);
    }

    protected static  function save($dbData, $data){

        $dbData[H::F_DATE] = date( "Y-m-d H:i:s" );
        if(!isset($dbData[H::F_ID_USER])){
            if( \Arrow\Access\Auth::getDefault()->isLogged())
                $dbData[H::F_ID_USER] = \Arrow\Access\Auth::getDefault()->getUser()->getPKey();
            else
                $dbData[H::F_ID_USER] = -1;
        }

        $h = H::create($dbData);

        //saving more data to file
        if($data){
            $currMonth =  date( "Y-m" );
            $savedir = self::SAVE_DATA_DIR.$currMonth;
            if(!file_exists($savedir))
                mkdir($savedir);

            file_put_contents($savedir.DIRECTORY_SEPARATOR.$h->getPKey().".txt", $data);

        }

    }

}