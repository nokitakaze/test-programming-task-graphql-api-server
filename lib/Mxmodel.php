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
         * @codeCoverageIgnore
         */
        public static function attributeTypes(): array
        {
            return [];
        }

        public static function getAttributeType(string $attribute): Type
        {
            $types = static::attributeTypes();
            if (!isset($types[$attribute])) {
                return Type::string();
            }
            if ($types[$attribute] instanceof Type) {
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
            foreach (array_keys($obj->attributeLabels()) as $name) {
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
                    $record = $query->one();
                    if (is_null($record)) {
                        // @todo А что нужно возвращать, если null?
                        return null;
                    }
                    $result = $record->attributes;

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

        public static function getAvailableMutations(): array
        {
            $queries = [];
            $class = static::class;
            $classShort = basename(str_replace('\\', '/', $class));
            /** @var Mxmodel $obj */
            /** @var Mxmodel $class */
            $obj = new $class();

            // update
            /** @noinspection PhpParamsInspection */
            $mainUpdateArgs = ['id' => Type::nonNull($class::getAttributeType('id'))];
            foreach (array_keys($obj->attributeLabels()) as $name) {
                if ($name === 'id') {
                    continue;
                }
                $mainUpdateArgs[$name] = [
                    // hint: Другие фичи для аргументов сейчас не используются
                    'type' => $class::getAttributeType($name),
                ];
            }

            $queries['update'.$classShort] = [
                'type' => static::getGraphQLType(false, '_update_'),
                'description' => 'Get item with type '.$classShort,
                'args' => $mainUpdateArgs,
                'resolve' => function ($root, array $args) use ($class, $classShort): array {
                    if (empty($args)) {
                        // hint: Сюда код не придёт никогда, так как Code Contract GraphQL-PHP не позволит этому случиться
                        // @codeCoverageIgnoreStart
                        throw new \Exception('Args must have at least one field');
                        // @codeCoverageIgnoreEnd
                    }
                    if (empty($args['id'])) {// hint: В реальности primary key может называться как угодно
                        // hint: Сюда код не придёт никогда, так как Code Contract GraphQL-PHP не позволит этому случиться —
                        // поле выставлено как not null
                        // @codeCoverageIgnoreStart
                        throw new \Exception('Arg `id` must have value');
                        // @codeCoverageIgnoreEnd
                    }

                    $query = $class::find()->where(['=', 'id', $args['id']]);
                    // hint: Реляции подгружаются на уровне GraphQL-PHP и их значения подставляются сами по себе
                    $record = $query->one();
                    foreach ($args as $key => $value) {
                        // hint: Сюда надо вставлять код, который санирует поля, в которые нельзя писать
                        if ($key !== 'id') {
                            $record->{$key} = $value;
                        }
                    }
                    if (!$record->validate() or !$record->save()) {
                        throw new \Exception(sprintf('Can not save record (%s) id=%s: %s',
                            $classShort, $record->id, $record->getErrorsAsString()));
                    }

                    $record = $query->one();

                    return $record->attributes;
                },
            ];

            // insert
            $mainInsertArgs = [];
            foreach (array_keys($obj->attributeLabels()) as $name) {
                if ($name === 'id') {
                    continue;
                }
                $mainInsertArgs[$name] = [
                    // hint: Другие фичи для аргументов сейчас не используются
                    'type' => $class::getAttributeType($name),
                ];
            }
            $queries['insert'.$classShort] = [
                'type' => static::getGraphQLType(false, '_insert_'),
                'description' => 'Get item with type '.$classShort,
                'args' => $mainInsertArgs,
                'resolve' => function ($root, array $args) use ($class, $classShort): array {
                    // hint: Тут специально нет проверки на empty($args), объект просто создастся со своими дефолтными значениями
                    if (!empty($args['id'])) {// hint: В реальности primary key может называться как угодно
                        // hint: Сюда код не придёт никогда, так как Code Contract GraphQL-PHP не позволит этому случиться —
                        // этого поля просто нет среди доступных
                        // @codeCoverageIgnoreStart
                        throw new \Exception('Arg `id` must not be set');
                        // @codeCoverageIgnoreEnd
                    }

                    /** @var Mxmodel $newRecord */
                    $newRecord = new $class();
                    foreach ($args as $key => $value) {
                        // hint: Сюда надо вставлять код, который санирует поля, в которые нельзя писать
                        $newRecord->{$key} = $value;
                    }
                    if (!$newRecord->validate() or !$newRecord->save()) {
                        throw new \Exception(sprintf('Can not save new record (%s): %s',
                            $classShort, $newRecord->getErrorsAsString()));
                    }

                    $record = $class::find()->where(['=', 'id', $newRecord->id])->one();

                    return $record->attributes;
                },
            ];

            // delete
            /** @noinspection PhpParamsInspection */
            $mainDeleteArgs = ['id' => Type::nonNull($class::getAttributeType('id'))];

            $queries['delete'.$classShort] = [
                'type' => Type::boolean(),
                'description' => 'Delete item with type '.$classShort,
                'args' => $mainDeleteArgs,
                'resolve' => function ($root, array $args) use ($class, $classShort): bool {
                    if (empty($args)) {
                        // hint: Сюда код не придёт никогда, так как Code Contract GraphQL-PHP не позволит этому случиться
                        // @codeCoverageIgnoreStart
                        throw new \Exception('Args must have at least one field');
                        // @codeCoverageIgnoreEnd
                    }
                    if (empty($args['id'])) {// hint: В реальности primary key может называться как угодно
                        // hint: Сюда код не придёт никогда, так как Code Contract GraphQL-PHP не позволит этому случиться —
                        // поле выставлено как not null
                        // @codeCoverageIgnoreStart
                        throw new \Exception('Arg `id` must have value');
                        // @codeCoverageIgnoreEnd
                    }

                    $query = $class::find()->where(['=', 'id', $args['id']]);
                    $record = $query->one();
                    if (!is_null($record)) {
                        return ($record->delete() === 1);
                    } else {
                        return false;
                    }
                },
            ];

            return $queries;
        }

        final public static function getGraphQLSchema(): \GraphQL\Type\Schema
        {
            $schema_array = [];
            $availableQueries = [];
            $availableMutations = [];
            // hint: Проблема, когда класс называется Query или Mutation
            foreach (static::getTypesList() as $class) {
                /** @var Mxmodel $fullClassName */
                $fullClassName = '\\app\\models\\'.$class;
                $graphQLType = $fullClassName::getGraphQLType();
                $schema_array[$class] = $graphQLType;

                // Запросы
                $availableQueries = array_merge($availableQueries, $fullClassName::getAvailableQueries());

                // Мутации
                $availableMutations = array_merge($availableMutations, $fullClassName::getAvailableMutations());
            }

            $schema_array['query'] = new ObjectType([
                'name' => 'Query',
                'fields' => $availableQueries,
            ]);
            $schema_array['mutation'] = new ObjectType([
                'name' => 'Mutation',
                'fields' => $availableMutations,
            ]);

            $schema = new \GraphQL\Type\Schema($schema_array);

            return $schema;
        }

        public const REL_ONE = 'one';
        public const REL_MANY = 'many';

        /**
         * @return array
         * @codeCoverageIgnore
         */
        public static function getRelations(): array
        {
            return [];
        }

        /**
         * @return string
         * @codeCoverageIgnore
         */
        public function getErrorsAsString(): string
        {
            if (empty($this->errors)) {
                return '';
            }

            $a = [];
            foreach ($this->errors as $field => $value) {
                $a[] = sprintf('`%s`: %s', $field, implode(', ', $value));
            }

            return implode('; ', $a);
        }
    }