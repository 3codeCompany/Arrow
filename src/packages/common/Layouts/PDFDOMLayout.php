<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 30.07.2018
 * Time: 13:24
 */

namespace Arrow\Common\Layouts;


use Arrow\Models\IResponseHandler;
use Dompdf\Dompdf;
use Dompdf\Options;

class PDFDOMLayout extends \Arrow\Models\AbstractLayout implements IResponseHandler
{

    private $fileName = "pdf-document";

    /**
     * @var Dompdf
     */
    private $dompdf;


    public function __construct(?string $template, array $data = [])
    {
        parent::__construct($template, $data);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        //$options->setFontDir( ARROW_PROJECT . "/public/assets/erp/pdf-fonts" );

        $this->dompdf = new Dompdf($options);
    }

    public function setFileName($str)
    {
        $this->fileName = $str;
    }


    public function getDOMPDF(){
        $this->dompdf->loadHtml($this->render());
        $this->dompdf->setPaper('A4', 'portail');
        $this->dompdf->render();
        return $this->dompdf;
    }

    public function send()
    {

        $this->dompdf->loadHtml($this->render());
        $this->dompdf->setPaper('A4', 'portail');
        $this->dompdf->render();
        $this->dompdf->stream($this->fileName, ["Attachment" => 0]);

    }


    public function render()
    {
        ob_start();
        include __DIR__ . "/PDFDOMLayout.phtml";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

    }

}