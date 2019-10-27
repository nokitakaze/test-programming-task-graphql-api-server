<?php
    declare(strict_types=1);

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
                [['name', 'price', 'description'], 'required'],
                [['id'], 'integer'],
                [['price'], 'number'],
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

        /**
         * @return array
         */
        public static function getRelations(): array
        {
            return [
                [static::REL_MANY, 'GoodsFeature', 'id', 'goods_id', 'features'],
            ];
        }

        public function beforeValidate()
        {
            if (is_null($this->price)) {
                $this->price = 0;
            }
            if (empty($this->description)) {
                $this->description = null;
            }

            return parent::beforeValidate();
        }
    }