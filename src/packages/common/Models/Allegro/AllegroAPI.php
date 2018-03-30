<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 17.03.2016
 * Time: 06:49
 */

namespace Arrow\Common\Models\Allegro;


use Arrow\Common\Models\Allegro\Persistent\AllegroAuctionTemplate;
use Arrow\Common\Models\Allegro\Persistent\AllegroAuction;
use Arrow\Common\Models\Allegro\Persistent\AllegroOrderProduct;
use Arrow\Common\Models\Allegro\Persistent\ProductAllegroProperty;
use Arrow\Access\Models\User;
use Arrow\Common\Models\History\History;
use Arrow\Media\Models\MediaAPI;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Shop\Models\Basket\Basket;
use Arrow\Shop\Models\Persistent\CustomerAddress;
use Arrow\Shop\Models\Persistent\Order;
use Arrow\Shop\Models\Persistent\OrderProduct;
use Arrow\Shop\Models\Persistent\Product;
use Arrow\Shop\Models\Persistent\ProductVariant;

class AllegroAPI
{

    private $client = null;

    /*
Klucz Allegro WebAPI 4622f723

Klucz Sandbox WebAPI se5e6f07
Użytkownik SmA-Esotiq*/

    /*
     * Dane dostępowe do Sandboxa WebAPI:
        Klucz se5e6f07
        Login SmA-Esotiq
        Hasło e5e6f075c6e8f776
      */


    /**
     * Request instance
     *
     * @var AllegroAPI
     */
    private static $oInstance = null;

    private $session;
    private $webKey = "4622f723";
    private $sandbox = false;


    private $login = "SmA-Esotiq";
    private $password = "strefaesot1q";

    /**
     * Singleton
     *
     * @return AllegroAPI
     */
    public static function getDefault()
    {
        if (self::$oInstance == null) {
            self::$oInstance = new AllegroAPI();
        }
        return self::$oInstance;
    }

    public function __construct()
    {

    }

    public function setType($type)
    {
        if ($type == "esotiq" || empty($type)) {
            $this->login = "SmA-Esotiq";
            $this->password = "strefaesot1q";
            $this->webKey = "4622f723";
        } elseif ($type == "henderson") {
            $this->login = "Sma-Henderson";
            $this->password = "strefaesot1q";
            $this->webKey = "35330865";
        }

        $this->session = false;

    }

    private function connect()
    {
        if ($this->client === null) {
            try {
                $options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
                if (!$this->sandbox) {
                    $this->client = new \SoapClient('https://webapi.allegro.pl/service.php?wsdl', $options);
                } else {
                    $this->webKey = "se5e6f07";
                    $this->client = new \SoapClient('https://webapi.allegro.pl.webapisandbox.pl/service.php?wsdl', $options);
                }
            } catch (\Exception $e) {
                echo $e;
            }
        }
    }

    public function setInSandboxMode($mode)
    {
        $this->sandbox = $mode;
        $this->client = null;
        $this->session = null;
        return $this;
    }

    public function getSandboxmode()
    {
        return $this->sandbox;
    }

    public function getVersion()
    {

        $this->connect();
        $doquerysysstatus_request = array(
            'sysvar' => 3,
            'countryId' => 1,
            'webapiKey' => $this->webKey
        );

        $r = $this->client->doQuerySysStatus($doquerysysstatus_request);
        return $r->verKey;
    }

    private function login()
    {
        if ($this->session)
            return;

        if (!$this->sandbox) {
            $login = $this->login;
            $password = $this->password;
            $version = $this->getVersion();
        } else {
            $login = "SmA-Esotiq";
            $password = "e5e6f075c6e8f776";
            $version = $this->getVersion();
        }

        $isCrypo = false;

        // wykrywam czy sa dostepne funkcje kryptograficzne
        if (function_exists('hash') && in_array('sha256', hash_algos())) {
            $isCrypo = true;
            $password = hash('sha256', $password, true); // ostatni parametr musi byc na true, chce otrzymac odcisk w postaci bajtowej a nie hexadecymalnej

        } // starszy mhash
        else if (function_exists('mhash') && is_int(MHASH_SHA256)) {
            $isCrypo = true;
            $password = mhash(MHASH_SHA256, $password);
        }

        try {

            if ($isCrypo) // jak nie wymusono logowania plaintekstem
            {
                // Allegro WebAPI wymaga by hasło zakodowane było dodatkowo w base64Binary
                $password = base64_encode($password);
                $response = $this->client->doLoginEnc(["userLogin" => $login, "userHashPassword" => $password, "countryCode" => 1, "webapiKey" => $this->webKey, "localVersion" => $version]);
            } else {
                $response = $this->client->doLogin(["userLogin" => $login, "userPassword" => $password, "countryCode" => 1, "webapiKey" => $this->webKey, "localVersion" => $version]);
            }

            $response = (array)$response;
            $this->session = $response["sessionHandlePart"];
        } catch (\Exception $e) {
            print /*$e->getCode()." ".*/
                $e->getMessage() . "<br />";
            /*            print "Obecna wersja lokalna:" . $version . "<br />";
                        print "Obecna wersja zdalna:" . $this->getVersion() . "<br />";*/
            exit();
        }

    }


