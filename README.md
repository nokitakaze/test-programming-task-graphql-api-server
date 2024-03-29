# Тестовое задание на имлементацию GraphQL-сервера
## Текст задания
Создать CRUD для товара в виде GraphQL API (сервера с программным интерфейсом на GraphQL).

Товар содержит следующие поля: 
- Наименование товара
- Цена
- Описание
- Характеристики товара 

Данные по товару хранить в БД Mysql далее таблицу товары привести ко второй нормальной форме.
По использованию инструментов ограничений нет.

В качестве интерфейса для тестирования предлагается использование расширений для Google Chrome:
- [ChromeiQL](https://chrome.google.com/webstore/detail/chromeiql/fkkiamalmpiidkljmicmjfbieiclmeij)
- [GraphIQL Feen](https://chrome.google.com/webstore/detail/graphiql-feen/mcbfdonlkfpbfdpimkjilhdneikhfklp)

Преимуществом будет использование:
- ORM
- Сторонней библиотеки для валидации на бэке
- PHPUnit

|| Изначально задание представляло из себя набор баззвордов. До приемлимого вида доведено здесь. ||

## Результат
За каркас взята basic-комлпектация Yii 2.0.14. Каркас был необходим так как есть доступ к сущностям из базы данных, который осуществляется не по конкретным шаблонам, а по предикатам, взятым из запросов.

Устанавливать так:
`composer install --no-interaction`

Не забыть установить DDL и поправить конфиги к базе данных `config/db.php`

Конфиг nginx внутри: `nginx-conf.conf`

Тестировать так:
`composer test`
93% кодо-покрытия.

Реализован весь CRUD: create, read, update, delete.

Ушло 14 часов, много времени (4-5 часов) ушло на изучение GraphQL (для меня это совершенно новый язык) и GraphQL-PHP.

Data-контракт для GraphQL налету создаётся из модели:
1. Каждый query для каждой сущности имеет возможность фильтровать по любому из доступных полей. Это касается и запроса одиночной сущности и запроса всех сущностей сразу
2. Тоже самое касается update & insert
3. Реляции вставляются полностью для каждой из сущностей для обоих типов реляций (BELONG / HAS)
4. Для того чтобы создать новую совершенно рабочую сущность, нужно просто прописать ей поля и реляции. То есть написанный код расширяем. Весь необходимый behaviour CRUD'а для работы сущности выставится сам

Не считая некоторых огрехов по незнанию GraphQL (например, что если у нас есть сущности с названиями Query/Mutation или что небходимо сделать, если single query не может найти сущность) и императивной установки названия примарного ключа = `id`, этот код вполне можно использовать в продакшене. Сделано на долгие года использования.
Также надо проверить best practice по ограничению подзапросов, пока ограничения нет.

Удаление сущности не ведёт за собой удаление всех её зависимостей. Это необходимо продумывать заранее и прописывать правила удаления в реляциях. Как это сделано с foreign key в СУБД (например, в MySQL).

### Примеры запросов
```GraphQL
query {
  allGoodsFeature(goods_id: 1) {
    name,
    goods {
      name,
      id
    }
  }
}
```
```GraphQL
query {
  allGoodsFeature() {
    name,
    goods {
      name,
      id
    }
  }
}
```
```GraphQL
query {allGoods {id, name, description, price, features{id,name,value,goods_id}}}
```
```GraphQL
mutation {  
  insertGoodsFeature (name: "g63d8cx4hg", value: "fxjvc7jjee", goods_id: 12) {
    id, name  
  }
}
```
```GraphQL
mutation {  
  updateGoodsFeature (id: 100500, name: "g63d8cx4hg", value: "fxjvc7jjee", goods_id: 12) {
    id, name  
  }
}
```
```GraphQL
mutation {  
  deleteGoodsFeature (id: 100500)
}
```

## Баджи о качестве кода
[![Build Status](https://secure.travis-ci.org/nokitakaze/test-programming-task-graphql-api-server.png?branch=master)](http://travis-ci.com/nokitakaze/test-programming-task-graphql-api-server)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/nokitakaze/test-programming-task-graphql-api-server/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/nokitakaze/test-programming-task-graphql-api-server/)
![Code Coverage](https://scrutinizer-ci.com/g/nokitakaze/test-programming-task-graphql-api-server/badges/coverage.png?b=master)
