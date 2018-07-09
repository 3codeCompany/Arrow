<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 02.07.2018
 * Time: 10:55
 */

namespace Arrow\Common\Models\Dictionaries;


use Arrow\Common\Models\Interfaces\InterfaceIdentifiableClass;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\PersistentObject;

class DictionaryORMConnector
{
    const CONN_SINGLE = 1;
    const CONN_MULTI = 2;
    const CONN_MULTI_WITH_VALUE = 4;
    const CONN_MULTI_WITH_VALUE_INIT_SIMPLE = 8;


    public static function registerField(PersistentObject $object, $fieldName, $connType = self::CONN_MULTI)
    {


        $name = $object instanceof InterfaceIdentifiableClass ? $object->getClassID() : $object::getClass();

        $object->addVirtualField($fieldName, function ($field, PersistentObject $obj) use ($connType, $fieldName, $name) {


            $criteria = DictionaryModelValue::get()
                ->_model($name)
                ->_modelId($obj->getPKey())
                ->_field($fieldName);


            if ($connType == self::CONN_SINGLE) {
                $result = $criteria->find();
                $count = $result->count();
                if ($count == 1) {
                    return $result->fetch()[DictionaryModelValue::F_DICTIONARY_ID];
                } elseif ($count == 0) {
                    return null;
                } else {
                    throw new \Exception("To many values for single dictionary");
                }
            } elseif ($connType == self::CONN_MULTI) {

                return $criteria->findAsFieldArray(DictionaryModelValue::F_DICTIONARY_ID);
            } elseif ($connType & self::CONN_MULTI_WITH_VALUE) {
                return $criteria->find()->map(function (DictionaryModelValue $el) {
                    return [
                        "key" => $el->_id(),
                        "value" => $el->_dictionaryId(),
                        "additional_value" => $el->_value(),
                        "additional_data" => $el->_data()
                    ];
                });
            }

            throw new \Exception("Bad conn type option provided");

        }, function ($field, $value, PersistentObject $obj) use ($connType, $fieldName, $name) {

            $mainObjectKey = $obj->getPKey();

            if (empty($mainObjectKey)) {
                throw new \Exception("You need object key to properly save dictionary values.\nTry to save object before set dictionaries");
            }


            $criteria = DictionaryModelValue::get()
                ->_model($name)
                ->_modelId($mainObjectKey)
                ->_field($fieldName);

            if ($connType == self::CONN_SINGLE) {
                if (is_array($value)) {
                    throw new \Exception("Bad value for CONN_SINGLE option provided");
                } else {

                    $result = $criteria->find();
                    $count = $result->count();
                    if ($count == 1) {
                        $result
                            ->fetch()
                            ->setValue(DictionaryModelValue::F_DICTIONARY_ID, $value)
                            ->save();
                    } elseif ($count == 0) {
                        DictionaryModelValue::create([
                            DictionaryModelValue::F_MODEL => $name,
                            DictionaryModelValue::F_FIELD => $fieldName,
                            DictionaryModelValue::F_MODEL_ID => $mainObjectKey,
                            DictionaryModelValue::F_DICTIONARY_ID => $value,
                        ]);
                    } else {
                        throw new \Exception("To many values for single dictionary");
                    }

                }
            } elseif ($connType == self::CONN_MULTI) {
                if (empty($value)) {
                    $value = [];
                }
                self::deleteNotInValue($value, $fieldName, $name, $obj);

                $existing = $obj->getValue($fieldName);
                $toAdd = array_diff($value, $existing);

                foreach ($toAdd as $key) {
                    DictionaryModelValue::create([
                        DictionaryModelValue::F_MODEL => $name,
                        DictionaryModelValue::F_FIELD => $fieldName,
                        DictionaryModelValue::F_MODEL_ID => $mainObjectKey,
                        DictionaryModelValue::F_DICTIONARY_ID => $key,
                    ]);
                }


            } elseif ($connType & self::CONN_MULTI_WITH_VALUE) {
                if (empty($value)) {
                    $value = [];
                }

                if ($connType & self::CONN_MULTI_WITH_VALUE_INIT_SIMPLE) {
                    foreach ($value as $key => $el) {
                        if (!is_array($el)) {
                            $test = DictionaryModelValue::get()
                                ->_model($name)
                                ->_modelId($mainObjectKey)
                                ->_field($fieldName)
                                ->_dictionaryId($el)
                                ->findFirst();
                            $value[$key] = [
                                "key" => $test ? $test->getPKey() : null,
                                "value" => $el,
                                "additional_value" => $test ? $test->_value() : "",
                                "additional_data" => $test ? $test->_data() : "",
                            ];
                        }
                    }
                }

                $values = array_reduce($value, function ($p, $c) {
                    if (isset($c["value"])) {
                        $p[] = $c["value"];
                    }
                    return $p;
                }, []);


                self::deleteNotInValue($values, $fieldName, $name, $obj);

                $existing = $obj->getValue($fieldName);
                foreach ($value as $value) {
                    $exists = false;
                    $valueData = isset($value["additional_data"]) ? $value["additional_data"] : "";
                    foreach ($existing as $inDb) {
                        if (isset($value["key"]) && $inDb["key"] == $value["key"]) {
                            $exists = true;
                            if ($inDb["additional_value"] != $value["additional_value"] || $inDb["additional_data"] != $valueData) {
                                DictionaryModelValue::get()
                                    ->_model($name)
                                    ->_modelId($mainObjectKey)
                                    ->_field($fieldName)
                                    ->_dictionaryId($inDb["value"])
                                    ->findFirst()
                                    ->setValue(DictionaryModelValue::F_VALUE, $value["additional_value"])
                                    ->setValue(DictionaryModelValue::F_DATA, $valueData)
                                    ->save();
                            }
                        }
                    }
                    if (!$exists) {
                        DictionaryModelValue::create([
                            DictionaryModelValue::F_MODEL => $name,
                            DictionaryModelValue::F_FIELD => $fieldName,
                            DictionaryModelValue::F_MODEL_ID => $obj->getPKey(),
                            DictionaryModelValue::F_DICTIONARY_ID => $value["value"],
                            DictionaryModelValue::F_VALUE => $value["additional_value"],
                            DictionaryModelValue::F_DATA => $valueData,
                        ]);
                    }

                }
            }

        });

    }

    private static function deleteNotInValue($value, $fieldName, $name, $obj)
    {
        $toDelete = DictionaryModelValue::get()
            ->_model($name)
            ->_modelId($obj->getPKey())
            ->_field($fieldName)
            ->_dictionaryId($value, Criteria::C_NOT_IN)
            ->find()
            ->delete();

        //$toDeleteIds = $toDelete->reduce(function($el){ return $el->getPKey(); });

        //$toDelete->delete();

        //return $toDeleteIds;
    }


}