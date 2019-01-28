<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.01.2018
 * Time: 15:50
 */

namespace Arrow\Utils\Controllers;

use App\Models\Persistent\User;
use Arrow\Kernel;
use Arrow\Media\Models\ElementConnection;
use Arrow\Models\AnnotationRouteManager;
use Arrow\Models\Project;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Shop\Controllers\Esotiq\Services\PaymentSummary;
use Arrow\Shop\Models\Persistent\CustomerAddress;
use Arrow\Shop\Models\Persistent\Order;
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

    private static function statusMap($statusGeneral) {
        $statusGen = [
            1 => 1, // nowe => aktywne
            2 => 1, // w realizacji => aktywne
            6 => 4, // wyslane => zafuakturowane
            8 => 2, // wstrzymane => wstrzymane 2, 10, 11
            9 => 3,  // anulowane => anulwoane
            10 => 1, // => zrealizowane częsciowo => aktywne,
            11 => 3, // => nieodebrane => anulowane (?)
        ];

        $statusSynchro = [
            1 => 2, // nowe => aktywne
            2 => 2, // w realizacji => w realizacji
            6 => 6, // wyslane => zafuakturowane
            8 => 4, // wstrzymane => wstrzymane 2, 10, 11
            9 => 5,  // anulowane => anulwoane
            10 => 3, // zrealizowane częsciowo => w częsciowej realizacji
            11 => 1, // => nieodebrane => unknown (?)
        ];

        return [
            "status_general" => $statusGen[$statusGeneral],
            "status_synchro" => $statusSynchro[$statusGeneral]
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
        $this->mapProduct("shop_order_products_fs");

        //$this->mapVariant();
        die();
        $this->json([
            "result" => true
        ]);
    }

    /**
     * @Route("/kitUpdate")
     */
    public function kitUpdate() {
        $db = $this->conf["db"];

        $sth = $db->prepare("select * from shop_kits_fs");
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($res as $item) {
            $active = $item["active"] ? $item["active"] : 0;
            $id = $item["id"];
            $name = $item["name"];

            $q = "INSERT INTO `shop_kits`(`id`, `active`, `name`, `old_kit_id`) VALUES (null, $active, '$name', $id)";

            $db->exec($q);
        }
    }

    /**
     * @Route("/transferOrders")
     */
    // tworzysz tabele shop_orders_fs
    // dogrywsz do niej brakujące zamówienia z fs
    // uruchamiasz metode
    // tableka shop_order_fs jest gotowa do wgrania do shop_orders na bazie
    public function transferOrders() {
        $db = $this->conf["db"];

        $sth = $db->prepare("select * from shop_orders_fs");
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($res as $key => $value) {
            $rowId = $value["id"];
            $statusGen = self::statusMap($value["status"])["status_general"];
            $statusSynchro = self::statusMap($value["status"])["status_synchro"];
            $source = "finalsale.pl";
            $type = "finalsale";
            $subtype = "";
            $statusCiompl = 0;

            $q = "update shop_orders_fs set status_general = $statusGen, status_synchronization = $statusSynchro, status_completing = $statusCiompl, source = '$source', type = '$type', subtype = '$subtype' where id = $rowId";
            $db->exec($q);
        }
    }

    /**
     * @Route("/transferOrderProducts")
     */
    public function transferOrderProduct() {

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
     * @Route("/mapProduct")
     */
    public function mapProduct($table):void {
        $db = $this->conf["db"];

        $sth = $db->prepare("select * from $table");
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($res as $key => $value) {
            $oldProductId = $value["product_id"];
            $rowId = $value["id"];

            $sth = $db->prepare("select * from shop_products where old_fs_id = $oldProductId");
            $sth->execute();
            $r = $sth->fetchAll(\PDO::FETCH_ASSOC);

            if ($r) {
                $newProductId = $r[0]["id"];

                $q = "update $table set product_id = $newProductId where id = $rowId";
                $db->exec($q);
            }


        }

    }

    /**
     * @Route("/mapVariant")
     */
    // tabelka _fs musi zawierac sku jako variant_id -> finalsale -> Migrate.php -> rewriteTable()

    public function mapVariant():void {
        $db = $this->conf["db"];

        $sth = $db->prepare("select * from shop_order_products_fs");
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($res as $key => $value) {
            $sku = $value["variant_id"];
            $orderId = $value["order_id"];
            $rowId = $value["id"];

            $sth = $db->prepare("select * from shop_product_variant where sku = '$sku'");
            $sth->execute();
            $r = $sth->fetchAll(\PDO::FETCH_ASSOC);

            if ($r && $orderId > 3000000000) {
                $variantId = $r[0]["id"];

                $q = "update shop_order_products_fs set variant_id = $variantId where id = $rowId";
                $db->exec($q);
            }
            
        }

        echo "<pre>";
        print_r("done");
        die();
    }

    /**
     * @Route("/userUpdate")
     */
    public function userUpdate():void {
        $db = $this->conf["db"];

        $q = $db->prepare("SELECT * from access_user where login like '%_finalsale%'");
        $q->execute();
        $fsUsers = $q->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($fsUsers as $key => $fsUser) {
            $fsLogin = $fsUser["login"];
            $login = str_replace("_finalsale", "", $fsUser["login"]);
//
//
//            $q = $db->prepare("SELECT * from access_user_fs where login = '$login' ");
//
//
//            $q->execute();
//            $user = $q->fetch();
//
//
//            $newPass = $user["password"];
//
//            $query = "update access_user set password = '$newPass' where login = '$fsLogin' ";

            //$db->exec($query);



        }

        echo "<pre>";
        //print_r(count($fsUsers));
        die();
    }

    /**
     * @Route("/userRewrite")
     */
    // mapuje wszystkich uzytkownikow z fs do orders
    public function userRewrite()
    {
        $db = $this->conf["db"];

        $q = $db->prepare("SELECT * from access_user_fs");
        $q->execute();
        $fsUsers = $q->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($fsUsers as $fsUser) {

            $user = User::get()
                ->_login($fsUser["login"] . "_finalsale")
                ->findFirst()
            ;

            $order = Order::get()
                ->_id(3000020105, Criteria::C_GREATER_EQUAL)
                ->_customerId($fsUser["id"])
                ->find()
                ->toPureArray()
            ;

            if ($order) {
                foreach ($order as $o) {
                    $x = Order::get()
                        ->findByKey($o["id"])
                    ;

                    $x->setValue("customer_id", $user->_id());
                    $x->save();
                }
            }
        }
    }

    /**
     * @Route("/userMigration")
     */
    // kopiujemy z fs 2 tabele i wykonujemy metode
    public function userMigration() {
        $db = $this->conf["db"];

        $q = $db->prepare("SELECT * from access_user_fs");
        $q->execute();
        $fsUsers = $q->fetchAll(\PDO::FETCH_ASSOC);

        $q = $db->prepare("SELECT * from customer_addresses_fs");
        $q->execute();
        $fsUsersAdressess = $q->fetchAll(\PDO::FETCH_ASSOC);


        $counter = 0;

        foreach ($fsUsers as $fsUser) {
            $update = $fsUser;
            $update["id"] = null;
            $update["login"] = $update["login"] . "_finalsale";

            $user = User::create($update);
            $user->save();

            $order = Order::get()
                ->_id(3000020105, Criteria::C_GREATER_EQUAL)
                ->_customerId($fsUser["id"])
                ->find()
                ->toPureArray()
            ;

            foreach ($order as $item) {
                $x = Order::get()
                    ->findByKey($item["id"])
                ;

                $x->setValue(Order::F_CUSTOMER_ID, $user->_id());
                $x->save();
            } // 138069


            foreach ($fsUsersAdressess as $adress) {
                if ($adress["user_id"] == $fsUser["id"]) {
                    $u = $adress;
                    $u["id"] = null;
                    $u["user_id"] = $user->_id();

                    CustomerAddress::create($u)->save();
                }
            }

            $counter++;
        }




        echo "<pre>";
        print_r("done " . $counter);
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