    public function getSellFormFields()
    {
        $request = array(
            'countryId' => 1,
            'categoryId' => 256931, //random category skarpetki
            'webapiKey' => $this->webKey
        );

        $this->connect();
        $fields = $this->client->doGetSellFormFieldsForCategory($request);


        $disabledFields = [
            //nazwa , cena
            1, 8,
            //za granice
            63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80,
            163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180,
            263, 264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280,
            //zdjęcia
            16, 17, 18, 19, 20, 21, 22, 23,
            //kategoria ( już wybrana dla przedmiotu )
            2,
            // Koszt przesyłki pokrywa / opcje dot transportu // formy płatności
            //12, 13, 14,
            //Pierwsze konto bankowe,Drugie konto bankowe,Darmowe opcje przesyłki
            //33, 34, 35,
            //Europejski Kod Towarowy
            //337,
            // Waga (z opakowaniem)
            22191,
            //24453 Dostawa z Polski
            24453,
            //kraj , miejscowos, wojewodztwo, kod pocztowy
            //9, 10, 11, 32,
            //cena wywoławcza, cena mninimalna
            6, 7,
            //data rozpoczęcia  / opcje dodatkowe / wysyłka w ciagu  / Automatyczne wznowienie oferty w sklepie
            3, 15, 340, 30,
            //liczba sztuk
            5,
            //Dodatkowe informacje o przedmiocie, Rozszerzenie informacji o przedmiocie, Sztuki / Komplety / Pary
            24, 25, 28,
            //nowy opis oferty
            341,
            //zdjęcia 9 do 16
            342, 343, 344, 345, 346, 347, 348, 349
        ];


        $fields = $fields->sellFormFieldsForCategory->sellFormFieldsList->item;

        foreach ($fields as $key => &$prop) {
            $prop = (array)$prop;
            if (in_array($prop["sellFormId"], $disabledFields))
                unset($fields[$key]);

            if ($prop["sellFormCat"] != 0)
                unset($fields[$key]);
        }

        return $fields;
    }


    public function getFields($categoryId, $withSize = false)
    {

        $this->connect();

        $request = array(
            'countryId' => 1,
            'categoryId' => $categoryId,
            'webapiKey' => $this->webKey
        );


        $result = $this->client->doGetSellFormFieldsForCategory($request);

        $properties = $result->sellFormFieldsForCategory->sellFormFieldsList->item;


        $disabledFields = [
            //za granice
            63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80,
            163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180,
            263, 264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280,
            //zdjęcia
            16, 17, 18, 19, 20, 21, 22, 23,
            //kategoria ( już wybrana dla przedmiotu )
            2,
            // Koszt przesyłki pokrywa / opcje dot transportu // formy płatności
            12, 13, 14,
            //Pierwsze konto bankowe,Drugie konto bankowe,Darmowe opcje przesyłki
            33, 34, 35,
            //Europejski Kod Towarowy
            337,
            // Waga (z opakowaniem)
            22191,
            //24453 Dostawa z Polski
            24453,
            //kraj , miejscowos, wojewodztwo, kod pocztowy
            9, 10, 11, 32,
            //cena wywoławcza, cena mninimalna
            6, 7,
            //data rozpoczęcia / czas trwania / opcje dodatkowe / wysyłka w ciagu / format sprzedaży / Automatyczne wznowienie oferty w sklepie
            3, 4, 15, 340, 29, 30,
            //liczba sztuk
            5,
            //nowy opis oferty
            341,
            //zdjęcia 9 do 16
            342, 343, 344, 345, 346, 347, 348, 349
        ];


        $disabledNames = [
            "dodatkowe informacje o przesyłce i płatności",

        ];
        if (!$withSize) {
            $disabledNames[] = "rozmiar";
        }

        $systemDefaultValues = [
            //  Dostawa z Polski
            24453 => 1, //tak,
            //Marka
            23955 => 37, //Esotiq
            //stan
            19970 => 1, //Nowy
        ];


        foreach ($properties as $key => &$prop) {
            $prop = (array)$prop;
            if (in_array($prop["sellFormId"], $disabledFields))
                unset($properties[$key]);

            //usuwanie form wysyłki
            if ($prop["sellFormDefValue"] == "-1") {
                unset($properties[$key]);
            }

            if (isset($systemDefaultValues[$prop["sellFormId"]]))
                $prop["sellFormDefValue"] = $systemDefaultValues[$prop["sellFormId"]];

            if (in_array(strtolower($prop["sellFormTitle"]), $disabledNames)) {

                unset($properties[$key]);
            }
        }

        return $properties;
    }


    public function getFieldTextValue($value, $field)
    {
        if (!in_array($field["sellFormType"], [4, 5, 6]))
            return $value;

        $prepareValues = function () use ($field) {
            $labels = explode("|", $field["sellFormDesc"]);
            $values = explode("|", $field["sellFormOptsValues"]);
            return array_combine($values, $labels);
        };


        if (in_array($field["sellFormType"], [4, 5])) {
            return $prepareValues()[$value];
        }

        /*ExceptionHandler::getDefault()->clearOutput = false;
        print "<pre>";*/

        if ($field["sellFormType"] == 6) {
            $vals = [];
            $tmp = explode("|", $value);
            $data = $prepareValues();


            foreach ($tmp as $el) {
                if (isset($data[$el]))
                    $vals[] = $data[$el];
            }
            return $vals;
        }
    }


