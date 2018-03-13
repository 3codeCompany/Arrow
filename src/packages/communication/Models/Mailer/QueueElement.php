<?php
namespace Arrow\Communication\Models\Mailer;


use Arrow\ORM\ORM_Arrow_Communication_Models_Mailer_QueueElement;
use Exception;
use function date;
use function file_exists;
use function file_put_contents;
use function mkdir;

class QueueElement extends ORM_Arrow_Communication_Models_Mailer_QueueElement
{

    private $folder;

    public function __construct($data = null, ?array $parameters = null)
    {
        parent::__construct($data, $parameters);
        $this->folder = ARROW_DOCUMENTS_ROOT . "/data/mailerQueue/" . date("Y-m");

    }


    public function setContent($content)
    {

        if (!file_exists($this->folder)) {
            mkdir($this->folder);
        }

        file_put_contents($this->folder . "/" . $this->_id() . ".txt", $content);
    }

    public function getContent()
    {

        $file = $this->folder . "/" . $this->_id() . ".txt";
        if (file_exists($file)) {
            return file_get_contents($this->folder . "/" . $this->_id() . ".txt");
        } else {
            throw new Exception("Content file dosn't exists [{$this->_id()}] ");
        }
    }

    public function getAttachments()
    {
        if ($this->_attachments() == "") {
            return [];
        }
        return explode(",,,", $this->_attachments());
    }


}

?>
