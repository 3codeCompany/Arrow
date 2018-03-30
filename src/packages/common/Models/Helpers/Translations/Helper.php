<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 29.11.2017
 * Time: 08:10
 */

namespace Arrow\Common\Models\Helpers\Translations;


use Arrow\Translations\Models\Translations;

class Helper
{

    private static $cache = null;

    private static function generateCache()
    {
        $product = [
            "Stanik",
            "Koszulka",
            "Majtki",
            "Kapcie",
            "Dekoracyjne ramiączka",
            "dwupak",
            "trzypak",
            "Podkoszulek",
            "Koszula Nocna",
            "Pas do pończoch",
            "Spodnie",
            "Piżama",
            "Skarpety",
            "Rajstopy",
            "Pończochy",
            "Skarpetki",
            "Leginsy",
            "Szlafrok",
            "Perfumy",
            "Maska",
            "Serum",
            "Ujędrniający żel pod prysznic",
            "Balsam ujędrniający do ciała",
            "Błyszczyk nawilżający",
            "Błyszczyk ochronny",
            "Nawilżający krem do rąk",
            "Ochronny krem do rąk",
            "Nawilżający krem do stóp",
            "Regenerujący krem do stóp",
            "Nawilżający żel do ciała",
            "Nawilżający balsam do ciała",
            "Relaksujący balsam do ciała",
            "Żel do ciała z witaminami",
            "Relaksujący żel do ciała",
            "Balsam do ciała z witaminami",
            "Szampon do włosów",
            "Odżywka do włosów",
            "Kombinezon plażowy",
            "Kombinezon",
            "Komplet",
            "Narzutka plażowa",
            "Klapki plażowe",
            "Góra od bikini",
            "Dół od bikini",
            "bikini",
            "Kalesony",
            "Bokserki",
            "Nocna",
            "Ujędrniający",
            "pod prysznic",
            "Peeling Antycellulitowy",
            "Transport",
            "Torba",
            "Kosmetyczka",
            "ze zmieniającymi się cekinami",
            "na zamek z napisem",
            "geometryczna",
            "krata",
            "gwiazdki",
            "Magnes",
            "świąteczny",
            "Brelok",
            "Puszysty brelok z głową misia",
            "Gumka do włosów z kwiatem",
            "Opaska do włosów z kwiatem",
            "Serduszko",
            "Ujędrniający",
            "żel",
            "Żel",
        ];

        $products = array_combine($product, $product);
        $products = Translations::translateTextArray($products);

        $properties = [
            "kolekcja Created by",
            "klasyczne",
            "poliamid",
            "bawełna",
            "elastan",
            "Elastan",
            "wiskoza",
            "Akryl",
            "pianka",
            "Dół",
            "poliester",
            "Poliester",
            "Materiał syntetyczny",
            "Metal",
            "Szkło",
            "Skóra",
            "Żywica",
            "polipropylen",
            "włókno bambusowe",
            "nylon",
            "lurex",
            "wtórnie przetworzony poliester",
            "bambus",
            "Bambus",
            "polyamid",
            "polyester",
            "akryl",
            "Skład materiału",
            "Góra",
            "szorty",
            "Materiał wierzchni",
            "Wyścielenie miseczki",
            "Siatka",
            "Część elastyczna",
            "Podszewka w kroczu",
            "Koronka",
            "Siatka w skrzydełkach",
            "Materiał o jednolitym kolorze",
            "Siateczka",
            "Podszewka w kroku",
            "Wąskie wstawki z koronki",
            "Wstawka w kroczu",
            "Wewnętrzny materiał pasa",
            "Podszewka",
            "Pas w talii i ściągacze w nogawkach",
            "Koronka w skrzydełkach",
            "Tylko duże rozmiary",
            "Wąskie wstawki koronki",
            "Materiał z nadrukiem",
            "Elementy elastyczne",
            "rayon",
            "Część dekoracyjna",
            "Pasek",
            "Wierzch",
            "środek",
            "podeszwa",
            "Jesień/Zima",
            "Wiosna/Lato",
            "Wielosezonowy",
            "Biel",
            "Czerń",
            "Ecru",
            "Multi",
            "Odcienie brązu",
            "Odcienie brązu i beżu",
            "Odcienie czerwieni",
            "Odcienie fioletu",
            "Odcienie niebieskiego",
            "Odcienie pomarańczowego",
            "Odcienie różu",
            "Odcienie szarości i srebra",
            "Odcienie szarości",
            "Odcienie zieleni",
            "Odcienie żółtego i złota",
            "Różne kolory",
            "Różowy",
            "Szary",
            "z kapturem",
            "z kieszeniami",
            "Typ zapięcia",
            "brak",
            "guziki",
            "wiązany",
            "półusztywniany",
            "na większy biust",
            "nieodpinane",
            "odpinane",
            "koszula nocna",
            "bokserki",
            "slipy",
            "dół",
            "krótki rękaw",
            "długie spodnie",
            "długi rękaw",
            "długi",
            "Koszulka",
            "Spodnie",
        ];

        $properties = array_combine($properties, $properties);
        $properties = Translations::translateTextArray($properties);

        self::$cache = [
            "products" => $products,
            "properties" => $properties,
        ];

    }

    public static function translateProductsName($products)
    {
        if (self::$cache == null) {
            self::generateCache();
        }

        foreach ($products as $index => $p) {
            $p["name"] = strtr($p["name"], self::$cache["products"]);
            $tmp[$index]["name"] = $p["name"];
        }

        return $tmp;

    }

    public static function translateProductListObject($object, $prefix = false)
    {
        if (self::$cache == null) {
            self::generateCache();
        }

        $setPrefix = $prefix == false ? "name" : $prefix;

        foreach ($object["data"] as $index => $p) {
            $p[$setPrefix] = strtr($p[$setPrefix], self::$cache["products"]);
            $object["data"][$index][$setPrefix] = $p[$setPrefix];
        }

        return $object;
    }

    public static function translatePropertyValue($properties)
    {
        if (self::$cache == null) {
            self::generateCache();
        }

        foreach ($properties as $index => $p) {

            $properties[$index]["PP:value"] = str_ireplace(array_keys(self::$cache["properties"]), array_values(self::$cache["properties"]), $p["PP:value"]);
            //$p->setValue("PP::value", strtr($p["PP::value"], self::$cache["products"]));
        }

        return $properties;
    }

}
