<?php
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;
use Phalcon\Http\Request;


// ローダーの定義
$loader = new Loader();

$loader->registerNamespaces(
    [
        'Store\Toys' => __DIR__ . '/models/',
    ]
);

$loader->register();

//diの構築
$di = new FactoryDefault();

$di->set(
    "db",
    function () {
        return new PdoMysql(
            [
                "host"     => "localhost",
                "username" => "root",
                "password" => "",
                "dbname"   => "test",
            ]
        );
    }
);

$app = new Micro($di);

// Retrieves all products
$app->get(
    "/api/products",
    function () use ($app) {
        $phql = "SELECT * FROM Store\Toys\products ORDER BY name";

        $products = $app->modelsManager->executeQuery($phql);

        $data = [];

        foreach ($products as $product) {
            $data[] = [
                "id"   => $product->id,
                "name" => $product->name,
            ];
        }

        echo json_encode($data);
    }
);

// Searches for products with $name in their name
$app->get(
    "/api/products/search/{name}",
    function ($name) use ($app) {
        $phql = "SELECT * FROM Store\Toys\products WHERE name LIKE :name: ORDER BY name";

        $products = $app->modelsManager->executeQuery(
            $phql,
            [
                "name" => "%" . $name . "%"
            ]
        );

        $data = [];

        foreach ($products as $product) {
            $data[] = [
                "id"   => $product->id,
                "name" => $product->name,
            ];
        }

        echo json_encode($data);
    }
);

// Retrieves products based on primary key
$app->get(
    "/api/products/{id:[0-9]+}",
    function ($id) use ($app) {
        $phql = "SELECT * FROM Store\Toys\products WHERE id = :id:";

        $product = $app->modelsManager->executeQuery(
            $phql,
            [
                "id" => $id,
            ]
        )->getFirst();



        // Create a response
        $response = new Response();

        if ($product === false) {
            $response->setJsonContent(
                [
                    "status" => "NOT-FOUND"
                ]
            );
        } else {
            $response->setJsonContent(
                [
                    "status" => "FOUND",
                    "data"   => [
                        "id"   => $product->id,
                        "name" => $product->name
                    ]
                ]
            );
        }

        return $response;
    }
);

// Adds a new product
$app->post(
    '/api/products',
    function () use ($app) {
        $product = file_get_contents('php://input');
        //$product = $app->request->getJsonRawBody(file_get_contents('php://input'));
        //$product = json_decode($input);
        //$product = $this->objectToArray($this->request->getJsonRawBody());

        //echo $input;
        echo $product;

        $phql = 'INSERT INTO Store\Toys\products (name, manual, price, image) VALUES (:name:, :manual:, :price:, :image:)';

        //がんばってSQL文を変換
        $product = str_replace('"', '', $product);
        $product = str_replace("'", '', $product);
        $product = str_replace(':', '', $product);
        $product = str_replace('{', '', $product);
        $product = str_replace('}', '', $product);

        var_dump($product);

        $product_array = explode(',',$product);

        $id = str_replace('id', '', $product_array[0]);
        $name = str_replace('name', '', $product_array[1]);
        $manual = str_replace('manual', '', $product_array[2]);
        $price = str_replace('price', '', $product_array[3]);
        $image = str_replace('image', '', $product_array[4]);

        //$test = $app->modelsManager->executeQuery($phql);
        //echo $test;

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'id' => $id,
                'name' => $name,
                'manual' => $manual,
                'price' => $price,
                'image' => $image,
            ]
        );

        // Create a response
        $response = new Response();

        // Check if the insertion was successful
        if ($status->success() === true) {
            // Change the HTTP status
            $response->setStatusCode(201, "Created");

            $id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    "status" => "OK",
                    "data"   => $product,
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, "Conflict");

            // Send errors to the client
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    "status"   => "ERROR",
                    "messages" => $errors,
                ]
            );
        }

        return $response;
    }
);

// Updates products based on primary key
$app->put(
    "/api/products/{id:[0-9]+}",
    function ($id) use ($app) {
        $product = file_get_contents('php://input');

        $phql = "UPDATE Store\Toys\products SET name = :name:, manual = :manual:, price = :price:, image = :image: WHERE id = :id:";

        $product = str_replace('"', '', $product);
        $product = str_replace("'", '', $product);
        $product = str_replace(':', '', $product);
        $product = str_replace('{', '', $product);
        $product = str_replace('}', '', $product);

        var_dump($product);

        $product_array = explode(',',$product);

        $name = str_replace('name', '', $product_array[0]);
        $manual = str_replace('manual', '', $product_array[1]);
        $price = str_replace('price', '', $product_array[2]);
        $image = str_replace('image', '', $product_array[3]);

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                "id"   => $id,
                "name" => $name,
                "manual" => $manual,
                "price" => $price,
                "image" => $image,
            ]
        );

        // Create a response
        $response = new Response();

        // Check if the insertion was successful
        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    "status" => "OK"
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, "Conflict");

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    "status"   => "ERROR",
                    "messages" => $errors,
                ]
            );
        }

        return $response;
    }
);

// Deletes products based on primary key
$app->delete(
    "/api/products/{id:[0-9]+}",
    function ($id) use ($app) {
        $phql = "DELETE FROM Store\Toys\products WHERE id = :id:";

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                "id" => $id,
            ]
        );

        // Create a response
        $response = new Response();

        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    "status" => "OK"
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, "Conflict");

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    "status"   => "ERROR",
                    "messages" => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->handle();