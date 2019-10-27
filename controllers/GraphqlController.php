<?php

    namespace app\controllers;

    use app\lib\Mxmodel;
    use Yii;
    use yii\filters\AccessControl;
    use yii\web\BadRequestHttpException;
    use yii\web\Controller;
    use yii\web\Response;
    use yii\filters\VerbFilter;
    use app\models\LoginForm;
    use app\models\ContactForm;
    use GraphQL\GraphQL;
    use GraphQL\Type\Schema;
    use GraphQL\Type\Definition\Type;
    use GraphQL\Type\Definition\ObjectType;

    class GraphqlController extends Controller
    {
        function __construct($id, $module, $config = [])
        {
            parent::__construct($id, $module, $config);
        }

        /**
         * @param \yii\base\InlineAction $action
         *
         * @return bool
         * @throws BadRequestHttpException
         */
        public function beforeAction($action)
        {
            switch ($action->id) {
                case 'request':
                    $this->enableCsrfValidation = false;
                    break;
            }

            return parent::beforeAction($action);
        }

        /**
         * {@inheritdoc}
         */
        public function behaviors()
        {
            return [
                'access' => [
                    'class' => AccessControl::class,
                    'only' => ['logout'],
                    'rules' => [
                        [
                            'actions' => ['logout'],
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'logout' => ['post'],
                    ],
                ],
            ];
        }

        /**
         * {@inheritdoc}
         */
        public function actions()
        {
            return [
                'error' => [
                    'class' => 'yii\web\ErrorAction',
                ],
                'captcha' => [
                    'class' => 'yii\captcha\CaptchaAction',
                    'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                ],
            ];
        }

        /**
         * Displays about page.
         *
         * @return string
         */
        public function actionRequest()
        {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            try {
                // Получение запроса
                $rawInput = file_get_contents('php://input');
                $input = json_decode($rawInput, true);
                $query = $input['query'];

                // Содание типа данных "Запрос"
                /*
                $queryType = new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'hello' => [
                            'type' => Type::string(),
                            'description' => 'Возвращает приветствие',
                            'resolve' => function () {
                                return 'Привет, GraphQL!';
                            },
                        ],
                    ],
                ]);

                // Создание схемы
                $schema = new Schema([
                    'query' => $queryType,
                ]);
                */

                $schema = Mxmodel::getGraphQLSchema();

                // Выполнение запроса
                $result = GraphQL::executeQuery($schema, $query)->toArray();
            } catch (\Exception $e) {
                $a = 0;
                $result = [
                    'error' => [
                        'message' => $e->getMessage(),
                    ],
                ];
            }

            return $result;
        }
    }
