<?php

namespace DotPlant\Store\models\order;

use Yii;

/**
 * This is the model class for table "{{%dotplant_store_order_status}}".
 *
 * @property integer $id
 * @property string $label_class
 * @property integer $is_active
 */
class OrderStatus extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%dotplant_store_order_status}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['is_active'], 'integer'],
            [['label_class'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('dotplant.store', 'ID'),
            'label_class' => Yii::t('dotplant.store', 'Label Class'),
            'is_active' => Yii::t('dotplant.store', 'Is Active'),
        ];
    }
}
