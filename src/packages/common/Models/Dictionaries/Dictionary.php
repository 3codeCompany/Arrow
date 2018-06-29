<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 22.06.2018
 * Time: 15:09
 */

namespace Arrow\Common\Models\Dictionarie;


use Arrow\Exception;
use Arrow\ORM\Extensions\TreeNode;
use Arrow\ORM\ORM_Arrow_Common_Models_Dictionary;
use Arrow\ORM\Persistent\Criteria;
use Arrow\ORM\Persistent\PersistentObject;

class Dictionary extends ORM_Arrow_Common_Models_Dictionary
{
    use TreeNode;

    public static function getDictionaryFor($name)
    {
        $parent = Dictionary::get()
            ->_systemName($name)
            ->findFirst();

        if (!$parent) {
            throw new \Exception("No dictionary '{$name}' found");
        }

        return $parent->getChildren();
    }



    public function getValuesFor(PersistentObject $model, $name, $onlyDicId = false)
    {

        $parent = Dictionary::get()
            ->_systemName($name)
            ->findFirst();

        $children = Dictionary::get()
            ->_parentId($parent->_id())
            ->findAsFieldArray("id");

        $result = DictionaryModelValue::get()
            ->_model($model::getClass())
            ->_modelId($model->getPKey())
            ->_dictionaryId($children, Criteria::C_IN);

        if ($onlyDicId) {
            return
                $result->findAsFieldArray(DictionaryModelValue::F_DICTIONARY_ID);
        } else {
            return $result
                ->find();
        }

    }

    public function setDicValues($name, $model, $values)
    {

        if (!is_array($values) && $values) {
            throw new Exception("Incorect dictionary `{$name}` value");
        } elseif (!$values) {
            $values = [];
        }


        $parent = Dictionary::get()
            ->_systemName($name)
            ->findFirst();


        $children = Dictionary::get()
            ->_parentId($parent->_id())
            ->findAsFieldArray("id");


        $result = DictionaryModelValue::get()
            ->_model($model::getClass())
            ->_modelId($model->getPKey())
            ->_dictionaryId($children, Criteria::C_IN)
            ->find();


        //dodawanie do listy i aktualizowanie
        foreach ($values as $value) {
            $exists = false;

            if (!is_array($value)) {

                foreach ($result as $el) {
                    if ($value == $el->_dictionaryId()) {
                        $exists == true;
                    }
                }

                if (!$exists) {
                    DictionaryModelValue::create([
                        DictionaryModelValue::F_MODEL => $model::getClass(),
                        DictionaryModelValue::F_MODEL_ID => $model->getPKey(),
                        DictionaryModelValue::F_DICTIONARY_ID => $value,
                    ]);
                }
            } else {

                foreach ($result as $el) {
                    if ($value[DictionaryModelValue::F_DICTIONARY_ID] == $el->_dictionaryId()) {
                        $exists == true;
                    }
                }

                if (!$exists) {
                    DictionaryModelValue::create(array_merge([
                        DictionaryModelValue::F_MODEL => $model::getClass(),
                        DictionaryModelValue::F_MODEL_ID => $model->getPKey(),
                    ], $value));
                } else {
                    $el->setValues($value);
                    $el->save();
                }
            }
        }


        //usuwanie
        foreach ($result as $el) {
            $exists = false;
            foreach ($values as $value) {
                if (!is_array($value)) {
                    if ($el->_dictionaryId() == $value) {
                        $exists = true;
                    }
                } else {
                    if ($value[DictionaryModelValue::F_DICTIONARY_ID] == $el->_dictionaryId()) {
                        $exists = true;
                    }
                }
            }

            if (!$exists) {
                $el->delete();
            }

        }

        //usuwanie z listy


    }
}