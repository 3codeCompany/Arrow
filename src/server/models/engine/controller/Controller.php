<?php

namespace Arrow\Models;

use Arrow\Access\Models\AccessAPI;
use Arrow\Access\Models\Auth;
use Arrow\RequestContext;
use Arrow\Router;
use const ARROW_APPLICATION_PATH;
use const ARROW_DOCUMENTS_ROOT;
use const DIRECTORY_SEPARATOR;
use Exception;
use function file_exists;
use function str_replace;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 13:21
 * To change this template use File | Settings | File Templates.
 */
//todo - tutaj wszystko ok ale zrobic porzadek z singletonami trzeba

abstract class Controller implements IController
{


    final protected function getUser()
    {
        return Auth::getDefault()->getUser();
    }

    final protected function render(AbstractLayout $layout, $data = [])
    {

        $template = ARROW_DOCUMENTS_ROOT . str_replace("/", DIRECTORY_SEPARATOR, $this->action->getTemplatePath()) . ".phtml";

        if (!file_exists($template)) {
            throw new \Exception("Template file dont exist: " . $template);
        }

        $layout->setTemplate($template);
        $layout->setData($data);


        $response = new Response(
            $layout->generate(),
            Response::HTTP_OK,
            array('content-type' => 'text/html')
        );


        $response->send();
    }

    final protected function json($data = [])
    {
        (new JsonResponse($data))->send();
        exit();
    }


    public function eventRunBeforeAction(Action $action)
    {
    }


}
