<?php
namespace Arrow\Communication\Models\Mailer;


use Arrow\ORM\ORM_Arrow_Communication_Models_Mailer_QueueElement;
use function file_put_contents;

class QueueElement extends ORM_Arrow_Communication_Models_Mailer_QueueElement
{

    public function setContent($content)
    {
        file_put_contents(ARROW_DOCUMENTS_ROOT . "/data/mailerQueue/" . $this->_id() . ".txt", $content);
    }

    public function getContent()
    {
        file_get_contents(ARROW_DOCUMENTS_ROOT . "/data/mailerQueue/" . $this->_id() . ".txt");
    }


}

?>
