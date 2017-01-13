<?php
namespace Arrow\Communication;


class ContactForm extends \Arrow\ORM\ORM_Arrow_Communication_ContactForm{

    public function beforeObjectCreate(\Arrow\ORM\PersistentObject $object)
    {
        parent::beforeObjectCreate($object);
        $this->setValue('date', date("Y-m-d H:i:s"));

    }

    public function afterObjectCreate(\Arrow\ORM\PersistentObject $object)
    {
        parent::afterObjectCreate($object);

        //TODO uruchomic wysyłanie maila

        /*
        $template = CommMailerAPI::getTemplateBySystemName("contact", MailTemplate::TCLASS);
        $topic = "Zapytanie ze strony logonabalonie.pl";

        $data = $initialValues;
        $data["object"] = $object;
        CommMailerAPI::sendTemplate($template, "biuro@logonabalonie.pl", $data,
            array(
                "topic" => $topic,
                "model" => "communication.ContactForm",
                "object_id" => $object->getPKey()
            ), false);



        $template = CommMailerAPI::getTemplateBySystemName("contact_to_client", MailTemplate::TCLASS);
        $topic = "Dziękujemy za kontakt";
        CommMailerAPI::sendTemplate($template, $initialValues["email"], $data,
            array(
                "topic" => $topic,
                "model" => "communication.ContactForm",
                "object_id" => $object->getPKey()
            ), false);
        */
    }





	
}
?>