    public function generateFormField($fieldData, $value)
    {
        unset($fieldData["sellFormFieldDesc"]);
        $f = $fieldData;
        /*      [sellFormId] => 340
        [sellFormTitle] => Wysyłka w ciągu
        [sellFormCat] => 0
        [sellFormType] => 4
        [sellFormResType] => 2
        [sellFormDefValue] => 0
        [sellFormOpt] => 8
        [sellFormPos] => 0
        [sellFormLength] => 3
        [sellMinValue] => 0.00
        [sellMaxValue] => 504.00
        [sellFormDesc] => -- Wybierz --|0|24|48|72|96|120|168|240|336|504
        [sellFormOptsValues] => 0|1|24|48|72|96|120|168|240|336|504
        [sellFormFieldDesc] => Określ, ile czasu zajmie Ci wysłanie przedmiotu Kupującemu.
        [sellFormParamId] => 0
        [sellFormParamValues] =>
        [sellFormParentId] => 0
        [sellFormParentValue] =>
        [sellFormUnit] =>
        [sellFormOptions] => 0*/
        /*sellFormType | int
            Określenie typu pola w formularzu sprzedaży (1 - string, 2 - integer, 3 - float, 4 - combobox, 5 - radiobutton, 6 - checkbox, 7 - image (base64Binary), 8 - text (textarea), 9 - datetime (Unix time), 13 - date).*/
        $prepareValues = function () use ($f) {
            $labels = explode("|", $f["sellFormDesc"]);
            $values = explode("|", $f["sellFormOptsValues"]);
            return array_combine($values, $labels);
        };
        $prepareName = function () use ($f) {
            return 'allegro[' . $f["sellFormId"] . ']';
        };

        $str = "";
        switch ($f["sellFormType"]) {
            case 1:
                $str .= '<input type="text" name="' . $prepareName() . '" value="' . $value . '" /> ' . $f["sellFormUnit"];
                break;
            case 2:
                $str .= '<input type="text" name="' . $prepareName() . '" value="' . $value . '" /> ' . $f["sellFormUnit"];
                break;
            case 3:
                $str .= '<input type="text" name="' . $prepareName() . '"  value="' . $value . '" /> ' . $f["sellFormUnit"];
                break;
            case 4:
                $def = $value ? $value : $f["sellFormDefValue"];
                $options = "";
                foreach ($prepareValues() as $val => $label)
                    $options .= '<option value="' . $val . '" ' . ($def == $val ? 'selected="selecter"' : '') . ' >' . $label . '</option>';
                $str .= '<select name="' . $prepareName() . '">' . $options . '</select> ' . $f["sellFormUnit"];
                break;
            case 5:
                $def = $value ? $value : $f["sellFormDefValue"];
                foreach ($prepareValues() as $val => $label) {
                    $str .= '<input type="radio" name="' . $prepareName() . '" ' . ($def == $val ? 'checked="checked"' : '') . ' value="' . $val . '" /> ' . $label . "<br />";
                }
                break;
            case 6:
                //jeśli dane są zserializowane
                if (is_array($value))
                    $checked = $value;
                else
                    $checked = $value ? explode("|", $value) : [$f["sellFormDefValue"]];

                foreach ($prepareValues() as $val => $label) {
                    if ($label != "-")
                        $str .= '<input type="checkbox" name="' . $prepareName() . '[]" ' . (in_array($val, $checked) ? 'checked="checked"' : '') . ' value="' . $val . '" /> ' . $label . "<br />";
                }
                //$str.="<pre>".print_r($fieldData,1)."</pre>";
                break;
            case 8:
                $str .= '<textarea name="' . $prepareName() . '"  style="width: 400px; height: 150px;;">' . $value . '</textarea> ';
                break;
        }

        return $str;

        unset($fieldData["sellFormFieldDesc"]);


        return "<pre>" . print_r($fieldData, 1) . "</pre>";
    }


    public function getCategories()
    {
        $cacheFile = "./data/cache/allegro_cats.php";
        if (!file_exists($cacheFile)) {
            $this->cacheCategories($cacheFile);
        }
        require_once $cacheFile;
        uasort($allegroCats, function ($a, $b) {
            return $a["path"] > $b["path"];
        });

        return $allegroCats;
    }


