<?php

    namespace app\lib;

    use GraphQL\Type\Definition\ObjectType;
    use GraphQL\Type\Definition\ScalarType;
    use \Yii;
    use \yii\db\ActiveRecord;
    use \GraphQL\Type\Definition\Type;

    class Mxmodel extends ActiveRecord
    {
        /**
         * @return string[]
         */
        public static function attributeTypes(): array
        {
            return [];
        }

        public static function getAttributeType(string $attribute): ScalarType
        {
            $types = static::attributeTypes();
            if (!isset($types[$attribute])) {
                return Type::string();
            }
            if ($types[$attribute] instanceof ScalarType) {
                return $types[$attribute];
            }

            // hint: Type::getStandardType is private, so we should do it this way
            switch ($types[$attribute]) {
                case Type::STRING:
                    /** @noinspection PhpDuplicateSwitchCaseBodyInspection */
                    return Type::string();
                case Type::INT:
                    return Type::int();
                case Type::FLOAT:
                    return Type::float();
                case Type::BOOLEAN:
                    return Type::boolean();
                case Type::ID:
                    return Type::id();
                default:
                    return Type::string();
            }
        }

        /**
         * @return string[]|Mxmodel[]
         */
        final public static function getTypesList(): array
        {
            return ['Goods', 'GoodsFeature'];
        }

        public static function getGraphQLType(): ObjectType
        {
            $fields = [];
            $class = static::class;
            /** @var Mxmodel $obj */
            /** @var Mxmodel $class */
            $obj = new $class();
            foreach ($obj->attributeLabels() as $name => $label) {
                $fields[$name] = [
                    'type' => $class::getAttributeType($name),
                    'description' => $label,
                    'resolve' => function (array $root) use ($name) {
                        return $root[$name];
                    },
                ];
            }
            // @todo relation

            $objectType = new ObjectType([
                'name' => $class,
                'fields' => function () use ($fields) {
                    return $fields;
                },
            ]);

            return $objectType;
        }

        /**
         * @return ObjectType[]
         */
        public static function getAvailableQueries(): array
        {
            $selfType = static::getGraphQLType();

            $queries = [];
            $class = static::class;
            $classShort = basename(str_replace('\\', '/', $class));
            /** @var Mxmodel $obj */
            /** @var Mxmodel $class */
            $obj = new $class();

            $mainQueryArgs = [];
            foreach ($obj->attributeLabels() as $name => $label) {
                $mainQueryArgs[$name] = [
                    // hint: Другие фичи для аргументов сейчас не используются
                    'type' => $class::getAttributeType($name),
                ];
            }

            $queries[$classShort] = [
                'type' => $selfType,
                'description' => 'Get item with type '.$classShort,
                'args' => $mainQueryArgs,
                'resolve' => function ($root, array $args) use ($class) {
                    if (empty($args)) {
                        throw new \Exception('Args must have at least one field');
                    }

                    $query = $class::find();
                    foreach ($args as $key => $value) {
                        $query = $query->andWhere(['=', $key, $value]);
                    }
                    $result = $query->one()->attributes;

                    // @todo реляции

                    return $result;
                },
            ];

            $queries['all'.$classShort] = [
                'type' => Type::listOf($selfType),
                'description' => 'Get all items with type '.$classShort,
                'resolve' => function () use ($class) {
                    $result = [];
                    foreach ($class::find()->all() as $value) {
                        // @todo реляции
                        $result[] = $value->attributes;
                    }

                    return $result;
                },
            ];

            return $queries;
        }

        final public static function getGraphQLSchema(): \GraphQL\Type\Schema
        {
            $schema_array = [];
            $availableQueries = [];
            // hint: Проблема, когда класс называется Query
            foreach (static::getTypesList() as $class) {
                /** @var Mxmodel $fullClassName */
                $fullClassName = ('\\app\\models\\'.$class);

                $queryType = $fullClassName::getGraphQLType();
                $availableQueries = array_merge($availableQueries, $fullClassName::getAvailableQueries());

                $schema_array[$class] = $queryType;
            }

            $schema_array['query'] = new ObjectType([
                'name' => 'Query',
                'fields' => $availableQueries,
            ]);

            $schema = new \GraphQL\Type\Schema($schema_array);

            return $schema;
        }

    }