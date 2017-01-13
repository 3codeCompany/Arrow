<?php
namespace Arrow\Communication;

class Loader
{

    public final static function registerAutoload()
    {
        {
            $classes = array(
                'arrow\\package\\communication\\administrationextensionpoint' => '/panel/AdministrationExtensionPoint.php',
                'arrow\\package\\communication\\mailerapi' => '/mailer/MailerAPI.php',
                'arrow\\package\\communication\\contactform' => '/ContactForm.php',
                'arrow\\package\\communication\\mailtemplate' => '/mailer/MailTemplate.php',
                'arrow\\package\\communication\\sendedmail' => '/mailer/SendedMail.php',
                'commimapclient' => '/messages/CommIMAPClient.php',
                'comminternalmessage' => '/internal/CommInternalMessage.php',
                'commmailbox' => '/messages/CommMailbox.php',
                'commmailboxmessage' => '/messages/CommMailboxMessage.php',
                'commmessage' => '/messages/CommMessage.php',
                'commmessageattachments' => '/messages/CommMessageAttachments.php',
                'commmessagemanager' => '/messages/CommMessageManager.php',
                'commmessageparser' => '/messages/CommMessageParser.php',
                'commnewsletteradressee' => '/newsletter/CommNewsletterAdressee.php',
                'commnewslettercampaign' => '/newsletter/CommNewsletterCampaign.php',
                'commnewslettercampaigngroup' => '/newsletter/CommNewsletterCampaignGroup.php',
                'commnewslettergroup' => '/newsletter/CommNewsletterGroup.php',
                'commnewslettergroupsubscriber' => '/newsletter/CommNewsletterGroupSubscriber.php',
                'commnewsletterqueue' => '/newsletter/CommNewsletterQueue.php',
                'phpmailer' => '/mailer/mailer/phpmailer.php',
                'phpmailerexception' => '/mailer/mailer/phpmailer.php',
                'pop3' => '/mailer/mailer/class.pop3.php',
                'smtp' => '/mailer/mailer/class.smtp.php',
                'arrow\\package\\communication\\communicationcontroller' => '/../controllers/CommunicationController.php',
            );
            \Arrow\Models\Loader::registerClasses(__DIR__."/models", $classes);

        }


    }

}