    private function cacheCategories($file)
    {
        $request = array(
            'countryId' => 1,
            'countryCode' => 1,
            'webapiKey' => $this->webKey
        );

        set_time_limit(36000);
        $this->connect();
        $result = $this->client->doGetCatsData($request);
        $_cats = $result->catsList->item;


        $acceptedCats = [1454, 1429, 63757, 11763];
        $cats = [];

        foreach ($_cats as $key => $cat) {
            $cat = (array)$cat;
            if ($cat["catParent"] == 0 && !in_array($cat["catId"], $acceptedCats)) {
                //print "usuwam ". $cat["catName"]." ".$cat["catId"]."<br />";
            } else {
                $cats[$cat["catId"]] = $cat;
            }
        }

        for ($i = 0; $i <= 10; $i++) {
            foreach ($cats as $key => $cat) {
                if (!isset($cats[$cat["catParent"]]) && $cat["catParent"] != 0)
                    unset($cats[$key]);
            }
        }

        $getById = function ($id) use ($cats) {
            foreach ($cats as $cat) {
                if ($id == $cat["catId"])
                    return $cat;
            }
            return false;
        };
        foreach ($cats as &$cat) {
            $parentId = $cat["catParent"];
            $path = $cat["catName"];
            $parentsArr = [];
            if ($cat["catParent"])
                $parentsArr = [$cat["catParent"]];
            while ($parentId != 0) {
                $parent = $getById($parentId);
                if (isset($parent["path"])) {
                    $path = $parent["path"] . "/" . $path;
                    break;
                }

                $path = $parent["catName"] . "/" . $path;
                $parentId = $parent["catParent"];
                if ($parentId)
                    $parentsArr[] = $parentId;
            }
            $cat["path"] = $path;
            //$cat["parentsArr"]  = array_reverse($parentsArr);
        }

        $txt = "<?php\n \$allegroCats = " . var_export($cats, true) . ";";

        file_put_contents($file, $txt);
    }


    public function createAuction(ProductVariant $variant, $size, $template_id, $quantity)
    {
        $prepareValues = function ($field) {
            $labels = explode("|", $field["sellFormDesc"]);
            $values = explode("|", $field["sellFormOptsValues"]);
            return array_combine($values, $labels);
        };

        $this->connect();
        if (!$this->session)
            $this->login();

        $product = Product::get()->findByKey($variant->_productId());

        //tworzenie pól
        $_fields = $this->getFields($product->_allegroCategoryId(), true);

        foreach ($_fields as &$_tmp)
            unset($_tmp["sellFormFieldDesc"]);
        //print_r($_fields);
        //exit();
        $tmp = ProductAllegroProperty::get()->_productId($product->_id())->find();
        $values = [];
        foreach ($tmp as $row)
            $values[$row->_propertyId()] = $row->_value();

        $translationArray = [
            1 => "String", 2 => "Int", 3 => "Float", 7 => "Image", 9 => "Datetime", "13" => "Date", 4 => "Int"
        ];

        /*
         * Wskazanie na typ pola, w którym należy przekazać wybraną wartość pola (1 - string, 2 - integer, 3 - float, 7 - image (base64Binary), 9 - datetime (Unix time), 13 - date).*/
        foreach ($_fields as $f) {


            if (isset($values[$f["sellFormId"]])) {
                if ($f["sellFormId"] == 1) {
                    $values[$f["sellFormId"]] .= " {$variant->_size()}";
                }

                $type = $translationArray[$f["sellFormResType"]];
                $addon = [
                    "fid" => $f["sellFormId"],
                    "fvalue" . $type => $values[$f["sellFormId"]]
                ];
                if ($type == "Int") {
                    $addon["fValueIntSpecified"] = true;
                }
                $fields[] = $addon;


            } elseif (strtolower($f["sellFormTitle"]) == "rozmiar") {

                $sizes = $prepareValues($f);

                $size = array_search(($size ? $size : $variant["size"]), $sizes);

                if ($size === false)
                    throw new \Exception($product->_name() . " nieprawidłowy rozmiar {$variant['size']}. Dostępne rozmiary: " . implode(",", $sizes));

                //dodanie rozmiaru
                $fields[] = [
                    "fid" => $f["sellFormId"],
                    "fvalueInt" => $size,//3,//
                    "fValueIntSpecified" => true
                ];
                //print "-".$f["sellFormTitle"]." [{$f["sellFormId"]}]".PHP_EOL;
            }
        }


        //liczba sztuk
        $fields[] = [
            "fid" => 5,
            "fvalueInt" => $quantity
        ];
        // katgoria
        $fields[] = [
            "fid" => 2,
            "fvalueInt" => $product->_allegroCategoryId()
        ];
        $template = AllegroAuctionTemplate::get()->findByKey($template_id);
        $_template = file_get_contents("https://www.esotiq.com/admin/allegro/productTemplate?case=4&comp=1&product=" . $product->_id()."&comp=1&type=".$template["type"]);


        if (strpos($_template, "Exception") !== false)
            return;

        $fields[] = [
            "fid" => 24,
            "fvalueString" => $_template
        ];

        MediaAPI::prepareMedia([$product]);
        if ($media = $product->getParameter("media")) {
            foreach ($media["images"] as $key => $file) {
                $fields[] = [
                    "fid" => 16 + $key,
                    "fvalueImage" => base64_encode(file_get_contents(MediaAPI::getMini($file["path"], 650, 650)))
                ];
            }
        }


        $_fields = $this->getSellFormFields();

        $values = [];

        if ($template && $template->_content()) {
            $values = unserialize($template->_content());
        }

        foreach ($_fields as $f) {
            if (isset($values[$f["sellFormId"]]) && $values[$f["sellFormId"]]) {
                $type = $translationArray[$f["sellFormResType"]];
                $addon = [
                    "fid" => $f["sellFormId"],
                    "fvalue" . $type => $values[$f["sellFormId"]]
                ];
                if ($type == "Int") {
                    $addon["fValueIntSpecified"] = true;
                }
                $fields[] = $addon;

                //zapamiętanie ilości dni - aby zwróćić na koncu
                if ($f["sellFormId"] == 4) {
                    $days = $prepareValues($f)[$values[$f["sellFormId"]]];

                }
            }
        }

        /*        print "<pre>";
                ob_start();
                var_dump($fields);
                $c = ob_get_contents();
                ob_end_clean();
                print htmlentities($c);
                exit();*/

        if($template->_type() == "henderson"){
            $afterSalesServiceConditions = [
                'impliedWarranty' => "07359c5e-ff79-48d4-a7db-8004cb5dce08",
                "returnPolicy" => "fdd62e48-7412-4d8a-8b3c-94ea87b18b31",
                "warranty" => "",
            ];
            //https://allegro.pl/afterSalesServiceConditions/returnPolicy/update/fdd62e48-7412-4d8a-8b3c-94ea87b18b31
        }else {
            $afterSalesServiceConditions = [
                'impliedWarranty' => "8dad9206-a400-4a17-9b6d-9130982f30eb",
                "returnPolicy" => "8f21e2b6-7e90-4b93-ae42-0063e5da3eb7",
                "warranty" => "",
            ];
        }

        $request = array(
            'sessionHandle' => $this->session,
            'fields' => $fields,
            'afterSalesServiceConditions' => $afterSalesServiceConditions
        );

        $result = (array)$this->client->doNewAuctionExt($request);
        $result["days"] = $days;

        return $result;
    }



