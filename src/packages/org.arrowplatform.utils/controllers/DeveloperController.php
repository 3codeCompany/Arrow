<?php

namespace Arrow\Package\Utils;

use Arrow\Models\IAction;
use Arrow\ORM\Persistent\Criteria,
    Arrow\Access\Auth,
    Arrow\ViewManager, Arrow\RequestContext,
    Arrow\Models\Operation,
    Arrow\Models\ApplicationException,
    Arrow\Models\ExceptionContent,
    Arrow\Access\AccessAPI,
    Arrow\Models\Project, Arrow\Models\View;
use Arrow\ORM\DB;
use Arrow\Package\Common\AdministrationLayout;
use Arrow\Package\Common\EmptyLayout;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */

class DeveloperController extends \Arrow\Models\Controller
{

    private $dbDumpDir;
    private $deployDir;

    protected function __construct()
    {
        $this->dbDumpDir = $dbDumpDir = Project::getInstance()->getPath() . "/data/db_dump/";
        $this->dbDumpDir = $dbDumpDir = Project::getInstance()->getPath() . "/data/deploy/";
    }

    public function developer_clear_cache(IAction $action, RequestContext $request)
    {
        \Arrow\Controller::$project->clearCache();
        $this->back();
    }

    public function developer_console(Action $view, RequestContext $request, $package)
    {
        $view->setLayout(new AdministrationLayout(), new EmptyLayout());
        $file = Project::getInstance()->getPath() . "/data/last_dev_console.txt";
        if (file_exists($file))
            $code = file_get_contents($file);
        else
            $code = "print 'Hello';";

        $view->assign("code", $code);
    }

    public function developer_console_execute(Action $view, RequestContext $request, $package)
    {
        Console::init();
    }

    public function developer_saveConsoleCode(IAction $action, RequestContext $request)
    {

        file_put_contents(Project::getInstance()->getPath() . "/data/last_dev_console.txt", $request["code"]);
    }


    public function developer_db_dump_list(Action $view, RequestContext $request, $package)
    {
        $iterator = new \DirectoryIterator($this->dbDumpDir);
        $view->assign("filesiterator", $iterator);
    }

    public function developer_db_dump(IAction $action, RequestContext $request)
    {
        $settings = \Arrow\Models\Settings::getDefault()->getSetting("application.db");
        $dumpFile = $this->dbDumpDir . date("Y_m_d_H_i_s") . ".sql";
        $tmpConsoleOutputFile = ARROW_CACHE_PATH . "/shell_out.txt";

        file_put_contents($dumpFile, "SET AUTOCOMMIT = 0; " . PHP_EOL . "SET FOREIGN_KEY_CHECKS=0;" . PHP_EOL);
        $command = "mysqldump --user={$settings["user"]}  --password={$settings["password"]} --_add-drop-table  --skip-comments {$settings["name"]} >> \"" . $dumpFile . "\" 2> $tmpConsoleOutputFile";
        $ret = shell_exec($command);
        $out = $ret ? $ret : file_get_contents($tmpConsoleOutputFile);
        if (strpos($out, "Got error") !== false) {
            unlink($dumpFile);
            throw new ApplicationException(new ExceptionContent("Archiver problem", $out));
        }

        if ($request["zip"]) {
            $command = "zip -r $dumpFile.zip $dumpFile 2> $tmpConsoleOutputFile";
            $ret = shell_exec($command);
            $out = $ret ? $ret : file_get_contents($tmpConsoleOutputFile);
            unlink($dumpFile);
            if (file_exists($dumpFile . ".zip")) {
                return true;
            } else {
                throw new ApplicationException(new ExceptionContent("Archiver problem", $out));
            }
        }

        return true;
    }

    public function developer_db_dump_delete(IAction $action, RequestContext $request)
    {
        unlink($this->dbDumpDir . $request["file"]);
    }

    public function developer_db_dump_get(IAction $action, RequestContext $request)
    {
        header('Content-disposition: attachment; filename=' . \Arrow\Controller::getRunConfiguration() . "_" . $request["file"]);
        header('Content-type: text/plain');
        readfile($this->dbDumpDir . $request["file"]);
        exit();
    }

    /*public function developer_deploy(Action $view, RequestContext $request, $package)
    {
        $fileCheck = $this->dbDumpDir . DIRECTORY_SEPARATOR . "last_dump.txt";
        if (!file_exists($fileCheck))
            $fileCheck = Project::getInstance()->getPath() . DIRECTORY_SEPARATOR . "index.php";
        $lastDump = filemtime($fileCheck);
        $view->assign("lastDeploy", date("Y-m-d H:i:s"));
        $view->assign("currDeployPackage", "23423234234");
    }*/

