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


    public function send()
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($this->render());

        $dompdf->setPaper('A4', 'portail');
        $dompdf->render();
        $dompdf->stream("xxx",["Attachment" => 0]);

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