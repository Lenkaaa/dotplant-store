<?php

namespace DotPlant\Store\models\warehouse;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use DotPlant\Store\exceptions\WarehouseException;
use DotPlant\Store\interfaces\WarehouseInterface;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * This is the model class for table "{{%dotplant_store_warehouse}}".
 *
 * @todo: Add indexes for all queries
 *
 * @property integer $id
 * @property integer $type
 * @property integer $priority
 */
class Warehouse extends \yii\db\ActiveRecord implements WarehouseInterface
{
    use MultilingualTrait;
    use TagDependencyTrait;

    const TYPE_WAREHOUSE = 1;
    const TYPE_SELLER = 2;

    const STATUS_IN_STOCK = 1;
    const STATUS_BY_REQUEST = 2;
    const STATUS_OUT_OF_STOCK = 3;

    private static $_identityMap = [];

    private static $_typesMap = [
        self::TYPE_WAREHOUSE => TypeWarehouse::class,
        self::TYPE_SELLER => TypeSeller::class,
    ];

    public static function getTypes()
    {
        return [
            self::TYPE_WAREHOUSE => Yii::t('dotplant.store', 'Warehouse'),
            self::TYPE_SELLER => Yii::t('dotplant.store', 'Seller'),
        ];
    }

    /**
     * =================================================================================================================
     */

    private static function fillMap()
    {
        if (empty(static::$_identityMap)) {
            static::$_identityMap = static::find()
                ->indexBy('id')
                ->orderBy(['priority' => SORT_ASC])
                ->all();
        }
    }

    public static function getMap()
    {
        static::fillMap();
        return static::$_identityMap;
    }

    public static function getFromMap($id)
    {
        static::fillMap();
        return isset(static::$_identityMap[$id]) ? static::$_identityMap[$id] : null;
    }

    /**
     * This method returns an optimal warehouse
     * @todo: Move this method to interface and implement different types
     * Now it is implemented by priority
     * @param integer $goodsId
     * @param double $quantity
     */
    public static function getOptimalWarehouse($goodsId, $quantity)
    {
        foreach (static::getWarehouses($goodsId, false) as $warehouse) {
            if ($warehouse->getCount() >= $quantity) {
                return $warehouse;
            }
        }
        throw new WarehouseException(Yii::t('dotplant.store', 'No one of warehouses has enough goods'));
    }

    /**
     * @inheritdoc
     */
    public static function getWarehouse($goodsId, $warehouseId, $asArray = true)
    {
        $result = false;
        if ($warehouse = static::getFromMap($warehouseId)) {
            $goodsWarehouse = GoodsWarehouse::find()
                ->where(
                    [
                        'goods_id' => $goodsId,
                        'warehouse_id' => $warehouseId,
                    ]
                )
                ->asArray(true)
                ->limit(1)
                ->one();
            if ($asArray) {
                return $goodsWarehouse;
            } elseif ($goodsWarehouse !== null) {
                $model = new static::$_typesMap[$warehouse['type']];
                static::populateRecord($model, $goodsWarehouse);
                $result = $model;
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public static function getWarehouses($goodsId, $asArray = true, $allowedOnly = true)
    {
        $warehouses = static::getMap();
        $warehouseIds = array_keys($warehouses);
        $condition = ['goods_id' => $goodsId, 'warehouse_id' => $warehouseIds];
        if ($allowedOnly) {
            $condition['is_allowed'] = 1;
        }
        $goodsWarehouses = GoodsWarehouse::find()
            ->where($condition)
            ->indexBy('warehouse_id')
            ->orderBy(new Expression('FIELD(warehouse_id, ' . implode(', ', $warehouseIds) . ')'))
            ->asArray(true)
            ->all();
        if ($asArray) {
            return $goodsWarehouses;
        }
        foreach ($goodsWarehouses as $warehouseId => $goodsWarehouse) {
            if (isset(self::$_typesMap[$warehouses[$warehouseId]->type])) {
                $model = new self::$_typesMap[$warehouses[$warehouseId]['type']];
                static::populateRecord($model, $goodsWarehouse);
                $goodsWarehouses[$warehouseId] = $model;
            }
        }
        return $goodsWarehouses;
    }

    /**
     * @inheritdoc
     */
    public static function isAvailable($goodsId)
    {
        return static::getStatusCode($goodsId) !== self::STATUS_OUT_OF_STOCK;
    }

    /**
     * @inheritdoc
     */
    public static function getStatusCode($goodsId)
    {
        $row = GoodsWarehouse::find()
            ->select(['is_unlimited', 'available_count'])
            ->where(['goods_id' => $goodsId, 'is_allowed' => 1])
            ->orderBy(['is_unlimited' => SORT_DESC, 'available_count' => SORT_DESC])
            ->limit(1)
            ->asArray(true)
            ->one();
        if ($row === null) {
            return self::STATUS_OUT_OF_STOCK;
        }
        return $row['is_unlimited'] == 0 || $row['available_count'] > 0
            ? self::STATUS_IN_STOCK
            : self::STATUS_BY_REQUEST;
    }

    /**
     * @inheritdoc
     */
    public static function getMinPrice($goodsId, $isRetailPrice = true)
    {
        $priceField = $isRetailPrice ? 'retail_price' : 'wholesale_price';
        return GoodsWarehouse::find()
            ->select(new Expression('currency_iso_code AS `iso_code`, MIN(`' . $priceField . '`) AS `value`'))
            ->where(['goods_id' => $goodsId, 'is_allowed' => 1])
            ->groupBy('currency_iso_code')
            ->orderBy([$priceField => SORT_ASC])
            ->asArray(true)
            ->all();
    }

    /**
     * =================================================================================================================
     */

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'multilingual' => [
                'class' => MultilingualActiveRecord::class,
                'translationModelClass' => WarehouseTranslation::class,
                'translationPublishedAttribute' => false,
            ],
            'cacheable' => [
                'class' => CacheableActiveRecord::class,
            ],
            'sortable' => [
                'class' => ContinuousNumericalSortableBehavior::class,
                'sortAttribute' => 'priority',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%dotplant_store_warehouse}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['priority', 'type'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('dotplant.store', 'ID'),
            'type' => Yii::t('dotplant.store', 'Type'),
            'priority' => Yii::t('dotplant.store', 'Priority'),
        ];
    }

    public function search($params)
    {
        $query = static::find();
        $dataProvider = new ActiveDataProvider(
            [
                'query' => $query,
                'pagination' => [],
            ]
        );
        return $dataProvider;
    }
}