    public function developer_deploy(IAction $action, RequestContext $request)
    {

        $response = "";
        $lastDumpFile = $this->dbDumpDir . "last_dump.txt";
        $deployFile = $this->dbDumpDir . "deploy.zip";

        if ($request["getLast"] != null) {


            if (!file_exists($lastDumpFile))
                $response = filemtime(Project::getInstance()->getPath() . DIRECTORY_SEPARATOR . "index.php") . "000";
            else
                $response = filemtime($this->dbDumpDir . DIRECTORY_SEPARATOR . "last_dump.txt") . "000";
        }

        if (isset($_FILES["deploy"])) {

            move_uploaded_file($_FILES["deploy"]["tmp_name"], $deployFile);
            $response .= "Catching deploy file";
        }

        if ($request["deploy"] != null) {


            if (!file_exists($deployFile)) {

                $response = "NOTHING TO DEPLOY: " . $deployFile;
            } else {

                $response = "Unpacking " . PHP_EOL;
                system("unzip -o {$deployFile} 'application/*' -d " . ARROW_APPLICATION_PATH);
                system("unzip -o {$deployFile} 'arrowplatform/*' -d " . dirname(ARROW_ROOT_PATH));
                system("unzip -o {$deployFile} 'resources/*' -d " . ARROW_APPLICATION_PATH);
                system("unzip -o {$deployFile} 'libs/*' -d " . ARROW_APPLICATION_PATH);

                //pliki glowne
                //system ("unzip -o {$deployFile} 'application/*.*' -d ". ARROW_APPLICATION_PATH);

                $response .= "Saving last dump data " . PHP_EOL;
                file_put_contents($lastDumpFile, "");
                $response .= "Clearing cache " . PHP_EOL;
                \Arrow\Controller::$project->clearCache();
                unlink($deployFile);

                $response .= "Checking packages `setup's` " . PHP_EOL;
                try {
                    AccessAPI::checkInstallation();
                } catch (\Arrow\Exception $ex) {
                    AccessAPI::setup();
                    $response .= "Access API setup finish";
                }

                $response .= "Importing access matrix " . PHP_EOL;
                // AccessAPI::importAccessMatrixFromPackages();

                $response .= "Done " . PHP_EOL;

            }
        }
        print $response;
        exit();
    }


    public function developer_routing(Action $view, RequestContext $request, $package)
    {
        $packages = \Arrow\Controller::$project->getPackages();
        $view->assign("packages", $packages);

        if ($request["currPackage"])
            $_SESSION["devCurrPackage"] = $request["currPackage"];

        if (!isset($_SESSION["devCurrPackage"])) {
            $keys = array_keys($packages);
            $_SESSION["devCurrPackage"] = reset($keys);
        }
        $view->assign("currPackage", $_SESSION["devCurrPackage"]);

        $routeStructure = \Arrow\Models\Dispatcher::getDefault()->getRouteStructure($_SESSION["devCurrPackage"]);
        $views = $routeStructure['views'];
        $paths = array();
        foreach ($views as $key => $row)
            $paths[$key] = $row[0];
        array_multisort($paths, SORT_ASC, $views);


        $view->assign("routeStructure", $routeStructure);
        $view->assign("views", $views);

        $array = array();
        foreach ($paths as $path) {
            $path = trim($path, '/');
            $list = explode('/', $path);
            $n = count($list);

            $arrayRef = & $array; // start from the root
            for ($i = 0; $i < $n; $i++) {
                $key = $list[$i];
                $arrayRef = & $arrayRef[$key]; // index into the next level
            }
        }
        $view->assign("viewsTree", $array);


    }

    /* private function getPackageViews($packageNamespace){
         $views = array();


         return $views;
     }*/


    public function developer_log_list(Action $view, RequestContext $request, $package)
    {
        $iterator = new \DirectoryIterator(ARROW_CACHE_PATH . "/../logs");
        $view->assign("filesiterator", $iterator);

    }

    public function developer_log_view(Action $view, RequestContext $request, $package)
    {
        $view->assign("filename", $request["file"]);
        $content = file_get_contents(ARROW_CACHE_PATH . "/../logs/" . $request["file"]);
        $view->assign("content", $content);
    }


    public function developer_data_structure(Action $view, RequestContext $request)
    {
        $view->setLayout(new AdministrationLayout());
        $db = DB::getDB();
        $view->assign("schema", $db->getSchema());
    }

    public function developer_data_addTable( Action $view, RequestContext $request ){}




}