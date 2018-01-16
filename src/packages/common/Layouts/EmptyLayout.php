<?php

namespace Arrow\Common\Layouts;

use
    Arrow\ORM\Persistent\Criteria,
    Arrow\Access\Models\Auth,
    AccessManager,
    \Arrow\RequestContext,
    \Arrow\Access\Models\AccessAPI,
    Arrow\ViewManager;
use Arrow\Router;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;

class EmptyLayout extends \Arrow\Models\AbstractLayout
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

    public function generate()
    {
        ob_start();
        include __DIR__."/EmptyLayout.phtml";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

    }


}

?>