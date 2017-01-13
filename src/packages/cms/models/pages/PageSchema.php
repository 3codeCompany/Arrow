<?php
namespace Arrow\CMS;
use Arrow\ORM\Persistent\Criteria;
class PageSchema extends \Arrow\ORM\ORM_Arrow_CMS_PageSchema
{
    static $avail_places = NULL;
    protected $schema_places = NULL;

    public function __construct($initialValues, $params = array())
    {
        parent::__construct($initialValues,$params);
        $this->getPlaces();
    }

    public function synchronizePlaces()
    {
        $places = $this->getAvailablePlaces();
        $schema_places = $this->schema_places;
        $place_ids = array();
        foreach ($schema_places as $place) {
            $place_ids[] = $place->getKey();
        }
        foreach ($places as $place) {
            if (!in_array($place->getPKey(), $place_ids))
                $this->schema_places[] = new PagePlaceSchema(
                    array(
                        PagePlaceSchema::F_PLACE_ID => $place[PagePlace::F_ID],
                        PagePlaceSchema::F_ID_SCHEMA => $this[PageSchema::F_ID]
                    )
                );
        }
        foreach ($this->schema_places as $obj) {
            $obj->save();
        }
    }


    protected function getAvailablePlaces($load = true)
    {
        if (self::$avail_places == null && $load)
            self::$avail_places = Criteria::query(PagePlace::getClass())->find();
        return self::$avail_places;
    }

    public function getPlaces($load = true)
    {
        if ($this->schema_places === null && $load) {
            $this->schema_places = Criteria::query(PagePlaceSchema::getClass())
                ->c(PagePlaceSchema::F_ID_SCHEMA, $this[self::F_ID])
                ->find();
        }

        if (count($this->schema_places) != count($this->getAvailablePlaces())) {
            $this->synchronizePlaces();
        }

        $places = $this->schema_places;
        if (!is_array($places)) $places = array();
        return $places;
    }


    public function setValue($key, $value, $tmp = false)
    {
        if ($key == "places") {
            //echo "<pre>" ;
            //print_r($this);
            //exit;
            $schema_places = $this->getPlaces();
            foreach ($value as $name => $placeData) {
                $place_ok = false;
                foreach ($schema_places as $key => $place) {
                    if (is_array($value)) {
                        if ($name == $place->getName()) {
                            $place->setValues($placeData);
                            $place_ok = true;
                        }
                    } else
                        throw new \Arrow\Exception(array('msg' => '[PageSchema] Incorrect placeData in setValue ',));
                }
                if (!$place_ok)
                    throw new \Arrow\Exception(array('msg' => '[PageSchema] Incorrect place name', 'name' => $name));
            }
            return;
        }
        parent::setValue($key, $value, $tmp);
    }

    public function delete()
    {
        $places = $this->getPlaces();
        foreach ($places as $place)
            $place->delete();
        parent::delete();
    }

    public function save()
    {
        parent::save();
        $places = $this->getPlaces();
        foreach ($places as $place) {
            if ($place[PagePlaceSchema::F_ID_SCHEMA] != $this->getKey()) {
                $place[PagePlaceSchema::F_ID_SCHEMA] = $this->getKey();
            }
            $place->save();
        }
    }
}

?>