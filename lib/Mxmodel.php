<?php
    declare(strict_types=1);

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

        protected static $_relationReferencesCount = [];

        public static function getGraphQLType($recursive = true, $prefix = ''): ObjectType
        {
            $class = static::class;
            $fields = [];
            $classShort = basename(str_replace('\\', '/', $class));
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
            if ($recursive) {
                foreach ($class::getRelations() as $relation) {
                    list($relationType, $otherClass, $myField, $otherField, $fieldName) = $relation;
                    /**
                     * @var string         $relationType
                     * @var Mxmodel        $otherClass
                     * @var string         $myField
                     * @var string         $otherField
                     * @var Mxmodel|string $otherClassFull
                     */
                    $otherClassFull = '\\app\\models\\'.$otherClass;
                    if (isset(self::$_relationReferencesCount[$otherClassFull])) {
                        self::$_relationReferencesCount[$otherClassFull]++;
                    } else {
                        self::$_relationReferencesCount[$otherClassFull] = 1;
                    }

                    $otherType = $otherClassFull::getGraphQLType(false,
                        '_rel'.self::$_relationReferencesCount[$otherClassFull].'_');
                    switch ($relationType) {
                        case self::REL_ONE:
                            $fields[$fieldName] = [
                                'type' => $otherType,
                                'resolve' => function (array $root) use ($otherClassFull, $myField, $otherField) {
                                    $query = $otherClassFull::find()->where(['=', $otherField, $root[$myField]]);

                                    return $query->one()->attributes;
                                },
                            ];
                            break;
                        case self::REL_MANY:
                            $fields[$fieldName] = [
                                'type' => Type::listOf($otherType),
                                'resolve' => function (array $root) use ($otherClassFull, $myField, $otherField) {
                                    $query = $otherClassFull::find()->where(['=', $otherField, $root[$myField]]);
                                    $result = [];
                                    foreach ($query->all() as $record) {
                                        $result[] = $record->attributes;
                                    }

                                    return $result;
                                },
                            ];
                            break;
                    }
                }
            }

            $objectType = new ObjectType([
                'name' => $prefix.$classShort,
                'fields' => function () use ($fields): array {
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

            // Запрос одной записи
            $queries[$classShort] = [
                'type' => $selfType,
                'description' => 'Get item with type '.$classShort,
                'args' => $mainQueryArgs,
                'resolve' => function ($root, array $args) use ($class): array {
                    if (empty($args)) {
                        throw new \Exception('Args must have at least one field');
                    }

                    $query = $class::find();
                    foreach ($args as $key => $value) {
                        $query = $query->andWhere(['=', $key, $value]);
                    }
                    // hint: Реляции подгружаются на уровне GraphQL-PHP и их значения подставляются сами по себе
                    $result = $query->one()->attributes;

                    return $result;
                },
            ];

            // Запрос всех записей
            $queries['all'.$classShort] = [
                'type' => Type::listOf($selfType),
                'description' => 'Get all items with type '.$classShort,
                'args' => $mainQueryArgs,
                'resolve' => function ($root, array $args) use ($class) {
                    $query = $class::find();
                    foreach ($args as $key => $value) {
                        $query = $query->andWhere(['=', $key, $value]);
                    }

                    $result = [];
                    foreach ($query->all() as $value) {
                        /** @var Mxmodel $value */
                        // hint: Реляции подгружаются на уровне GraphQL-PHP и их значения подставляются сами по себе
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
                $fullClassName = '\\app\\models\\'.$class;

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

        public const REL_ONE = 'one';
        public const REL_MANY = 'many';

        /**
         * @return array
         */
        public static function getRelations(): array
        {
            return [];
        }
    }