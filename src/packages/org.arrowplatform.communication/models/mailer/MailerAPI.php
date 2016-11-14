<?php
namespace Arrow\Package\Communication;

use Arrow\ORM\Persistent\Criteria;

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

    public static function getTemplateBySystemName($systemName)
    {
        $template = Criteria::query(MailTemplate::getClass())
            ->c(MailTemplate::F_CODE_NAME, $systemName)
            ->findFirst();

        if (empty($template)) throw new \Arrow\Exception(array("msg" => "[Mail::sendForm] Can't find template", "system_name" => $systemName));
        return $template;
    }

    /** Wysyła podany szblon
     *
     * @param $code_name - nazwa kodowa wysyłanego templata
     * @param $emails - dodatkowe maile na ktore zostanie wysłana wiadomość
     * @param $data - dane ktore są wrzucane w szablon
     * @param $attachments - załączniki - tablica krotek (path,name)
     * @param $save - bool - informuje czy ma zostać zapisana wiadomość w bazie danych czy nie
     * @return bool - informacja o tym czy powiodło się wysłanie czy nie
     */
    public static function sendTemplate(MailTemplate $template, $emails, $data = array(), $save = true, $replyTo = false, $attachments = array())
    {
        $data["host"] = $_SERVER["HTTP_HOST"];
        $content = self::buildTemplateContent($template, $data);

        $title = $template[MailTemplate::F_TITLE] ? $template[MailTemplate::F_TITLE] : $save["topic"];
        $replyTo = $template[MailTemplate::F_REPLY_TO] ? $template[MailTemplate::F_REPLY_TO] : $replyTo;
        /*$smc = new SmartyCompiler( $title );
        $title = $smc->getResult( $data );*/
        //$emails.",{$template[MailTemplate::F_RECIVER_EMAIL]}"

        $sended = self::send($emails, $content, $title, $replyTo, "", "", $attachments);

        return;
        if ($save != false) { // zapisanie danych
            $mtp = array();
            if (!is_array($save)) {
                $mtp[Mail::F_TYPE] = $save;
            } else {
                $mtp[Mail::F_TYPE] = $title;
                $mtp[Mail::F_MODEL] = $save["model"];
                $mtp[Mail::F_OBJECT_ID] = $save["object_id"];
            }

            $mtp[Mail::F_CONTENT] = $content;
            $mtp[Mail::F_EMAIL] = $emails;
            $mtp[Mail::F_ACTIVE] = 1;
            $mtp[Mail::F_DATA_ADD] = time();
            $mtp["sended"] = $sended ? 1 : 0;
            $mtp["error"] = self::$lastError;
            $ob = Mail::create($mtp, Mail::TCLASS);
            $ob->save();
        }


        return $sended;
    }

    private static $tmpTemplateData;

    private static function getMatchetTemplate($regs)
    {

        $t = self::getTemplateBySystemName($regs[1]);
        $ret = str_replace(array("{", "}"), array("{ldelim", "{rdelim}"), $t->getContent());//self::buildTemplateContent($t, self::$tmpTemplateData));
        $ret = str_replace("{ldelim", "{ldelim}", $ret);
        return $t->getContent();
    }

    private static function buildTemplateContent($template, $data)
    {
        $content = $template->getContent();

        self::$tmpTemplateData = $data;
        $content = preg_replace_callback("/\[\[(\w+?)\]\]/", array(__CLASS__, "getMatchetTemplate"), $content);
        $content = preg_replace_callback("/\[\[(\w+?)\]\]/", array(__CLASS__, "getMatchetTemplate"), $content);
        $content = preg_replace_callback("/\[\[(\w+?)\]\]/", array(__CLASS__, "getMatchetTemplate"), $content);

        ob_start();
        eval("?> " . $content . "<?");
        $content = ob_get_contents();
        ob_clean();

        return $content;
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


        $settings = \Arrow\Models\Settings::getDefault()->getSetting("communication.mailer");

        // Create the Transport
        if (self::$specialConf)
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


    /**
     * wyciaga z kontentu obrazki i zamienia je z cidami
     *
     * @param string $content - tresc wiadomosci
     * @param array $img - zwraca tablice z cidami i odpowiadającymi mu obrazkami
     * return przeparsowaną treść z cid obrazków
     */
    private static function parseEmbededImages($content, &$img)
    {
        // wyciągnięcie wszystkich ścierzek do zdjęć
        /*$ret = null;
         $new_content = $content ;
         $aims = null ;
         $pattern = "/src=\"(.*?)\"/" ;
         $it = preg_match_all( $pattern, $content, $aims ) ;

         if( $it > 0 and count($aims) > 0 ) {
            foreach( $aims[1] as $key => $aim ) {
            $el["path"] = $aim ;
            //$el["cid"] = "img_$key" ;
            $new_content = str_replace( $aim, "http://".$_SERVER["HTTP_HOST"]."/".$el["path"], $new_content ) ;
            $ret[] = $el ;
            }
            }
            //$img = $ret ;


            */
        $pattern = "/src=\"(.*?)\"/";
        //return preg_replace( $pattern, "src=\""."http://".$_SERVER["HTTP_HOST"]."/".self::getBasePath(). "/$1\"", $content);
        return preg_replace($pattern, "src=\"" . "http://poznaj-swiat.pl/$1\"", $content);
    }


    private static function getBasePath()
    {
        return substr($_SERVER['SCRIPT_FILENAME'], 0, strlen($_SERVER['SCRIPT_FILENAME']) - strlen(strrchr($_SERVER['SCRIPT_FILENAME'], "\\")));
    }


}

?>