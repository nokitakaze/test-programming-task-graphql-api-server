<?php
    declare(strict_types=1);

    namespace app\tests;

    use app\lib\GraphqlQueryResolver;
    use app\models\Goods;
    use app\models\GoodsFeature;

    require_once __DIR__.'/_bootstrap.php';

    /** @noinspection PhpFullyQualifiedNameUsageInspection */

    class MainTest extends \PHPUnit\Framework\TestCase
    {
        public function __construct($name = null, array $data = [], $dataName = '')
        {
            parent::__construct($name, $data, $dataName);
            self::$letters = array_merge(range('0', '9'), range('a', 'z'));

            // Генерируем названия фич
            if (empty(self::$feature_names)) {
                self::$feature_names = [];
                for ($i = 0; $i < 30; $i++) {
                    do {
                        $s = self::generateValue();
                    } while (in_array($s, self::$feature_names));
                    self::$feature_names[] = $s;
                }
            }
        }

        public function testDBConnection()
        {
            Goods::find()->one();
            $this->assertTrue(true);
        }

        protected function executeQuery(string $query): array
        {
            $rawResult = GraphqlQueryResolver::runQuery($query);
            if (!empty($rawResult->errors)) {
                $a = [];
                $u = false;
                foreach ($rawResult->errors as $e) {
                    $s = $e->getMessage();
                    $a[] = $s;
                    $u = ($u or !empty($s));
                }
                $string = implode('; ', $a);

                $this->fail(!$u ? 'Query ended with errors, but they are empty' : $string);
            }

            $result = $rawResult->toArray();
            $this->assertIsArray($result);
            $this->assertArrayNotHasKey('errors', $result);

            return $result;
        }

        public function testSchema()
        {
            $query =
                'query IntrospectionQuery { __schema { queryType { name } mutationType { name } subscriptionType { name } types { ...FullType } directives { name description locations args { ...InputValue } } } } fragment FullType on __Type { kind name description fields(includeDeprecated: true) { name description args { ...InputValue } type { ...TypeRef } isDeprecated deprecationReason } inputFields { ...InputValue } interfaces { ...TypeRef } enumValues(includeDeprecated: true) { name description isDeprecated deprecationReason } possibleTypes { ...TypeRef } } fragment InputValue on __InputValue { name description type { ...TypeRef } defaultValue } fragment TypeRef on __Type { kind name ofType { kind name ofType { kind name ofType { kind name ofType { kind name ofType { kind name ofType { kind name ofType { kind name } } } } } } } }';

            $output = GraphqlQueryResolver::runQueryAsArray($query);
            $schema = $output['data']['__schema'];
            $this->assertArrayHasKey('queryType', $schema);
            $this->assertArrayHasKey('mutationType', $schema);
            $this->assertArrayHasKey('subscriptionType', $schema);
            $this->assertArrayHasKey('types', $schema);

            $unique_keys = [];
            foreach ($schema['types'] as $type) {
                $this->assertArrayHasKey('kind', $type);
                $this->assertArrayHasKey('name', $type);
                $this->assertArrayHasKey('description', $type);
                $this->assertArrayHasKey('fields', $type);
                $this->assertArrayHasKey('inputFields', $type);
                $this->assertArrayHasKey('interfaces', $type);
                $this->assertArrayHasKey('enumValues', $type);
                $this->assertArrayHasKey('possibleTypes', $type);

                $this->assertNotContains($type['name'], $unique_keys);
                $unique_keys[] = $type['name'];
            }

            $this->assertContains('Goods', $unique_keys);
            $this->assertContains('GoodsFeature', $unique_keys);
        }

        private static $letters;

        public static final function generateValue($num = 10): string
        {
            $s = chr(mt_rand(ord('a'), ord('z')));
            for ($j = 0; $j < $num - 1; $j++) {
                $s .= self::$letters[array_rand(self::$letters)];
            }

            return $s;
        }

        protected function assertReturnGoods(
            ?array $need_keys,
            array $newItem,
            array $expectedValues
        ) {
            if (is_null($need_keys)) {
                $need_keys = ['id', 'name', 'description', 'price'];
            }

            $name = $expectedValues['name'];
            $description = $expectedValues['description'];
            $price = $expectedValues['price'];

            foreach ($need_keys as $key) {
                $this->assertArrayHasKey($key, $newItem);
                switch ($key) {
                    case 'id':
                        $this->assertIsInt($newItem['id']);
                        break;
                    case 'description':
                        if (is_null($description)) {
                            $this->assertNull($newItem['description']);
                        } else {
                            $this->assertIsString($newItem['description']);
                            $this->assertEquals($description, $newItem['description']);
                        }

                        break;
                    case 'price':
                        if (is_null($price)) {
                            $this->assertEquals(0, $newItem['price']);
                        } else {
                            $this->assertIsNumeric($newItem['price']);
                            $this->assertEquals($price, $newItem['price']);
                        }
                        break;
                    case 'name':
                        $this->assertIsString($newItem['name']);
                        $this->assertEquals($name, $newItem['name']);
                        break;
                }
            }
        }

        protected function assertReturnGoodsFeature(
            ?array $need_keys,
            array $newItem,
            array $expectedValues
        ) {
            if (is_null($need_keys)) {
                $need_keys = ['id', 'name', 'value', 'goods_id'];
            }

            $name = $expectedValues['name'];
            $value = $expectedValues['value'];
            $goods_id = $expectedValues['goods_id'];

            foreach ($need_keys as $key) {
                $this->assertArrayHasKey($key, $newItem);
                switch ($key) {
                    case 'id':
                        $this->assertIsInt($newItem['id']);
                        break;
                    case 'value':
                        if (is_null($value)) {
                            $this->assertNull($newItem['value']);
                        } else {
                            $this->assertIsString($newItem['value']);
                            $this->assertEquals($value, $newItem['value']);
                        }

                        break;
                    case 'goods_id':
                        $this->assertIsInt($newItem['goods_id']);
                        $this->assertEquals($goods_id, $newItem['goods_id']);
                        break;
                    case 'name':
                        $this->assertIsString($newItem['name']);
                        $this->assertEquals($name, $newItem['name']);
                        break;
                }
            }
        }

        protected static function getNeedKeysForGoods(): array
        {
            $need_keys = ['id'];
            if (mt_rand(0, 1) == 0) {
                $need_keys[] = 'name';
            }
            if (mt_rand(0, 1) == 0) {
                $need_keys[] = 'price';
            }
            if (mt_rand(0, 1) == 0) {
                $need_keys[] = 'description';
            }
            if (mt_rand(0, 4) == 0) {
                shuffle($need_keys);
            }

            return $need_keys;
        }

        protected static function getNeedKeysForGoodsFeature(): array
        {
            $need_keys = ['id'];
            if (mt_rand(0, 1) == 0) {
                $need_keys[] = 'name';
            }
            if (mt_rand(0, 1) == 0) {
                $need_keys[] = 'goods_id';
            }
            if (mt_rand(0, 1) == 0) {
                $need_keys[] = 'value';
            }
            if (mt_rand(0, 4) == 0) {
                shuffle($need_keys);
            }

            return $need_keys;
        }

        protected static $feature_names = [];
        protected static $dataGoods = [];
        protected static $dataFeatures = [];

        public function dataAllMainQuery1_Insert()
        {
            // Создаём 10 записей Goods. У каждой 1-5 feature
            $a = [];
            for ($i = 0; $i < 20; $i++) {
                $a[] = [$i];
            }

            return $a;
        }

        /**
         * Вставка данных
         * @dataProvider dataAllMainQuery1_Insert
         *
         * @param int $innerId
         */
        public function testAllMainQuery1_Insert(/** @noinspection PhpUnusedParameterInspection */ int $innerId)
        {
            // hint: По-хорошему это должно делаться через ортогональные массивы, но у меня нет времени

            // Вставляем сам item
            $name = self::generateValue();
            $price = (mt_rand(0, 5) == 1) ? null : mt_rand(1, 10000) * 0.01;
            $description = (mt_rand(0, 5) == 1) ? null : self::generateValue(20);

            $need_keys = self::getNeedKeysForGoods();
            $raw = ['name' => $name,];
            if (!is_null($price) or (mt_rand(0, 3) > 0)) {
                $raw['price'] = $price;
            }
            if (!is_null($description) or (mt_rand(0, 3) > 0)) {
                $raw['description'] = $description;
            }

            $string_value = [];
            foreach ($raw as $key => $value) {
                $string_value[] = sprintf('%s: %s', $key, json_encode($value));
            }

            $query = sprintf('mutation{  
  insertGoods (%s) {
    %s  
}}',
                implode(', ', $string_value),
                implode(', ', $need_keys)
            );
            $result = $this->executeQuery($query);

            $newItem = $result['data']['insertGoods'];
            $this->assertReturnGoods($need_keys, $newItem,
                ['name' => $name, 'description' => $description, 'price' => $price]);
            $this->assertEmpty(array_diff(array_keys($newItem), $need_keys));

            $recordItem = [
                'id' => $newItem['id'],
                'price' => $price,
                'description' => $description,
                'name' => $name,
                'features' => [],
            ];

            // Вставляем его features
            $feature_count = mt_rand(0, 5);
            for ($j = 0; $j < $feature_count; $j++) {
                $name = self::generateValue();
                $valueFeature = self::generateValue();

                $need_keys = self::getNeedKeysForGoodsFeature();
                $raw = [
                    'name' => $name,
                    'value' => $valueFeature,
                    'goods_id' => $recordItem['id'],
                ];

                $string_value = [];
                foreach ($raw as $key => $value) {
                    $string_value[] = sprintf('%s: %s', $key, json_encode($value));
                }

                $query = sprintf('mutation{  
  insertGoodsFeature (%s) {
    %s  
}}',
                    implode(', ', $string_value),
                    implode(', ', $need_keys)
                );
                $result = $this->executeQuery($query);
                $newItem = $result['data']['insertGoodsFeature'];
                $this->assertReturnGoodsFeature($need_keys, $newItem,
                    ['name' => $name, 'value' => $valueFeature, 'goods_id' => $recordItem['id']]);
                $raw['id'] = $newItem['id'];

                // Полный get фичи, и её товар
                $query = sprintf('query{  
  GoodsFeature (id: %s) {
    id, name, value, goods_id, goods {id, name, description, price}
}}',
                    $raw['id']
                );
                $result = $this->executeQuery($query);
                $newItem = $result['data']['GoodsFeature'];
                $this->assertReturnGoodsFeature(null, $newItem,
                    ['name' => $name, 'value' => $valueFeature, 'goods_id' => $recordItem['id']]);
                $this->assertReturnGoods(null, $newItem['goods'], $recordItem);

                $recordItem['features'][] = $raw['id'];
                self::$dataFeatures[$newItem['id']] = $newItem;
            }
            unset($result, $newItem, $raw, $query);

            // Полный get товара
            $query = sprintf('query{  
  Goods (id: %s) {
    id, name, description, price, features {id, name, value, goods_id}
}}',
                $recordItem['id']
            );
            $result = $this->executeQuery($query);
            $newItem = $result['data']['Goods'];
            $this->assertReturnGoods(null, $newItem, $recordItem);
            $this->assertEquals($feature_count, count($recordItem['features']));
            $used_feature_id = [];
            foreach ($newItem['features'] as $existedFeature) {
                $used_feature_id[] = $existedFeature['id'];
                $expected_feature = self::$dataFeatures[$existedFeature['id']];
                $this->assertReturnGoodsFeature(null, $existedFeature, $expected_feature);
            }
            $this->assertEquals(count($used_feature_id), count(array_unique($used_feature_id)));
            $this->assertEmpty(array_diff($used_feature_id, $recordItem['features']));
            $this->assertEmpty(array_diff($recordItem['features'], $used_feature_id));

            self::$dataGoods[$recordItem['id']] = $recordItem;
        }

        public function dataAllMainQuery2_Update()
        {
            // Создаём 10 записей Goods. У каждой 1-5 feature
            $a = [];
            for ($i = 0; $i < 10; $i++) {
                $a[] = [$i];
            }

            return $a;
        }

        protected static $updateGoodsIds = null;
        protected static $updateGoodsFeatureIds = null;
        protected static $deleteGoodsIds = null;
        protected static $deleteGoodsFeatureIds = null;

        /**
         * Обновление данных в Goods
         *
         * @param int $innerId
         *
         * @dataProvider dataAllMainQuery2_Update
         */
        public function testAllMainQuery2_UpdateGoods(int $innerId)
        {
            if (empty(self::$dataGoods)) {
                $this->fail('dataGoods is empty');
            }
            $ids = array_keys(self::$dataGoods);
            if (is_null(self::$updateGoodsIds)) {
                shuffle($ids);
                self::$updateGoodsIds = array_chunk($ids, 10)[0];
            }
            $goods_id = self::$updateGoodsIds[$innerId];
            $raw = [];

            if (mt_rand(0, 2) == 0) {
                $name = self::generateValue();
                $raw['name'] = $name;
                self::$dataGoods[$goods_id]['name'] = $name;
            }
            if (mt_rand(0, 2) == 0) {
                $price = (mt_rand(0, 5) == 1) ? null : mt_rand(1, 10000) * 0.01;
                if (!is_null($price) or (mt_rand(0, 3) > 0)) {
                    $raw['price'] = $price;
                    self::$dataGoods[$goods_id]['price'] = $price;
                }
            }
            if (mt_rand(0, 2) == 0) {
                $description = (mt_rand(0, 5) == 1) ? null : self::generateValue(20);
                if (!is_null($description) or (mt_rand(0, 3) > 0)) {
                    $raw['description'] = $description;
                    self::$dataGoods[$goods_id]['description'] = $description;
                }
            }

            $need_keys = self::getNeedKeysForGoods();

            $string_value = ['id: '.$goods_id,];
            foreach ($raw as $key => $value) {
                $string_value[] = sprintf('%s: %s', $key, json_encode($value));
            }

            $query = sprintf('mutation{  
  updateGoods (%s) {
    %s  
}}',
                implode(', ', $string_value),
                implode(', ', $need_keys)
            );
            unset($raw, $string_value);
            $result = $this->executeQuery($query);
            $newItem = $result['data']['updateGoods'];
            $this->assertReturnGoods($need_keys, $newItem, self::$dataGoods[$goods_id]);

            // Проверяем все записи. Чтобы убедиться, что не зацепилась левая
            foreach ($ids as $oldId) {
                $query = sprintf('query{
  Goods (id: %s) {
    id, name, description, price, features {id, name, value, goods_id}
}}',
                    $oldId
                );
                $result = $this->executeQuery($query);
                $newItem = $result['data']['Goods'];

                $this->assertReturnGoods(null, $newItem, self::$dataGoods[$oldId]);
                foreach ($newItem['features'] as $feature) {
                    $this->assertContains($feature['id'], self::$dataGoods[$oldId]['features']);
                    $this->assertReturnGoodsFeature(null, $feature, self::$dataFeatures[$feature['id']]);
                }
            }
        }

        protected function checkDataGoodsFeatureConsistency()
        {
            if (empty(self::$dataFeatures)) {
                $this->fail('dataFeatures is empty');
            }
            $ids = array_keys(self::$dataFeatures);
            foreach ($ids as $oldId) {
                $query = sprintf('query{
  GoodsFeature (id: %s) {
    id, name, value, goods_id, goods {id, name, description, price}
}}',
                    $oldId
                );
                $result = $this->executeQuery($query);
                $newItem = $result['data']['GoodsFeature'];

                $this->assertReturnGoodsFeature(null, $newItem, self::$dataFeatures[$oldId]);
                $this->assertReturnGoods(null, $newItem['goods'], self::$dataGoods[$newItem['goods_id']]);
            }
        }

        /**
         * Обновление данных в GoodsFeature
         *
         * @param int $innerId
         *
         * @dataProvider dataAllMainQuery2_Update
         */
        public function testAllMainQuery3_UpdateGoodsFeature(int $innerId)
        {
            if (empty(self::$dataFeatures)) {
                $this->fail('dataFeatures is empty');
            }
            $ids = array_keys(self::$dataFeatures);
            if (is_null(self::$updateGoodsFeatureIds)) {
                shuffle($ids);
                self::$updateGoodsFeatureIds = array_chunk($ids, 10)[0];
            }
            $feature_id = self::$updateGoodsFeatureIds[$innerId];
            $raw = [];

            if (mt_rand(0, 2) == 0) {
                $name = self::generateValue();
                $raw['name'] = $name;
                self::$dataFeatures[$feature_id]['name'] = $name;
            }
            if (mt_rand(0, 2) == 0) {
                $value = (mt_rand(0, 5) == 1) ? null : self::generateValue(20);
                if (!is_null($value) or (mt_rand(0, 3) > 0)) {
                    $raw['value'] = $value;
                    self::$dataFeatures[$feature_id]['value'] = $value;
                }
            }

            $need_keys = self::getNeedKeysForGoodsFeature();

            $string_value = ['id: '.$feature_id,];
            foreach ($raw as $key => $value) {
                $string_value[] = sprintf('%s: %s', $key, json_encode($value));
            }

            $query = sprintf('mutation{
  updateGoodsFeature (%s) {
    %s
}}',
                implode(', ', $string_value),
                implode(', ', $need_keys)
            );
            unset($raw, $string_value);
            $result = $this->executeQuery($query);
            $newItem = $result['data']['updateGoodsFeature'];
            $this->assertReturnGoodsFeature($need_keys, $newItem, self::$dataFeatures[$feature_id]);

            // Проверяем все записи. Чтобы убедиться, что не зацепилась левая
            $this->checkDataGoodsFeatureConsistency();
        }

        /**
         * Выборочный get полей
         */
        public function testAllMainQuery4_Get()
        {
            if (empty(self::$dataGoods)) {
                $this->fail('dataGoods is empty');
            }
            $goods_id = array_keys(self::$dataGoods)[0];
            for ($i = 0; $i < 40; $i++) {
                $need_keys = self::getNeedKeysForGoods();

                $query = sprintf('query{
  Goods (id: %s) {
    %s
}}',
                    $goods_id,
                    implode(', ', $need_keys)
                );
                $result = $this->executeQuery($query);
                $newItem = $result['data']['Goods'];
                $this->assertEmpty(array_diff(array_keys($newItem), $need_keys));
                $this->assertEmpty(array_diff($need_keys, array_keys($newItem)));
            }

            $feature_id = array_keys(self::$dataFeatures)[0];
            for ($i = 0; $i < 40; $i++) {
                $need_keys = self::getNeedKeysForGoodsFeature();

                $query = sprintf('query{
  GoodsFeature (id: %s) {
    %s
}}',
                    $feature_id,
                    implode(', ', $need_keys)
                );
                $result = $this->executeQuery($query);
                $newItem = $result['data']['GoodsFeature'];
                $this->assertEmpty(array_diff(array_keys($newItem), $need_keys));
                $this->assertEmpty(array_diff($need_keys, array_keys($newItem)));
            }
        }

        /**
         * Выбираем все items
         */
        public function testAllMainQuery4a_GetList()
        {
            $query = 'query {allGoods {id, name, description, price, features{id,name,value,goods_id}}}';
            $result = $this->executeQuery($query);

            $all_goods = [];
            $all_features = [];
            foreach ($result['data']['allGoods'] as $goods) {
                $all_goods[$goods['id']] = $goods;
                foreach ($goods['features'] as $feature) {
                    $all_features[$feature['id']] = $feature;
                }
            }
            foreach (self::$dataGoods as $goods_id => $dataGood) {
                $this->assertArrayHasKey($goods_id, $all_goods);
                $this->assertReturnGoods(null, $all_goods[$goods_id], $dataGood);
            }
            unset($goods_id, $dataGood);
            foreach (self::$dataFeatures as $feature_id => $dataFeature) {
                $this->assertArrayHasKey($feature_id, $all_features);
                $this->assertReturnGoodsFeature(null, $all_features[$feature_id], $dataFeature);
            }
        }

        /**
         * Выбираем все items
         */
        public function testAllMainQuery4b_GetList()
        {
            if (empty(self::$dataGoods)) {
                $this->fail('dataGoods is empty');
            }
            $ids = array_keys(self::$dataGoods);
            shuffle($ids);
            for ($i = 0; $i < 5; $i++) {
                $goods_id = $ids[$i];
                $query = sprintf('query {allGoods(id: %s) {id}}', $goods_id);
                $result = $this->executeQuery($query);
                $this->assertEquals(1, count($result['data']['allGoods']));
                $goods = $result['data']['allGoods'][0];
                $this->assertEquals($goods_id, $goods['id']);
            }
        }

        /**
         * Удаление фич
         * @dataProvider dataAllMainQuery2_Update
         *
         * @param int $innerId
         */
        public function testAllMainQuery6_DeleteGoodsFeature(int $innerId)
        {
            if (empty(self::$dataFeatures)) {
                $this->fail('dataFeatures is empty');
            }
            $ids = array_keys(self::$dataFeatures);
            if (is_null(self::$deleteGoodsFeatureIds)) {
                if (count($ids) <= 10) {
                    self::$deleteGoodsFeatureIds = $ids;
                } else {
                    shuffle($ids);
                    self::$deleteGoodsFeatureIds = array_chunk($ids, 10)[0];
                }
            }
            if ($innerId >= count(self::$deleteGoodsFeatureIds)) {
                $this->markTestSkipped('Not enough data');

                return; // hint: сюда код не придёт никогда, markTestSkipped кидает ошибку
            }
            $feature_id = self::$deleteGoodsFeatureIds[$innerId];

            $query = sprintf('mutation {  deleteGoodsFeature (id: %s) }', $feature_id);
            $result = $this->executeQuery($query);
            $this->assertTrue($result['data']['deleteGoodsFeature']);

            $record = GoodsFeature::find()->where(['=', 'id', $feature_id])->one();
            $this->assertNull($record);
            $goods_id = self::$dataFeatures[$feature_id]['goods_id'];
            unset(self::$dataFeatures[$feature_id]);

            // Удаляем данные из goods
            self::$dataGoods[$goods_id]['features'] = array_diff(self::$dataGoods[$goods_id]['features'], [$feature_id]);

            // проверяем остальные записи
            $this->checkDataGoodsFeatureConsistency();
        }

        /**
         * Удаление товаров
         * @dataProvider dataAllMainQuery2_Update
         *
         * @param int $innerId
         */
        public function testAllMainQuery7_DeleteGoods(int $innerId)
        {
            if (empty(self::$dataGoods)) {
                $this->fail('dataGoods is empty');
            }
            $ids = array_keys(self::$dataGoods);
            if (is_null(self::$deleteGoodsIds)) {
                shuffle($ids);
                self::$deleteGoodsIds = array_chunk($ids, 10)[0];
            }
            $goods_id = self::$deleteGoodsIds[$innerId];

            $query = sprintf('mutation {  deleteGoods (id: %s) }', $goods_id);
            $result = $this->executeQuery($query);
            $this->assertTrue($result['data']['deleteGoods']);

            $record = Goods::find()->where(['=', 'id', $goods_id])->one();
            $this->assertNull($record);
            foreach (self::$dataGoods[$goods_id]['features'] as $feature_id) {
                unset(self::$dataFeatures[$feature_id]);
            }
            unset(self::$dataGoods[$goods_id]);

            // проверяем остальные записи
            $this->checkDataGoodsFeatureConsistency();
        }

        public function dataAllClasses(): array
        {
            return [
                ['Goods'],
                ['GoodsFeature'],
            ];
        }

        /**
         * Удаление пустого места
         *
         * @param string $class
         *
         * @dataProvider dataAllClasses
         */
        public function testAllMainQuery8_DeleteEmpties(string $class)
        {
            $query = sprintf(sprintf('mutation {  delete%s (id: -1) }', $class));
            $result = $this->executeQuery($query);
            $this->assertFalse($result['data']['delete'.$class]);
        }

        /**
         * @param string $class
         *
         * @dataProvider dataAllClasses
         */
        public function testEmptySingleItemRequest(string $class)
        {
            $query = sprintf('query{%s {id}}', $class);
            $rawResult = GraphqlQueryResolver::runQuery($query);
            $this->assertNotEmpty($rawResult->errors);
        }
    }
