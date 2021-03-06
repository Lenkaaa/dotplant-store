<?php


namespace DotPlant\Store\components;


use DevGroup\Frontend\Universal\ActionData;
use DotPlant\Monster\DataEntity\DataEntityProvider;
use Yii;
use yii\web\BadRequestHttpException;

class OrderByHashProvider extends DataEntityProvider
{

    /**
     * @var string the region key
     */
    public $regionKey = 'orderRegion';

    /**
     * @var string the material key
     */
    public $materialKey = 'orderMaterial';

    public function pack()
    {
        return [
            'class' => static::class,
            'entities' => $this->entities,
        ];
    }

    /**
     * @param ActionData $actionData
     *
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function getEntities(&$actionData)
    {
        $hash = Yii::$app->request->get('hash');
        $order = Store::getOrder($hash);
        if (is_null($order)) {
            throw new BadRequestHttpException;
        }
        return [
            $this->regionKey => [
                $this->materialKey => [
                    'order' => $order,
                ],
            ],
        ];
    }
}