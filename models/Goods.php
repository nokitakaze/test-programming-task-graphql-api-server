<?php

    namespace app\models;

    use \app\lib\Mxmodel;
    use \yii\helpers\ArrayHelper;
    use \GraphQL\Type\Definition\Type;

    /**
     * Class Goods
     * @package app\models
     *
     * @property int                 $id
     * @property string              $name
     * @property float               $price
     * @property string              $description
     *
     * @property-read GoodsFeature[] $features
     */
    class Goods extends Mxmodel
    {
        /**
         * {@inheritdoc}
         */
        public static function tableName()
        {
            return 'goods';
        }

        /**
         * {@inheritdoc}
         */
        public function rules()
        {
            return [
                [['id', 'name', 'price', 'description'], 'required'],
                [['id'], 'integer'],
                [['price'], 'float'],
                [['name', 'description'], 'string'],
            ];
        }

        /**
         * {@inheritdoc}
         */
        public function attributeLabels()
        {
            return [
                'id' => 'Артикул товара',
                'name' => 'Название товара',
                'price' => 'Цена',
                'description' => 'Описание товара',
            ];
        }

        /**
         * @return string[]
         */
        public static function attributeTypes(): array
        {
            return [
                'id' => Type::INT,
                'name' => Type::STRING,
                'price' => Type::FLOAT,
                'description' => Type::STRING,
            ];
        }

        /**
         * @return GoodsFeature[]
         */
        public function getFeatures()
        {
            return $this->hasMany(GoodsFeature::class, ['goods_id' => 'id'])->all();
        }
    }