    public function changeQuantity($auction, $newQuantity)
    {
        $this->connect();
        $this->login();
        $r = [
            "sessionHandle" => $this->session,
            "itemId" => $auction,
            "newItemQuantity" => $newQuantity
        ];
        $this->client->doChangeQuantityItem($r);
    }

    public function closeAuctions($auctions = null)
    {
        $this->connect();
        $this->login();

        $tmp = [];
        foreach ($auctions as $id) {
            $tmp[] = [
                "finishItemId" => $id
            ];
        }
        $r = [
            "sessionHandle" => $this->session,
            "finishItemsList" => $tmp
        ];

        $data = (array)$this->client->doFinishItems($r);
        $data = json_decode(json_encode($data), TRUE);
        return $data;

    }

    public function getSiteJournal($startingPoint = null)
    {
        // zwraca maks 100 najnowszych
        $this->connect();
        $this->login();

        $dogetsitejournal_request = array(
            'sessionHandle' => $this->session,
            'startingPoint' => $startingPoint,
            'infoType' => 0
        );

        $data = (array)$this->client->doGetSiteJournal($dogetsitejournal_request);
        $data = json_decode(json_encode($data), TRUE);
        return $data;
    }


    public function getMySellItems()
    {

        $this->connect();
        $this->login();

        $dogetmysellitems_request = array(
            'sessionId' => $this->session,
            'sortOptions' => array(
                'sortType' => 1,
                'sortOrder' => 2),
            'filterOptions' => array(
                'filterFormat' => 0,
                'filterBids' => 0,
                'filterToEnd' => 0,
                'filterFromStart' => 0,
                'filterAutoListing' => 0,
                'filterPrice' => array(
                    'filterPriceFrom' => "",
                    'filterPriceTo' => "")),
            'searchValue' => "",
            'categoryId' => "",
            'itemIds' => "",
            'pageSize' => 1000,
            'pageNumber' => 0
        );

        $data = (array)$this->client->doGetMySellItems($dogetmysellitems_request);
        $data = json_decode(json_encode($data), TRUE);
        return $data;
    }


    public function getAuctionsInfo($auctions = null)
    {
        $this->connect();
        $this->login();


        $r = [
            "sessionId" => $this->session,
        ];

        if ($auctions)
            $r["itemIds"] = $auctions;

        $data = $this->client->doGetMySellItems($r)->sellItemsList;

        $data = json_decode(json_encode($data), TRUE);

        return $data;
    }

    public function getShipmentData()
    {
        $this->connect();
        $this->login();

        $r = [
            "countryId" => 1,
            "webapiKey" => $this->webKey,
        ];

        $result = $this->client->doGetShipmentData($r)->shipmentDataList->item;

        $result = json_decode(json_encode($result), TRUE);

        return $result;
    }


    public function getAllegroOrders()
    {
        $transactionsFound = $this->getTransactionsIdFromJournalDeals();

        $ordersFinished = Order::get()
            ->_allegroId(0, Criteria::C_GREATER_THAN)
            ->startGroup()
            ->_paymentStatus(1)
            ->_or()
            ->_status([6,9], Criteria::C_IN)
            ->endGroup()
            ->findAsFieldArray(Order::F_ALLEGRO_ID);

        $transactionsToProcess =[];
        foreach ($transactionsFound as $transactionId){
            if(!in_array($transactionId, $ordersFinished)){
                $transactionsToProcess[] = $transactionId;
            }
        }

        if(!empty($transactionsToProcess)) {
            $transactionsArrayChunks = array_chunk($transactionsToProcess, 20);
            foreach ($transactionsArrayChunks as $transactionsArray) {
                $this->processTransactions($transactionsArray);
            }
        }
    }

