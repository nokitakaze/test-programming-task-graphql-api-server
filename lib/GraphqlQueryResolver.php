<?php
    declare(strict_types=1);

    namespace app\lib;

    use GraphQL\GraphQL;

    class GraphqlQueryResolver
    {
        /**
         * Обработка GraphQL-запроса в Системе
         *
         * @param string $query
         *
         * @return \GraphQL\Executor\ExecutionResult
         */
        public static function runQuery(string $query): \GraphQL\Executor\ExecutionResult
        {
            $schema = Mxmodel::getGraphQLSchema();

            // Выполнение запроса
            return GraphQL::executeQuery($schema, $query);
        }

        /**
         * Обработка GraphQL-запроса в Системе
         *
         * @param string $query
         *
         * @return array
         */
        public static function runQueryAsArray(string $query): array
        {
            return static::runQuery($query)->toArray();
        }
    }