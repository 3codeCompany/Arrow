yarn ru<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 17.01.2017
 * Time: 20:44
 */

namespace Arrow\Access\Tests;


use Arrow\Access\Controllers\MailerController;
use Arrow\Access\Models\User;
use Arrow\Shop\Models\Persistent\Order;
use Arrow\Translations\Models\Translations;

class MailerTest extends \PHPUnit_Framework_DOMTestCase
{
    public function testRegisterMail()
    {
        $mc = MailerController::getInstance();
        $user = User::get()->findFirst();
        $content = $mc->prepareContent(MailerController::MAIL_REGISTER, ["key" => $user->_id()]);
        //fwrite(STDOUT, "".$content . "\n");
        $this->assertSelectEquals("span.generated", "", true, $content);
        $this->assertSelectEquals("span.generated-user", $user->_id(), true, $content);

    }

    public function testPasswordRemindMail()
    {

        $mc = MailerController::getInstance();
        $user = User::get()->findFirst();
        $content = $mc->prepareContent(MailerController::MAIL_FORGOT_PASSWORD, ["key" => $user->_id()]);

        $this->assertSelectEquals("span.generated", "", true, $content);
        $this->assertSelectEquals("span.generated-user", $user->_id(), true, $content);

    }


}