    private function getTransactionsIdFromJournalDeals()
    {
        $maxDisplayedDealPages = 99;
        $journalStart = null;

        $journalDeals = [];
        $journalCancelledDeals = [0];
        for ($i = 0; $i < $maxDisplayedDealPages; $i++) {
            $data = $this->getSiteJournalDeals($journalStart);
            $latestJournalStart = $journalStart;

            if (isset($data['siteJournalDeals']['item'])) {
                $lastOfferCounter = 0;
                foreach ($data['siteJournalDeals']['item'] as $d) {
                    $journalDeals[] = $d['dealTransactionId'];
                    if ($d['dealEventType'] == 3) {
                        $journalCancelledDeals[] = $d['dealTransactionId'];
                    }
                    if ($lastOfferCounter == 99) {
                        $journalStart = $d['dealEventId'];
                    }
                    $lastOfferCounter++;
                }
            }
            if ($latestJournalStart == $journalStart) { //break when no more offers
                $i = $maxDisplayedDealPages;
            }
        }
        $transactionsFound = array_unique($journalDeals);

        $transactionsWithoutCancelled =[];
        foreach ($transactionsFound as $transactionId){
            if(!in_array($transactionId, $journalCancelledDeals)){
                $transactionsWithoutCancelled[] = $transactionId;
            }
        }

        return $transactionsWithoutCancelled;
    }

    public function getSiteJournalDeals($journalStart = null)
    {
        // zwraca maks 100 najnowszych
        $this->connect();
        $this->login();

        $dogetsitejournaldeals_request = array(
            'sessionId' => $this->session,
            'journalStart' => $journalStart,
        );

        $data = (array)$this->client->doGetSiteJournalDeals($dogetsitejournaldeals_request);
        $data = json_decode(json_encode($data), TRUE);
        return $data;
    }

    public function processTransactions($transactionIdsArray)
    {
        $this->connect();
        $this->login();

        $r = [
            "sessionId" => $this->session,
            "transactionsIdsArray" => $transactionIdsArray
        ];

        $data = (array)$this->client->doGetPostBuyFormsDataForSellers($r)->postBuyFormData->item;

        foreach ($data as $row) {
            $transactionId = $row->postBuyFormId;
            $advancedUserInfo = $this->getTransactionsData([$transactionId]);

            $basket = Basket::getInstance();
            $basket->clear();

            $userData = $row->postBuyFormShipmentAddress;
            $sendAddress = [
                "name" => $userData->postBuyFormAdrFullName,
                "street" => $userData->postBuyFormAdrStreet,
                "street_nb" => "",
                "zip" => $userData->postBuyFormAdrPostcode,
                "city" => $userData->postBuyFormAdrCity,
                "phone" => $userData->postBuyFormAdrPhone,
                "company" => $userData->postBuyFormAdrCompany
            ];

            $advancedUserInfo = $advancedUserInfo[0];
            //w momencie kiedy nie występuje item to sie dzieje - produkt usunięty ?
            if(!isset($advancedUserInfo["userFirstName"])){
                return;
            }

            $address = [
                "name" => $advancedUserInfo["userFirstName"] . " " . $advancedUserInfo["userLastName"],
                "street" => $advancedUserInfo["userAddress"],
                "street_nb" => "",
                "zip" => $advancedUserInfo["userPostcode"],
                "city" => $advancedUserInfo["userCity"],
                "phone" => $advancedUserInfo["userPhone"]
            ];

            foreach ($row->postBuyFormItems->item as $allegroProduct) {
                $auction = AllegroAuction::get()
                    ->_retItemId($allegroProduct->postBuyFormItId)
                    ->findFirst();

                $product = Product::get()->findByKey($auction->_productId());
                $variant = ProductVariant::get()->findByKey($auction->_variantId());

                if(isset($allegroProduct->postBuyFormItDeals->item)) {
                    $quantity = 0;
                    foreach ($allegroProduct->postBuyFormItDeals->item as $deal){
                        $quantity += $deal->dealQuantity;
                    }

                    $basket->addProduct($product, $quantity, ["size" => $variant->_size()]);
                    $allegroOrderProduct = AllegroOrderProduct::get()
                        ->_allegroId($transactionId)
                        ->_auctionId($allegroProduct->postBuyFormItId)
                        ->findFirst();
                    if(!$allegroOrderProduct) {
                        $this->addAllegroOrderProduct([
                            "allegro_id" => $transactionId,
                            "auction_id" => $allegroProduct->postBuyFormItId,
                            "price" => $allegroProduct->postBuyFormItDeals->item[0]->dealFinalPrice,
                            "quantity" => $quantity,
                            "sum" => $allegroProduct->postBuyFormItAmount
                        ]);
                    }
                }
            }

            $user = User::get()
                ->_login($row->postBuyFormBuyerId . "_allegro")
                ->findFirst();
            if (!$user) {
                $user = $this->addUser(["userId" => $advancedUserInfo["userEmail"], "email" => $advancedUserInfo["userEmail"]]);
            }

            $order = Order::get()
                ->_allegroId($transactionId)
                ->findFirst();

            if (!$order) {
                $description = $row->postBuyFormMsgToSeller;

                //co online
                //ai raty
                //wire_transfer przelew
                //collect_on_delivery  pobranie
                $paymentType = $row->postBuyFormPayType;
                $arr = [
                    "co" => "ONLINE",
                    "wire_transfer" => "TRANSFER",
                    "collect_on_delivery" => "POBR"
                ];

                $shipmentID = $row->postBuyFormShipmentId;
                $shipmentArr = [
                    9 => 1, // Przesyłka kurierska
                    10022 => 2,  // Paczkomat
                    10006 => 4 // Paczka RUCHu
                ];

                $inpostMachine = str_replace(["Paczkomat ", "PACZKA w RUCHu: "], "", $row->postBuyFormGdAddress->postBuyFormAdrFullName);

                if ($row->postBuyFormInvoiceOption == 1) {
                    $invoiceData = $row->postBuyFormInvoiceData;
                    $invoice = [
                        "company" => $invoiceData->postBuyFormAdrCompany,
                        "nip" => $invoiceData->postBuyFormAdrNip,
                        "street" => $invoiceData->postBuyFormAdrStreet,
                        "city" => $invoiceData->postBuyFormAdrCity,
                        "zip" => $invoiceData->postBuyFormAdrPostcode,
                    ];
                }


                if ($this->login == "Sma-Henderson")
                    $auctionType = "www.henderson.pl";
                else
                    $auctionType = "www.esotiq.com";


                $order = $this->addOrder(
                    $transactionId,
                    $address,
                    $sendAddress,
                    $user,
                    isset($arr[$paymentType]) ? $arr[$paymentType] : "ONLINE",
                    $description,
                    $row->postBuyFormAmount,
                    $row->postBuyFormPostageAmount,
                    isset($shipmentArr[$shipmentID]) ? $shipmentArr[$shipmentID] : 1,
                    isset($inpostMachine) ? $inpostMachine : "",
                    $auctionType,
                    isset($invoice) ? $invoice : ""
                );
            }

            //postBuyFormPayStatus
            //Rozpoczęta , Anulowana , Zakończona

            if ($row->postBuyFormPayStatus == "Zakończona")
                $order[Order::F_PAYMENT_STATUS] = 1;

            $order->save();
        }
    }

