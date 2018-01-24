<?php

namespace Arrow\Common\Layouts;

use function array_merge;
use Arrow\Access\Models\Auth;
use Arrow\ConfigProvider;
use Arrow\Models\Action;

use Arrow\ViewManager;
use Symfony\Component\HttpFoundation\Request;


class ReactComponentLayout extends \Arrow\Models\AbstractLayout
{

    public function render()
    {
        $this->data = array_merge($this->data, $this->prepareData());
        ob_start();
        include __DIR__ . "/ReactComponentLayout.phtml";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

    }


    public function prepareData()
    {
        $data = [];

        $user = Auth::getDefault()->getUser();

        if ($user->isInGroup("Developers")) {
            $data["developer"] = true;
        } else {
            $data["developer"] = false;
        }

        $data["user"] = $user;

        $data["config"] = ConfigProvider::get("panel");


        $manifest = false;
        $manifestFile = ARROW_DOCUMENTS_ROOT . "/assets/dist/webpack-assets.json";
        if (file_exists($manifestFile)) {
            $manifest = json_decode(file_get_contents($manifestFile), true);
        }

        $data["webpackManifest"] = $manifest;

        return $data;

    }

}
