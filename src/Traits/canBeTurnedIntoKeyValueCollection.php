<?php

namespace OlahTamas\KeyValue;

use Illuminate\Support\Collection;

trait canBeTurnedIntoKeyValueCollection
{
    /*
     * These three constants can control the creation of the key-value collection.
     * The LABEL_FIELD sets the value to be displayed.
     * The ORDER_BY constants allow for sorting the results. When using class constants for IDs and labels,
     * the ORDER_BY_FIELD constant is ignored, as _ID and _LABEL pairs are collected from the class
     * the USE_LABEL_AS_TRANSLATION_KEY constant (false if absent) forces collection generation to use the names as translation keys
     *  const LABEL_FIELD = 'name_and_date_of_birth';
     *  const ORDER_BY_FIELD = 'name';
     *  const ORDER_BY_DIRECTION = 'asc';
     *  const USE_LABEL_AS_TRANSLATION_KEY = true
     *
     */

    public static function getLabelField()
    {
        if (defined('static::LABEL_FIELD')) {
            return static::LABEL_FIELD;
        } else {
            return 'name';
        }
    }

    public static function getOrderByField()
    {
        if (defined('static::ORDER_BY_FIELD')) {
            if (defined('static::ORDER_BY_DIRECTION')) {
                return (object) ['field' => static::ORDER_BY_FIELD, 'direction' => static::ORDER_BY_DIRECTION];
            }

            return (object) ['field' => static::ORDER_BY_FIELD, 'direction' => 'asc'];
        } else {
            return;
        }
    }

    public static function getSubclassForLabelField()
    {
        if (defined('static::LABEL_FIELD_SUBCLASS')) {
            return static::LABEL_FIELD_SUBCLASS;
        } else {
            return;
        }
    }

    public static function getKeyValueCollectionFromElements($elements)
    {
        $labelField = self::getLabelField();
        $subclass = self::getSubclassForLabelField();

        return self::getKeyValueCollectionFromObjectCollection($elements, 'id', $labelField, $subclass);
    }

    public static function getKeyValueCollectionOfClassConstants($constantSuffix = '_LABEL')
    {
        $useLabelAsTranslationKey = defined('static::USE_LABEL_AS_TRANSLATION_KEY')
            ? static::USE_LABEL_AS_TRANSLATION_KEY
            : false;
        $class = self::class;
        $reflection = new \ReflectionClass($class);
        $values = [];
        $suffixLength = strlen($constantSuffix);
        foreach ($reflection->getConstants() as $constantName => $constantValue) {
            if (substr($constantName, -1 * $suffixLength, $suffixLength) == $constantSuffix) {
                $idConst = str_replace($constantSuffix, '_ID', $constantName);
                if ($useLabelAsTranslationKey) {
                    $values[$reflection->getConstant($idConst)] = __($constantValue);
                } else {
                    $values[$reflection->getConstant($idConst)] = $constantValue;
                }
            }
        }
        $orderByData = self::getOrderByField();
        if ($orderByData !== null) {
            if ($orderByData->direction == 'asc') {
                asort($values);
            } else {
                arsort($values);
            }
        }

        return collect($values);
    }

    public static function getKeyValueCollection($useAdditionalQueries = true)
    {
        if (method_exists(self::class, 'all')) { //extends Model
            $orderByData = self::getOrderByField();
            if ($orderByData !== null) {
                $query = self::orderBy($orderByData->field, $orderByData->direction);
            } else {
                $query = self::where('id', '>', 0);
            }
            if (($useAdditionalQueries) && (method_exists(self::class, 'addKeyValueCollectionAdditionalQueriesToQuery'))) {
                $query = self::addKeyValueCollectionAdditionalQueriesToQuery($query);
            }
            $elements = $query->get();
            $result = self::getKeyValueCollectionFromElements($elements);
        } else {
            $result = self::getKeyValueCollectionOfClassConstants();
        }
        if (method_exists(self::class, 'postProcessKeyValueCollection')) {
            $result = self::postProcessKeyValueCollection($result);
        }

        return $result;
    }

    public static function getVSelectCompatibleKeyValuesetFromElements($elements, $labelField = null)
    {
        $result = [];
        if ($labelField == null) {
            $labelField = self::getLabelField();
        }
        foreach ($elements as $element) {
            $result[] = (object) ['label' => $element->$labelField, 'value' => $element->id];
        }

        return $result;
    }

    public static function getVSelectCompatibleValuesetFromKeyValueset($elements)
    {
        $result = [];
        foreach ($elements as $id => $element) {
            $result[] = (object) ['label' => $element, 'value' => $id];
        }

        return $result;
    }

    public static function getVSelectCompatibleKeyValueset($id = null)
    {
        if ($id === null) {
            return self::getVSelectCompatibleValuesetFromKeyValueset(self::getKeyValueCollection());
        } else {
            $elements = self::getKeyValueCollection()->filter(function ($item, $key) use ($id) {
                return $key == $id;
            });

            return self::getVSelectCompatibleValuesetFromKeyValueset($elements);
        }
    }

    public static function getKeyValueCollectionFromObjectCollection($objectCollection, $key, $value, $subClass = null)
    {
        $useLabelAsTranslationKey = defined('static::USE_LABEL_AS_TRANSLATION_KEY')
            ? static::USE_LABEL_AS_TRANSLATION_KEY
            : false;
        $resultColl = new Collection();
        foreach ($objectCollection as $obj) {
            if ($subClass === null) {
                if (isset($obj->$key) && (isset($obj->$value))) {
                    if ($useLabelAsTranslationKey) {
                        $resultColl->put($obj->$key, __($obj->$value));
                    } else {
                        $resultColl->put($obj->$key, $obj->$value);
                    }
                }
            } else {
                if (isset($obj->$subClass)) {
                    if (isset($obj->$subClass->$key) && (isset($obj->$subClass->$value))) {
                        if ($useLabelAsTranslationKey) {
                            $resultColl->put($obj->$key, __($obj->$subClass->$value));
                        } else {
                            $resultColl->put($obj->$key, $obj->$subClass->$value);
                        }
                    }
                }
            }
        }

        return $resultColl;
    }

    public static function getLabelForId($id)
    {
        return self::getKeyValueCollection()->get($id, '');
    }
}