    public function getTransactionsData($transactions)
    {
        $this->connect();
        $this->login();

        $r = [
            "sessionId" => $this->session,
            "transactionsIdsArray" => $transactions,
        ];

        $data = (array)$this->client->doGetPostBuyFormsDataForSellers($r)->postBuyFormData->item;
        $data = json_decode(json_encode($data), TRUE);

        foreach ($data as $key => $transaction) {

            $r = [
                "sessionHandle" => $this->session,
                "itemsArray" => [$transaction["postBuyFormItems"]["item"][0]["postBuyFormItId"]],
                "buyerFilterArray" => [$transaction["postBuyFormBuyerId"]]
            ];


            $data2 = $this->client->doGetPostBuyData($r)->itemsPostBuyData->item[0]->usersPostBuyData;
            //nie wiem czemu nie ma item czasami  - produkt usunięty ?
            if(isset($data2->item)) {
                $data2 = json_decode(json_encode($data2->item[0]->userData), TRUE);
                $data[$key] = array_merge($data[$key], $data2);
            }
        }

        return $data;
    }



    public function addUser($data)
    {
        $user = User::createIfNotExists([
            User::F_LOGIN => $data["userId"] . "_allegro",
            User::F_EMAIL => $data["email"],
            User::F_PASSWORD => "$%^@unregistred()",
            User::F_ACTIVE => 0,
            User::F_REGISTRED => 0,
        ]);

        return $user;

    }

    public function addAllegroOrderProduct($product){
        $orderProduct = [];
        $orderProduct[AllegroOrderProduct::F_ALLEGRO_ID] = $product["allegro_id"];
        $orderProduct[AllegroOrderProduct::F_AUCTION_ID] = $product["auction_id"];
        $orderProduct[AllegroOrderProduct::F_PRICE] = $product["price"];
        $orderProduct[AllegroOrderProduct::F_QUANTITY] = $product["quantity"];
        $orderProduct[AllegroOrderProduct::F_SUM] = $product["sum"];

        $allegroOrderProduct = AllegroOrderProduct::create($orderProduct);
        $allegroOrderProduct->save();

        return $allegroOrderProduct;
    }


