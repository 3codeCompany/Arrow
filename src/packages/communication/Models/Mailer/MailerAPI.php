<?php

namespace Arrow\Communication\Models\Mailer;

use Exception;
use Psr\Log\LoggerInterface;
use function var_dump;

class MailerAPI
{

    private static $mailLib = null;

    private static $lastError = null;

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
    public static function send($emails, $content, $topic, $reply = "", $from = "", $from_name = "", $attachments = null, $bcc = false, LoggerInterface $externalLogger = null)
    {
        //require_once ARROW_LIBS_PATH.DIRECTORY_SEPARATOR."Swift".DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."swift_required.php";
        $settings = self::$specialConf;

        $transport =  (new \Swift_SmtpTransport($settings["host"], $settings["port"]))
            ->setEncryption($settings["secure_type"])
            ->setUsername($settings["username"])
            ->setPassword($settings["password"]);


        // Create the Mailer using your created Transport
        $mailer = new \Swift_Mailer($transport);

        //$logger = new \Swift_Plugins_Loggers_ArrayLogger();
        //$mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($logger));

        $emails = str_replace([";", " "], [",", ""], $emails);

        $from = $from?$from: $settings["from_mail"];
        $from_name = $from_name?$from_name: $settings["from_name"];

        try {
            $message = (new \Swift_Message($topic))
                ->setSubject($topic)
                ->setFrom(array($from => $from_name))
                ->setTo(is_array($emails) ? $emails : explode(",", $emails))
                ->setBody($content, 'text/html');

            if ($attachments) {
                foreach ($attachments as $a) {
                    $message->attach(\Swift_Attachment::fromPath($a));
                }
            }

            $result = $mailer->send($message);
        } catch (Exception $ex) {
            print "<pre>";
            /*print_r($settings);
            var_dump(explode(",", $emails));
            var_dump($emails);
            var_dump($ex);*/
            exit("Błąd wysyłki maila");
        }

        /*if (!$result && $externalLogger) {
            $externalLogger->critical("Mail sent error: " . $logger->dump());
        }*/


        return $result;

    }

    public static function getLastError()
    {

        return self::$lastError;
    }


}

?>