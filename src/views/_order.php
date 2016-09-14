<?php

/**
 * @var \yii\widgets\ActiveForm $form
 * @var \yii\db\ActiveRecord $model
 * @var \yii\web\View $this
 */

use kartik\switchinput\SwitchInput;

$contexts = \DevGroup\Multilingual\models\Context::find()->all();
$tabs = [];
foreach ($contexts as $context) {
    $tabs[] = [
        'label' => $context->name,
        'content' => $this->render(
            '_order-statuses-tab',
            [
                'context' => $context,
                'form' => $form,
                'model' => $model,
            ]
        ),
    ];
}

?>
<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="box">
            <div class="box-header with-border"><h3 class="box-title"><?= Yii::t('dotplant.store', 'Cart') ?></h3></div>
            <div class="box-body">
                <?= $form->field($model, 'allowToAddSameGoods')->widget(SwitchInput::class) ?>
                <?= $form->field($model, 'countUniqueItemsOnly')->widget(SwitchInput::class) ?>
                <?= $form->field($model, 'singlePriceForWarehouses')->widget(SwitchInput::class) ?>
                <?= $form->field($model, 'registerGuestInCart')->widget(SwitchInput::class) ?>
            </div>
        </div>
    </div>
    <div class="col-xs-12 col-md-6">
        <div class="box">
            <div class="box-header with-border"><h3 class="box-title"><?= Yii::t('dotplant.store', 'Order statuses') ?></h3></div>
            <div class="box-body">
                <?=
                \yii\bootstrap\Tabs::widget(
                    [
                        'items' => $tabs,
                    ]
                )
                ?>
            </div>
        </div>
    </div>
</div>
