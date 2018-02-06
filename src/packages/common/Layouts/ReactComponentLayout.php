<?php

namespace Arrow\Common\Layouts;

use Arrow\Access\Models\Auth;
use Arrow\ConfigProvider;
use Arrow\Models\Project;
use Arrow\StateProvider;
use Arrow\ViewManager;
use Symfony\Component\HttpFoundation\Request;
use function array_merge;
use function file_get_contents;
use function json_encode;


class ReactComponentLayout extends \Arrow\Models\AbstractLayout
{

    private $onlyBody = false;

    public function setOnlyBody($onlyBody)
    {
        $this->onlyBody = $onlyBody;
    }

    public function render()
    {
        /** @var Request $request */
        $request = Project::getInstance()->getContainer()->get(Request::class);
        if ($request->isXmlHttpRequest()) {
            return json_encode($this->data);
        }

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

        $data["user"] = Auth::getDefault()->getUser();
        $data["config"] = ConfigProvider::get("panel");
        $data["compilationHash"] = file_get_contents(ARROW_DOCUMENTS_ROOT . "/assets/dist/compilation-hash-pl.txt");
        $data["config"] = ConfigProvider::get("panel");
        $data["onlyBody"] = $this->onlyBody;


        $tmp = file_get_contents(ARROW_DOCUMENTS_ROOT . "/assets/dist/compilation-hash-pl.txt");
        $data["jsCompilationData"] = explode("|", $tmp);

        $data[StateProvider::ARROW_DEV_MODE_FRONT] = Project::getInstance()->getContainer()->get(StateProvider::class)->get(StateProvider::ARROW_DEV_MODE_FRONT);


        return $data;

    }

}
