<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 27.08.2017
 * Time: 11:06
 */

namespace Arrow\Common\Models\Helpers;

use App\Models\Translations\CountryVersion;
use App\Models\Translations\Helper;
use Arrow\Access\Models\Auth;
use Arrow\Common\Models\Wigets\Table\TableDataSource;
use Arrow\Media\Models\MediaAPI;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;
use Arrow\Shop\Models\Persistent\Product;
use Arrow\Shop\Models\Persistent\ProductVariant;
use Arrow\Shop\Models\Persistent\Promotion;
use Arrow\Shop\Models\Persistent\PromotionProduct;
use Arrow\Translations\Models\Translations;


class SearcherHelper
{
    private $user;
    private $country = "pl";

    public function __construct()
    {
        $this->user = Auth::getDefault()->getUser();
        if ($this->user->isInGroup("Partnerzy sprzedaży")) {
            $this->country = substr($this->user->_login(), -2);
            Translations::setupLang($this->country);
        }
    }

    public function getProductsProposition($key, $typeWord)
    {
        if (strpos($typeWord, "-") !== false)
        {
            $productVariant = ProductVariant::get()
                ->_sku("%".$typeWord."%", Criteria::C_LIKE)
                ->findAsFieldArray(ProductVariant::F_PRODUCT_ID)
            ;

            $result = Product::get()
                ->_id($productVariant, Criteria::C_IN)
                ->_join(ProductVariant::class, [Product::F_ID => "product_id"], "PV")
                ->addGroupBy(Product::F_ID)
                ->find()
            ;
        } else {
            $typeWord = "%" . $typeWord . "%";
            $result = Product::get()
                ->addSearchCondition(["name", "group_key", "color"], $typeWord, Criteria::C_LIKE)
                ->_join(ProductVariant::class, [Product::F_ID => "product_id"], "PV")
                ->find();
        }

        $returnArr = [];
        foreach ($result as $row) {

            if ($row["PV:sku"]) {
                $returnArr[] = [
                    "id" => $row["id"],
                    "name" => $row->_name(),
                    "price" => $row->_price(),
                    "index" => $row["PV:sku"],
                ];
            }
        }

        $translated = Helper::translateProductsName($returnArr);
        foreach ($returnArr as $key => $value)
        {
            $returnArr[$key]["name"] = $translated[$key]["name"];
        }

        return [
            "items" => $returnArr,
        ];
    }

    public function getPureProductsProposition($typeWord) {
        if (strpos($typeWord, "-") !== false)
        {
            $searchKeys = explode("-", $typeWord);

            $result = Product::get()
                ->_groupKey($searchKeys[0])
                ->_color($searchKeys[1])
                ->find()
            ;
        } else {
            $typeWord = "%" . $typeWord . "%";
            $result = Product::get()
                ->addSearchCondition(["name", "group_key", "color"], $typeWord, Criteria::C_LIKE)
                ->find();
        }

        $returnArr = [];
        foreach ($result as $row) {

                $returnArr[] = [
                    "id" => $row["id"],
                    "name" => $row->_name(),
                    "price" => $row->_price(),
                    "index" => $row->_groupKey() . "-" . $row->_color(),
                ];

        }

        $translated = Helper::translateProductsName($returnArr);
        foreach ($returnArr as $key => $value)
        {
            $returnArr[$key]["name"] = $translated[$key]["name"];
        }

        return [
            "items" => $returnArr,
        ];
    }
}
