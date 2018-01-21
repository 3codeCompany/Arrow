<?php

namespace Arrow\Common\Layouts;
use Arrow\ConfigProvider;
use Arrow\Access\Models\Auth;
use Arrow\ORM\Table;

use const ARROW_DOCUMENTS_ROOT;

class AdministrationLayout extends \Arrow\Models\AbstractLayout
{


    public function prepareData()
    {

        $config = ConfigProvider::get("panel");

        $data = [
            "applicationTitle" => $config["title"],
            "applicationIcon" => $config["icon"]
        ];

        $user = Auth::getDefault()->getUser();

        if ($user && $user->isInGroup("Developers")) {
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


    public function render()
    {
        $this->data = array_merge($this->data, $this->prepareData());
        ob_start();
        include __DIR__ . "/admin/index2.phtml";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

    }

}

?>