<?php

namespace Arrow\Common\Layouts;

use function array_merge;
use Arrow\Access\Models\Auth;
use Arrow\Models\Action;

use Arrow\ViewManager;
use Symfony\Component\HttpFoundation\Request;


class ReactComponentLayout extends \Arrow\Models\AbstractLayout
{


    private $template;

    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function generate()
    {

        $this->data  = array_merge($this->data, $this->prepareData());

        ob_start();
        include __DIR__ . "/ReactComponentLayout.phtml";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

    }


    public function prepareData()
    {

        $data = [];

        if (!isset($_SESSION["inDev"])) {
            $_SESSION["inDev"] = false;
        }
        if (isset($_REQUEST["inDev"])) {
            $_SESSION["inDev"] = $_REQUEST["inDev"];
        }

        $user = Auth::getDefault()->getUser();

        if ($user->isInGroup("Developers")) {
            $data["developer"] = true;
        } else {
            $data["developer"] = false;
        }

        $data["user"] = $user;

        $manifest = false;
        $manifestFile = ARROW_DOCUMENTS_ROOT . "/assets/dist/webpack-assets.json";
        if (file_exists($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
        }

        $data["webpackManifest"] = $manifest;

        return $data;


    }


}