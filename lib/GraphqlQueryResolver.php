<?php

    namespace app\lib;

    use GraphQL\GraphQL;

    class GraphqlQueryResolver
    {
        /**
         * GraphQL-
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