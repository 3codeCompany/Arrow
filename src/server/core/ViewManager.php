<?php

namespace Arrow;

use Arrow\Models\Action;
use Arrow\Models\Dispatcher;
use \Arrow\Models\Project, Arrow\Access\AccessAPI, Arrow\Models\View;
use Arrow\Models\TemplateLinker;

/**
 * View control
 *
 * @version 1.0
 * @license  GNU GPL
 * @author Artur Kmera <artur.kmera@arrowplatform.org>
 */
class ViewManager
{

    private static $inCompilationMode = false;

    private $action;

    private static $currentView = array();


    private static $staticCacheFile;

    private static $staticCacheExists;


    public static function getInCompilationMode()
    {
        return self::$inCompilationMode;
    }


    /**
     * Returns current processed view
     * @return ViewManager
     */
    public static function getCurrentView()
    {
        return end(self::$currentView);
    }

    /**
     * @return View
     */
    public function get()
    {
        return $this->action;
    }

    /**
     * @param Action $view
     */
    public function __construct(\Arrow\Models\Action $view)
    {
        $this->action = $view;
        self::$currentView[] = $this;
    }


    public function display(RequestContext $request = null)
    {
        $view = $this->action;
        $request = $request ? $request : RequestContext::getDefault();

        /**
         * Access check
         */
        $action = trim(str_replace(DIRECTORY_SEPARATOR, "_", $view->getShortPath()), "_");
        $instance = $view->getController();

        if (!$view->isAccessible())
            AccessAPI::accessDenyProcedure($view->getPath() . " " . $view->getPackage());

        $instance->view = $view;
        $instance->eventRunBeforeAction($view, $request);
        $instance->$action($view, $request);
        $instance->eventRunAfterAction($view, $request);

        $layout = $view->getLayout();

        if ($view->getGenerator()) {
            if (is_string($view->getGenerator()))
                return $view->getGenerator();
            return $view->getGenerator()->generate();
        }


        if (!($layout instanceof \Arrow\Models\AbstractLayout)) {
            throw new Exception("View " . $view . " has no layout");
        }

        $layout->createLayout($this);
        $ret = $view->_require($this->checkCompile($view), $request);
        array_pop(self::$currentView);
        return $ret;
    }


    public function checkCompile(Action $view)
    {
        $rq = RequestContext::getDefault();

        $nameAddon = str_replace(DIRECTORY_SEPARATOR, "_", $this->action->getPath() . "_" . $this->action->getPackage() . "_" . ($rq->isXHR() ? "xhr" : "synchronus"));
        $nameAddon = substr($nameAddon, 1);
        if ($this->action->getCompilationId())
            $nameAddon .= "_" . $this->action->getCompilationId();

        $pathToCachedFile = ARROW_CACHE_PATH . DIRECTORY_SEPARATOR . "templates/" . $nameAddon . ".php";


        if (Project::$cacheFlag & Project::CACHE_REFRESH_TEMPLATES || Project::$cacheFlag & Project::CACHE_REFRESH_TEMPLATES_FORCE || !file_exists($pathToCachedFile)) {
            $generate = false;
            $layoutFile = $view->getLayout()->getLayoutFile();
            $actionFile = $this->action->getFile();

            if (Project::$cacheFlag & Project::CACHE_REFRESH_TEMPLATES_FORCE)
                $generate = true;
            elseif (!file_exists($pathToCachedFile))
                $generate = true;
            elseif (filemtime($layoutFile) > filemtime($pathToCachedFile))
                $generate = true;
            elseif (filemtime($actionFile) > filemtime($pathToCachedFile))
                $generate = true;


            if ($generate) {
                self::$inCompilationMode = true;
                $this->action->generate($pathToCachedFile);
            }

        }


        return $pathToCachedFile;
    }


    public static function startCache($id, $time = -1, $conditions = false)
    {
        self::$staticCacheFile = ARROW_CACHE_PATH . '/static/' . $id . (($conditions) ? "_" . md5(json_encode($conditions)) : '') . '.txt';

        if (!(Project::$cacheFlag & Project::CACHE_REFRESH_STATIC) && file_exists(self::$staticCacheFile) && ($time == -1 || filemtime(self::$staticCacheFile) - time() < $time)) {
            self::$staticCacheExists = true;
            readfile(self::$staticCacheFile);
        } else {
            self::$staticCacheExists = false;
            ob_start();
        }
        return !self::$staticCacheExists;
    }

    public static function endCache()
    {
        if (!self::$staticCacheExists) {
            $fp = fopen(self::$staticCacheFile, 'w');
            fwrite($fp, ob_get_contents());
            fclose($fp);
            ob_end_flush();
        }
    }

    public static function clearCache($id, $conditions = false)
    {
        $file = self::$staticCacheFile = ARROW_CACHE_PATH . '/static/' . $id . (($conditions) ? "_" . md5(json_encode($conditions)) : '') . '.txt';
        unlink($file);
    }

    public static function isCached($id, $time = -1, $conditions = false)
    {
        self::$staticCacheFile = ARROW_CACHE_PATH . '/static/' . $id . (($conditions) ? "_" . md5(json_encode($conditions)) : '') . '.txt';
        if (!(Project::$cacheFlag & Project::CACHE_REFRESH_STATIC) && file_exists(self::$staticCacheFile) && ($time == -1 || filemtime(self::$staticCacheFile) - time() < $time))
            return true;
        else
            return false;
    }

}