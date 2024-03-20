<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$db_host = 'localhost';
$db_username = 'root';
$db_password = 'new_password';
$db_name = 'mysql';

$app = AppFactory::create();

$app->get('/create-table', function (Request $request, Response $response, $args) use ($db_host, $db_username, $db_password, $db_name) {
    
    $mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

    
    if ($mysqli->connect_error) {
        return $response->withJson(["error" => "Connection failed: " . $mysqli->connect_error], 500);
    }

    
    $sql = "CREATE TABLE IF NOT EXISTS example_table (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                firstname VARCHAR(30) NOT NULL,
                lastname VARCHAR(30) NOT NULL,
                email VARCHAR(50),
                reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";

    
    if ($mysqli->query($sql) === TRUE) {
        $response->getBody()->write("Table created successfully");
    } else {
        $response->getBody()->write("Error creating table: " . $mysqli->error);
    }

    
    $mysqli->close();

    return $response;
});


$app->run();