    public function addOrder($transactionId, $address, $sendAddress, $customer, $paymentType, $description, $fullPrice, $addPrice, $deliverMethod, $inpostMachine, $auctionType, $invoice = null)
    {
        $d = $address;

        $basket = Basket::getInstance();

        $order = [];
        $order[Order::F_LANG] = 'pl';
        $order[Order::F_ALLEGRO_ID] = $transactionId;
        $order[Order::F_CREATED] = date("Y-m-d H:i:s");
        $order[Order::F_CUSTOMER_ID] = $customer->getPKey();

        $order[Order::F_SOURCE] = $auctionType;

        $address = CustomerAddress::createIfNotExists(array_merge(["user_id" => $customer->_id()], $sendAddress));
        $order[Order::F_ADDRESS_ID] = $address->_id();

        $order[Order::F_SHIPPING_NAME] = $sendAddress["name"];
        $order[Order::F_SHIPPING_STREET] = $sendAddress["street"];
        $order[Order::F_SHIPPING_CITY] = $sendAddress["city"];
        $order[Order::F_SHIPPING_ZIP] = $sendAddress["zip"];
        $order[Order::F_SHIPPING_PHONE] = $sendAddress["phone"];
        $order[Order::F_SHIPPING_COMPANY] = $sendAddress['company'];

        $order[Order::F_INVOICE_NAME] = $sendAddress["name"];

        if ($invoice) {
            $order[Order::F_INVOICE_COMPANY] = $invoice['company'];
            $order[Order::F_INVOICE_VATID] = $invoice['nip'];
            $order[Order::F_INVOICE_STREET] = $invoice['street'];
            $order[Order::F_INVOICE_CITY] = $invoice['city'];
            $order[Order::F_INVOICE_ZIP] = $invoice['zip'];
        }else{
            $order[Order::F_INVOICE_STREET] = $sendAddress["street"];
            $order[Order::F_INVOICE_CITY] = $sendAddress["city"];
            $order[Order::F_INVOICE_ZIP] = $sendAddress["zip"];
        }

        $order[Order::F_PAYMENT_TYPE] = $paymentType;

        $order[Order::F_PRICE_ADD] = $addPrice;
        $order[Order::F_STATUS] = 1;

        $order[Order::F_DELIVER_METHOD] = $deliverMethod;
        $order[Order::F_INPOST_MACHINE] = $inpostMachine;

        $order[Order::F_PRICE] = $fullPrice;
        $order[Order::F_FOR_DESCRIPTION] = $description;
        $order[Order::F_FOR_CURIER_DESCRIPTION] = $description;
        $order[Order::F_NEWSLETTER_AGREE] = 0;

        $order[Order::F_PRICE_DISCOUNT] = 0;
        $order[Order::F_PAYMENT_STATUS] = 0;


        if ($order[Order::F_PAYMENT_TYPE] == "POBR") {
            $order[Order::F_PAYMENT_STATUS] = 1;
        }


        $order["currency"] = "PLN";


        $orderObj = Order::create($order);
        $orderObj->save();

        $basketElements = array();

        foreach ($basket->getProducts() as $el) {

            /** @var \Arrow\Shop\Models\Persistent\Product $product */
            $product = $el->getProduct();
            /** @var \Arrow\Shop\Models\Persistent\ProductVariant $variant */
            $variant = $el->getVariant();

            $allegroOrderProduct = AllegroOrderProduct::get()
                ->_join(AllegroAuction::getClass(),["auction_id" => AllegroAuction::F_RET_ITEM_ID], "AA")
                ->c(AllegroOrderProduct::F_ALLEGRO_ID, $transactionId)
                ->c("AA:".AllegroAuction::F_VARIANT_ID, $variant->_id())
                ->findFirst();

            $insert = array();
            $insert[OrderProduct::F_ORDER_ID] = $orderObj->getPKey();
            $insert[OrderProduct::F_SUM] = ($allegroOrderProduct->_sum() ? $allegroOrderProduct->_sum() : $el->getSum());
            $insert[OrderProduct::F_PRICE] = ($allegroOrderProduct->_price() ? $allegroOrderProduct->_price() : $el->getPrice());
            $insert[OrderProduct::F_QUANTITY] = ($allegroOrderProduct->_quantity() ? $allegroOrderProduct->_quantity() : $el->getQuantity());
            $insert[OrderProduct::F_PRODUCT_ID] = $product->_id();
            $insert[OrderProduct::F_VARIANT_ID] = $variant->_id();
            $basketElement = OrderProduct::create($insert);
            $basketElement->save();
            $basketElements[] = $basketElement;
        }


        History::createEntry($orderObj, "stworzono");

        return $orderObj;
    }

    public function printAllSiteJournalDeals(){
        $maxDisplayedDealPages = 99;
        $journalStart = null;

        $journalDeals = [];
        print "<pre>";
        for ($i = 0; $i < $maxDisplayedDealPages; $i++) {
            $data = $this->getSiteJournalDeals($journalStart);
            $latestJournalStart = $journalStart;

            if (isset($data['siteJournalDeals']['item'])) {
                $lastOfferCounter = 0;
                print_r($data['siteJournalDeals']['item']);
                foreach ($data['siteJournalDeals']['item'] as $d) {
                    $journalDeals[] = $d['dealTransactionId'];
                    if ($lastOfferCounter == 99) {
                        $journalStart = $d['dealEventId'];
                    }
                    $lastOfferCounter++;
                }
            }
            if($latestJournalStart == $journalStart){ //break when no more offers
                $i = $maxDisplayedDealPages;
            }
        }
        $transactionsFound  = array_unique($journalDeals);

        print_r($transactionsFound);
    }

    public function getPostBuyFormData($transactionIdsArray)
    {
        $this->connect();
        $this->login();

        $r = [
            "sessionId" => $this->session,
            "transactionsIdsArray" => $transactionIdsArray
        ];

        $data = (array)$this->client->doGetPostBuyFormsDataForSellers($r)->postBuyFormData->item;
        return $data;
    }


}
