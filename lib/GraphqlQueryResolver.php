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
         * @return array
         */
        public static function runQuery(string $query): array
        {
            $schema = Mxmodel::getGraphQLSchema();

            // Выполнение запроса
            return GraphQL::executeQuery($schema, $query)->toArray();
        }
    }