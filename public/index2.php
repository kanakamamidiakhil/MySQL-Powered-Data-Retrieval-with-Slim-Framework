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

$app->post('/create-table', function (Request $request, Response $response, $args) use ($db_host, $db_username, $db_password, $db_name) {
    
    $mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

    
    if ($mysqli->connect_error) {
        return $response->withJson(["error" => "Connection failed: " . $mysqli->connect_error], 500);
    }

    
    $sql = "CREATE TABLE IF NOT EXISTS stock_data (
                ticker VARCHAR(10) NOT NULL,
                date DATE NOT NULL,
                revenue FLOAT,
                gp FLOAT,
                fcf FLOAT,
                capex FLOAT,
                PRIMARY KEY (ticker, date)
            )";


    if ($mysqli->query($sql) === TRUE) {
        $response->getBody()->write("Table created successfully\n");
    } else {
        $response->getBody()->write("Error creating table: " . $mysqli->error . "\n");
    }

    $mysqli->close();

    return $response;
});


$app->get('/parse-csv', function (Request $request, Response $response, $args) use ($db_host, $db_username, $db_password, $db_name) {
    
    $mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);


    if ($mysqli->connect_error) {
        return $response->withJson(["error" => "Connection failed: " . $mysqli->connect_error], 500);
    }


    $csvFile = fopen('/home/akhil/Downloads/Sample-Data-Historic.csv', 'r');
    if ($csvFile !== false) {

        for ($i = 0; $i < 1; $i++) {
            fgets($csvFile);
        }

        while (($data = fgetcsv($csvFile)) !== false) {
            $ticker = !empty($data[0]) ? $data[0] : null;
            $date = !empty($data[1]) ? date('Y-m-d', strtotime($data[1])) : null;
            $revenue = !empty($data[2]) ? $data[2] : 0;
            $gp = !empty($data[3]) ? $data[3] : 0;
            $fcf = !empty($data[4]) ? $data[4] : 0;
            $capex = !empty($data[5]) ? $data[5] : 0;

            
            $sql = "INSERT INTO stock_data (ticker, date, revenue, gp, fcf, capex) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                
                $stmt->bind_param('ssdddd', $ticker, $date, $revenue, $gp, $fcf, $capex);
                
                if ($stmt->execute()) {
                    $response->getBody()->write("Data inserted successfully\n");
                } else {
                    $response->getBody()->write("Error inserting data: " . $stmt->error . "\n");
                }
                
                $stmt->close();
            } else {
                $response->getBody()->write("Error preparing statement: " . $mysqli->error . "\n");
            }
        }

        fclose($csvFile);
    } else {
        $response->getBody()->write("Error opening CSV file\n");
    }

    $mysqli->close();

    return $response;
});


$app->get('/get-data', function (Request $request, Response $response, $args) use ($db_host, $db_username, $db_password, $db_name) {

    $mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

    if ($mysqli->connect_error) {
        return $response->withStatus(500)->withJson(["error" => "Connection failed: " . $mysqli->connect_error]);
    }

    $queryParams = $request->getQueryParams();
    $ticker = isset($queryParams['ticker']) ? $queryParams['ticker'] : null;
    $columns = isset($queryParams['column']) ? explode(',', $queryParams['column']) : array();
    $period = isset($queryParams['period']) ? $queryParams['period'] : null;


    $startDate = date('Y-m-d', strtotime("-{$period} years"));

    $columnList = implode(', ', $columns);
    $sql = "SELECT ticker, date, {$columnList} FROM stock_data WHERE ticker = '$ticker' AND date >= '$startDate'";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return $response->withStatus(500)->withJson(["error" => "Error preparing statement: " . $mysqli->error]);
    }
    $stmt->execute();


    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    $mysqli->close();

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
$app->run();
