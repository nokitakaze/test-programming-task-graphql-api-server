<?php

    namespace app\models;

    use \app\lib\Mxmodel;
    use \yii\helpers\ArrayHelper;
    use \GraphQL\Type\Definition\Type;

    /**
     * Class GoodsFeature
     * @package app\models
     *
     * @property int        $id
     * @property string     $name
     * @property int        $goods_id
     * @property string     $value
     *
     * @property-read Goods $goods
     */
    class GoodsFeature extends Mxmodel
    {
        /**
         * {@inheritdoc}
         */
        public static function tableName()
        {
            return 'goods_feature';
        }

        /**
         * {@inheritdoc}
         */
        public function rules()
        {
            return [
                [['id', 'name', 'goods_id', 'value'], 'required'],
                [['id', 'goods_id'], 'integer'],
                [['goods_id', 'value'], 'string'],
            ];
        }

        /**
         * {@inheritdoc}
         */
        public function attributeLabels()
        {
            return [
                'id' => 'Артикул товара',
                'goods_id' => 'ID товара',
                'name' => 'Название характеристики',
                'value' => 'Значение характеристики',
            ];
        }

        /**
         * @return string[]
         */
        public static function attributeTypes(): array
        {
            return [
                'id' => Type::INT,
                'goods_id' => Type::INT,
                'name' => Type::STRING,
                'value' => Type::STRING,
            ];
        }

        /**
         * @return Goods
         */
        public function getGoods()
        {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $this->hasOne(Goods::class, ['id' => 'goods_id'])->one();
        }
    }