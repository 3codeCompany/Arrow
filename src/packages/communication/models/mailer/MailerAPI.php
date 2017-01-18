<?php

namespace Arrow\Communication\Models\Mailer;

class MailerAPI extends \Arrow\Object
{

    private static $mailLib = NULL;

    private static $lastError = NULL;

    private static $specialConf = null;

    public static function pushSendboxConf($host, $port, $secureType, $user, $password, $from, $fromMail)
    {
        self::$specialConf = [
            "host" => $host,
            "port" => $port,
            "secure_type" => $secureType,
            "username" => $user,
            "password" => $password,
            "from_name" => $from,
            "from_mail" => $fromMail
        ];
    }


    /**
     * Wysyła maila jesli from i from name sa puste - wysyla z domyslnego
     *
     * @param string $mail_in - maile na które wysyłana będzie treść - oddzielone przecinkami
     * @param string $topic - tytuł maili
     * @param string $contents - treść maili
     * @param string $save - jeśli jest stringiem to jako taki typ zostanie zapisany
     * @return bool - true jeśli sie powiodło - flase w przeciwnym wypadku
     */
    public static function send($emails, $content, $topic, $reply = "", $from = "", $from_name = "", $attachments = NULL)
    {
        //require_once ARROW_LIBS_PATH.DIRECTORY_SEPARATOR."Swift".DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."swift_required.php";
        $settings = self::$specialConf;

        $transport = \Swift_SmtpTransport::newInstance($settings["host"])
            ->setPort($settings["port"])
            ->setEncryption($settings["secure_type"])
            ->setUsername($settings["username"])
            ->setPassword($settings["password"]);


        // Create the Mailer using your created Transport
        $mailer = \Swift_Mailer::newInstance($transport);

        $emails = str_replace([";", " "], [",", ""], $emails);

        $message = \Swift_Message::newInstance($topic)
            ->setSubject($topic)
            ->setFrom(array($settings["from_mail"] => $settings["from_name"]))
            ->setTo(is_array($emails) ? $emails : explode(",", $emails))
            ->setBody($content, 'text/html');

        if ($attachments) {
            foreach ($attachments as $a) {
                $message->attach(\Swift_Attachment::fromPath($a));
            }
        }

        // Send the message
        if (isset($_REQUEST["test55"])) {
            print_r($emails);
            print "<br /><br />";
        }

        $result = $mailer->send($message);


        if (!$result) {
            print_r($emails);
            exit("problem");
        }


        return $result;

    }

    public static function getLastError()
    {

        return self::$lastError;
    }


}

?>