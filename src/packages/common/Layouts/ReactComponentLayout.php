<?php

namespace Arrow\Common\Layouts;

use App\Models\Common\AdministrationExtensionPoint;
use Arrow\Access\Models\Auth;
use Arrow\ConfigProvider;
use Arrow\Kernel;
use Arrow\Models\Project;
use Arrow\StateProvider;
use Arrow\Translations\Models\Translations;
use Arrow\ViewManager;
use Symfony\Component\HttpFoundation\Request;
use function array_merge;
use function file_get_contents;
use function json_encode;
use Symfony\Component\HttpFoundation\Session\Session;


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

        $this->data = array_merge($this->data, ["layout" => $this->prepareData()]);
        ob_start();
        include __DIR__ . "/ReactComponentLayout.phtml";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

    }


    public function prepareData()
    {

        $container = Kernel::getProject()->getContainer();
        $data = [];
        /** @var Auth $auth */
        $auth = $container->get(Auth::class);

        /** @var Session $session */
        $session = $container->get(Session::class);

        /** @var Request $request */
        $request = $container->get(Request::class);

        $user = $auth->getUser();
        $data["user"] = $user ? [
            "login" => $user->_login(),
            "id" => $user->_id(),
            "isDev" => $user->isInGroup("Developers")
        ] : null;
        $data["config"] = ConfigProvider::get("panel");

        $data["config"] = ConfigProvider::get("panel");
        $data["onlyBody"] = $this->onlyBody;
        if ($user) {
            $data["allowedElements"] = (new AdministrationExtensionPoint())->getPreparedData();
        } else {
            $data["allowedElements"] = ["menu" => [], "dashboard" => []];
        }

        $data["language"] = $session->get("language", "pl");

        $data["languages"] = (new AdministrationExtensionPoint())->getActiveLanguages();

        $data["ARROW_DEV_MODE_FRONT"] = (bool)\getenv("APP_DEBUG_WEBPACK_DEV_SERVER") || $request->cookies->get("ARROW_DEBUG_WEBPACK_DEV_SERVER");

        if (!$data["ARROW_DEV_MODE_FRONT"]) {
            $tmp = file_get_contents(ARROW_DOCUMENTS_ROOT . "/assets/dist/compilation-hash-pl.txt");
            $data["jsCompilationData"] = explode("|", $tmp);
        }

        return $data;

    }

}
