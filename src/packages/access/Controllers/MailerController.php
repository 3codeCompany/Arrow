<?php
namespace Arrow\Access\Controllers;

use App\Layouts\Mailer\MailerLayout;
use Arrow\ConfigProvider;
use Arrow\Exception;
use Arrow\Models\Action;
use Arrow\Models\Dispatcher;
use Arrow\Models\Project;
use Arrow\Models\ViewParser;
use Arrow\ORM\Persistent\Criteria, Arrow\Models\View, Arrow\RequestContext, Arrow\Models\Operation;


use Arrow\Access\Models\User;
use Arrow\Translations\Models\Translations;
use Arrow\Communication\MailerAPI;
use Arrow\Package\CRM\Task;
use Arrow\Package\CRM\TaskCategory;
use Arrow\Package\CRM\TaskStatus;
use Arrow\Shop\Models\Persistent\Order;
use Arrow\Shop\Models\Persistent\OrderProduct;
use Arrow\Shop\Models\Persistent\OrderShipment;
use Arrow\Shop\Models\Persistent\ProductVariant;


/**
 * Class MailerController
 * @method MailerController Controller::getInstance()
 * @package Arrow\Package\Application
 */
class MailerController extends \Arrow\Communication\Controllers\MailerController
{

    const MAIL_REGISTER = "register";
    const MAIL_FORGOT_PASSWORD = "forgonPassword";


    protected $configuration = [
        self::MAIL_REGISTER => ["/access/mailer/account/register", "Potwierdzenie rejestracji"],
        self::MAIL_FORGOT_PASSWORD => ["/access/mailer/account/forgotPassword", "Zmiana hasła"],
    ];



    public function mail_account_register(Action $view, $request)
    {
        $view->setLayout(new MailerLayout());

        $user = User::get()->findByKey($request["key"]);

        $view->assign("user", $user);

    }



    public function mail_account_forgotPassword(Action $view, $request)
    {
        $view->setLayout(new MailerLayout());

        $user = User::get()->findByKey($request["key"]);

        $view->assign("user", $user);
        $view->assign("newPassword", $request["newPassword"]);
    }





}
