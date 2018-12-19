<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.01.2018
 * Time: 15:50
 */

namespace Arrow\Utils\Controllers;

use Arrow\Kernel;
use Arrow\Media\Models\ElementConnection;
use Arrow\Models\AnnotationRouteManager;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Shop\Models\Persistent\Product;
use Arrow\Translations\Models\LanguageText;

use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DeveloperTools
 * @package Arrow\Utils\Controllers
 * @Route("/migrationservice")
 */
class MigrationService extends \Arrow\Models\Controller
{
    function __construct()
    {
        $this->conf = [
            "db" => Project::getInstance()->getDB(),
            "apiURL" => "https://www.finalsale.pl/migrate/prepareData",
            "apiTables" => "https://www.finalsale.pl/migrate/getTables"
        ];
    }

    private function call($service, $postData = [])
    {
        $post_data_json = json_encode($postData);

        $ch = curl_init($service);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, (empty($postData) ? "GET" : "POST"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_json);

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    /**
     * @Route("/index")
     */
    public function index()
    {

        $this->userMigration();
        die();

        $this->json([
            "result" => true
        ]);
    }

    /**
     * @Route("/getData")
     */
    public function getData(Request $request) {
        $table = $request->get("table");
        $cond = $request->get("condField");

        $data = $this->call($this->conf["apiURL"], ["table" => $table, "condField" => $cond]);
        $data = json_decode($data);

        $this->json([
            "result" => $data
        ]);
    }

    /**
     * @Route("/userMigration")
     */
    public function userMigration() {
        $db = $this->conf["db"];

        $q = $db->prepare("SELECT * from access_user_fs");
        $q->execute();
        $fsUsers = $q->fetchAll(\PDO::FETCH_ASSOC);

        $q = $db->prepare("SELECT * from customer_addresses_fs");
        $q->execute();
        $fsUsersAdressess = $q->fetchAll(\PDO::FETCH_ASSOC);

        $fsUserAdressByUserID = [];
        foreach ($fsUsersAdressess as $adreeesKey => $adress) {
            $fsUserAdressByUserID[$adress["user_id"]] = $adress;
        }

        // LAST ID Z ACCESS USERS
        $lastId = 515712;

        foreach ($fsUsers as $key => $fsUser) {
            $lastId++;
            $fsUsers[$key]["login"] = $fsUser["login"] . "_finalsale";
            $fsUsers[$key]["id"] = $lastId;

            if (isset($fsUserAdressByUserID[$fsUser["id"]])) {
                $fsUserAdressByUserID[$fsUser["id"]]["user_id"] = $lastId;
            }

        }



        // RDY TO ISNERT




        echo "<pre>";
        print_r($fsUserAdressByUserID);
        die();

        $this->json([
            "result" => true
        ]);
    }

    /**
     * @Route("/getEsoData")
     */
    public function getEsoData(Request $request) {
        $db = $this->conf["db"];
        $table = $request->get("table");

        $q = $db->prepare("SELECT * from $table");
        $q->execute();
        $table_fields = $q->fetchAll(\PDO::FETCH_ASSOC);

        $this->json([
            "result" => $table_fields
        ]);
    }

    /**
     * @Route("/getTables")
     */
    public function getTables() {
        $data = $this->call($this->conf["apiTables"]);
        $data = json_decode($data);

        $this->json([
            "result" => $data
        ]);
    }

    /**
     * @Route("/setData")
     */
    public function setData(Request $request) {
        $data = $request->get("data");
        $table = $request->get("table");

        $db = $this->conf["db"];

        foreach ($data as $item) {
            $preparedValues = "";
            $preparedColumns = "";
            foreach ($item as $key => $el) {
                $preparedColumns .= $key . ",";
                $preparedValues .= "'".$el."'". ",";
            }

            $preparedColumns = substr($preparedColumns, 0, -1);
            $preparedValues = substr($preparedValues, 0, -1);

            $query = "insert into $table ($preparedColumns) values ($preparedValues)";

            $db->exec($query);
        }

        return[true];
    }

    /**
     * @Route("/getColumns")
     */
    public function getColumns(Request $request) {
        $db = $this->conf["db"];
        $table = $request->get("table");

        $q = $db->prepare("DESCRIBE $table");
        $q->execute();
        $table_fields = $q->fetchAll(\PDO::FETCH_COLUMN);

        $this->json([
            "result" => $table_fields
        ]);
    }
}
