<?php


namespace Arrow\Media\Models\Helpers;


use Arrow\Media\Models\Element;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DownloadHelper
{

    private $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT;

    public function getDownloadResponseFromElement(Element $element)
    {

    }
    public function getDownloadResponseFromPath($name, $path){
        $response = new BinaryFileResponse($path);

        // To generate a file download, you need the mimetype of the file
        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        if ($mimeTypeGuesser->isSupported()) {
            // Guess the mimetype of the file according to the extension of the file
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess($path));
        } else {
            // Set the mimetype of the file manually, in this case for a text file is text/plain
            $response->headers->set('Content-Type', 'text/plain');
        }

        $response->headers->set('Content-Length', filesize($path));

        $response->setContentDisposition(
            $this->disposition,
            $name
        );

        return $response;

    }
}