<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 27.08.2017
 * Time: 11:06
 */

namespace Arrow\Common\Models\Helpers;

use Arrow\Common\Models\Wigets\Table\TableDataSource;
use Arrow\Media\Models\MediaAPI;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\DataSet;
use Arrow\Shop\Models\Persistent\Product;
use Arrow\Shop\Models\Persistent\ProductVariant;
use Arrow\Shop\Models\Persistent\Promotion;
use Arrow\Shop\Models\Persistent\PromotionProduct;

class SearcherHelper
{
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
            $returnArr[] = [
                "id" => $row["id"],
                "name" => $row->_name(),
                "price" => $row->_price(),
                "index" => $row["PV:sku"],
            ];
        }

        return [
            "items" => $returnArr
        ];
    }
}
