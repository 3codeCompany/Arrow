<?php

namespace Arrow\Communications\Controllers\Mailer;

use App\Layouts\Mailer\MailerLayout;
use Arrow\Communication\Models\Mailer\MailerAPI;
use Arrow\ConfigProvider;
use Arrow\Exception;
use Arrow\Models\Action;
use Arrow\Models\Controller;
use Arrow\Models\Dispatcher;
use Arrow\Models\Project;
use Arrow\Models\ViewParser;
use Arrow\ORM\Persistent\Criteria, Arrow\Models\View, Arrow\RequestContext, Arrow\Models\Operation;


use Arrow\Access\Models\User;
use Arrow\Package\CRM\Task;
use Arrow\Package\CRM\TaskCategory;
use Arrow\Package\CRM\TaskStatus;
use Arrow\Shop\Models\Persistent\Order;
use Arrow\Shop\Models\Persistent\OrderProduct;
use Arrow\Shop\Models\Persistent\OrderShipment;
use Arrow\Shop\Models\Persistent\ProductVariant;
use Arrow\Translations\Models\Translations;
use Psr\Log\LoggerInterface;


/**
 * @method MailerController Controller::getInstance()
 * @package Arrow\Package\Application
 */
class MailerController extends Controller
{


    public $forceLang = false;
    public $forceFrom = false;
    private $c = true;


    public function prepareContent($conf, $data)
    {
        if (!is_array($conf)) {
            $conf = $this->configuration[$conf];
        }

        $view = Dispatcher::getDefault()->get($conf[0]);
        $this->request = new RequestContext($data);
        return $view->fetch(new RequestContext($data));
    }

    public function send($conf, $email, $data = [], $historyObject = null, $attachements = [],  $bcc = false, LoggerInterface $logger = null)
    {


        $mailerConf = ConfigProvider::get('communication')['emails']['default'];

        $from = $mailerConf['from'];
        if ($this->forceFrom) {
            $from = $this->forceFrom;
        }
        MailerAPI::pushSendboxConf($mailerConf['host'], $mailerConf['port'], $mailerConf['secureType'], $mailerConf['user'], $mailerConf['password'], $from, $from);

        if (!isset($this->configuration[$conf])) {
            throw new Exception(["msg" => "Can't find configuration `$conf`", "currentConf" => $this->configuration]);
        }

        $conf = $this->configuration[$conf];

        $view = Dispatcher::getDefault()->get($conf[0]);

        //$lang = $this->forceLang?$this->forceLang:$this->currentLang;

        $lang = "pl";

        Translations::setupLang($lang);

        $content = $this->prepareContent($conf, $data);

        $title = isset($view["mailTopic"]) ? $view["mailTopic"] : Translations::translateText($conf[1]);
        $title = isset($data["mailTopic"]) ? $data["mailTopic"] : $title;

        try {

            MailerAPI::send($email, $content, $title, "", "", "", $attachments, $bcc, $logger);
            if ($historyObject) {
                History::addByObject($historyObject, $title, $email, $content);
            }
        } catch (\Exception $ex) {

            if ($logger) {
                $logger->critical($ex->getMessage());
            }

            if ($historyObject) {
                History::addByObject($historyObject, "[error] [{$ex->getMessage()}] " . $title, $email, $ex->getMessage());
            }
        }

        return $content;

    }


    public function eventRunBeforeAction(Action $action)
    {

        $action->assign("_title", function ($text) {
            return '<p align="left" style="font-weight: bold; padding-bottom: 5px; color: #c60f11; border-bottom:solid 1px rgb(210,210,210);">' . $text . '</p>';
        });

        $action->assign("_text", function ($text) {
            return '<div align="left" style="font-size: 13px;">' . $text . '</div>';
        });

        $action->assign("style_th", 'text-align: center; font-size: 13px; padding: 4px; background-color: rgb(240,240,240); font-weight: bold;');
        $action->assign("style_td", 'text-align: right; font-size: 11px;padding: 6px;');
        parent::eventRunBeforeAction($action);
    }

    public function viewBeforeCompileEvent(Action $view)
    {
        parent::viewBeforeCompileEvent($view); // TODO: Change the autogenerated stub


        $view->addParser(new ViewParser("/<td s=\"s\">/", function ($matches) {
            return '<td style="font-size: 11px;padding: 6px;">';
        }));

        $view->addParser(new ViewParser("/<th s=\"s\">/", function ($matches) {
            return '<th style="text-align: center; font-size: 13px; padding: 4px; background-color: rgb(240,240,240); font-weight: bold;">';
        }));


        $view->setLayout(new MailerLayout());

        $view->assign("_title", function ($text) {
            return '<p align="left" style="font-weight: bold; padding-bottom: 5px; color: #c60f11; border-bottom:solid 1px rgb(210,210,210);">' . $text . '</p>';
        });

        $view->assign("_text", function ($text) {
            return '<div align="left" style="font-size: 13px;">' . $text . '</div>';
        });

        $view->assign("style_th", 'text-align: center; font-size: 13px; padding: 4px; background-color: rgb(240,240,240); font-weight: bold;');
        $view->assign("style_td", 'text-align: right; font-size: 11px;padding: 6px;');


        $view->addParser(new ViewParser("/\-\|(.+?)\|\-/", function ($matches) {
            return Translations::translateText($matches[1]);
        }));

    }


}
