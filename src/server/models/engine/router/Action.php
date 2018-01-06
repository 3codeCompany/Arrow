<?php

namespace Arrow\Models;

/**
 * Created by JetBrains PhpStorm.
 * User: Artur
 * Date: 22.09.12
 * Time: 12:26
 * To change this template use File | Settings | File Templates.
 */
use Arrow\Exception;
use Arrow\Access\Models\AccessAPI;
use Arrow\RequestContext;
use function var_dump;

class Action implements \ArrayAccess, IAction
{


    private $path;
    private $shortPath;
    private $controller;
    private $layout;
    private $XHRLayout;
    private $package;
    private $generator;


    /**
     * @var ViewParser[]
     */
    private $parsers = array();
    /**
     * @var IParsersProvider[]
     */
    private $parserProviders = array();
    private $compilationId = "";

    /**
     * //todo make private
     * @var array
     */
    public $vars = array();

    /**
     * @param $path View path
     * @param $controller
     * @param $package
     */
    public function __construct($path, $shortPath, $controller, $package)
    {
        $this->path = str_replace("/", DIRECTORY_SEPARATOR, $path);
        $this->shortPath = str_replace("/", DIRECTORY_SEPARATOR, $shortPath);
        $this->controller = $controller;
        $this->package = $package;

    }

    public function exists()
    {

        $action = trim(str_replace(DIRECTORY_SEPARATOR, "_", $this->getShortPath()), "_");


        return method_exists($this->getController(), $action);
    }


    /**
     * @param string $compilationId
     */
    public function setCompilationId($compilationId)
    {
        $this->compilationId = $compilationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompilationId()
    {
        return $this->compilationId;
    }


    public function fetch(RequestContext $request = null)
    {
        $viewManager = new \Arrow\ViewManager($this);
        return $viewManager->display($request);
    }

    public function getRequest()
    {
        return RequestContext::getDefault();
    }

    public function getRoute()
    {
        return $this->package . $this->path;
    }

    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        $c = $this->controller;
        return $c::getInstance();
    }


    public function getPackage()
    {
        return $this->package;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getRoute()
    {
        return $this->package . $this->path;
    }

    public function setLayout(AbstractLayout $layout, AbstractLayout $XHRLayout = null)
    {
        $this->layout = $layout;
        $this->XHRLayout = $XHRLayout;
    }

    public function getLayout()
    {
        $rq = RequestContext::getDefault();
        return $rq->isXHR() && $this->XHRLayout ? $this->XHRLayout : $this->layout;
    }


    function __toString()
    {
        return $this->path;
    }

    public function isAccessible()
    {
        return AccessAPI::checkAccess("view", "show", $this->getPath(), "");
    }

    public function assign($var, $value)
    {
        $this->vars[$var] = $value;
    }

    public function addParser(ViewParser $parser)
    {
        $this->parsers[] = $parser;
    }

    public function addParserProvider(IParsersProvider $provider)
    {
        $this->parserProviders[] = $provider;
    }

    /**
     * @return \Arrow\Models\ViewParser[]
     */
    public function getParsers()
    {
        return $this->parsers;
    }


    //todo disable path in argument
    //todo eliminate request from templates
    public function _require($path, $request = null)
    {

        ob_start();
        require($path);
        $content = ob_get_contents();
        ob_clean();

        return $content;
    }


    /**
     * @return mixed
     */
    public function getShortPath()
    {
        return $this->shortPath;
    }

    /**
     * @param mixed $generator
     */
    public function setGenerator($generator)
    {
        $this->generator = $generator;
    }

    /**
     * @return mixed
     */
    public function getGenerator()
    {
        return $this->generator;
    }


    //todo uporzadkowac
    public function includeView()
    {
        $file = $this->getFile();

        if (file_exists($file)) {
            return file_get_contents($file);
        } else {
            $parent = dirname($file);
            if (!file_exists($parent)) {
                if (!@mkdir($parent, 0777, true)) {
                    throw new Exception("Can't create action  dir: " . $parent);
                }
            }
            $phpCode = "<?\n /* @var \$this \\Arrow\\Models\\View */\n/* @var \$request \\Arrow\\RequestContext */\n ?>\n\n";

            if (!@file_put_contents($file,
                $phpCode . "View: " . $this->getPath() . "\n package: " . $this->getPackage() . "\n file: " . $file)
            ) {
                throw new Exception("Can't create action  file: " . $file);
            }
            chmod($file, 0777);
        }

        return file_get_contents($file);
    }

    public function generate($file = false)
    {

        $this->addParserProvider(new StandardParsersProvider());

        $controller = $this->getController();
        $controller->viewBeforeCompileEvent($this);
        if ($this->getLayout()) {
            $layoutSource = file_get_contents($this->getLayout()->getLayoutFile());
            $str = str_replace("[[include::view]]", $this->includeView(), $layoutSource);
        } else {
            $str = $this->includeView();
        }


        foreach ($this->parserProviders as $provider) {
            foreach ($provider->getParsers() as $parser) {
                $str = preg_replace_callback($parser->getRegularExpression(), $parser->getCallback(), $str);
            }
        }

        if ($this->parsers) {
            foreach ($this->parsers as $parser) {
                $str = preg_replace_callback($parser->getRegularExpression(), $parser->getCallback(), $str);
            }
        }

        $controller->viewAfterCompileEvent($this);

        if ($file) {
            file_put_contents($file, $str);
        } else {
            return $str;
        }

    }


    public function offsetExists($offset)
    {
        return isset($this->vars[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->vars[$offset])) {
            //throw new Exception(array("msg" => "Template var `{$offset}` not exists"));
            return "";
        }
        return $this->vars[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \Arrow\Exception(array("msg" => "Setting values of View not supported"));
    }

    public function offsetUnset($offset)
    {
        throw new \Arrow\Exception(array("msg" => "Unsetting values of View not supported"));
    }


    public function getFile()
    {
        $appFile = "." . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "views" . $this->path . ".phtml";

        if ($this->package != "app") {


            $file = ARROW_DOCUMENTS_ROOT . "/" . Project::getInstance()->getPackages()[$this->package] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . $this->shortPath . ".phtml";
            //fwrite(STDOUT, $file . "\n");

            if (file_exists($file) && !file_exists($appFile)) {
                return $file;
            }
        }

        return $appFile;

    }

}
