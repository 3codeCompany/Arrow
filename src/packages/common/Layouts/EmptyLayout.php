<?php

namespace Arrow\Common\Layouts;

use AccessManager;
use Arrow\ViewManager;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;

class EmptyLayout extends \Arrow\Models\AbstractLayout
{

    public function render()
    {
        ob_start();
        include __DIR__ . "/EmptyLayout.phtml";
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

    }

}

?>
