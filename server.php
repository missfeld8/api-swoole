<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

require 'vendor/autoload.php';
require __DIR__ . '/src/Router.php';

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$server = new Server("localhost", 9503);

$databaseConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => 'password',
    'database' => 'database',
];

try {
    $db = new PDO(
        "mysql:host={$databaseConfig['host']};dbname={$databaseConfig['database']}",
        $databaseConfig['user'],
        $databaseConfig['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

$router = new Router();

// página inicial
$router->get('/', function (Request $request, Response $response) use ($db) {
    try {
        $response->header('Content-Type', 'application/json');

        $query = $db->query("SELECT * FROM api");
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        $response->write(json_encode(['status' => 200, 'data' => $result]));
        $response->end();
    } catch (PDOException $e) {
        $response->status(500);
        $response->write("Erro no banco de dados: " . $e->getMessage());
        $response->end();
    }
});


//arquivos estáticos 
$router->get('/static/{file}', function (Request $request, Response $response) {
    $filename = '';
    if (isset($request->get['file'])) {
        $filename = __DIR__ . '/public/' . $request->get['file'];
    }
    
    if (file_exists($filename)) {
        $response->header('Content-Type', mime_content_type($filename));
        $response->sendfile($filename);
    } else {
        $response->status(404);
        $response->end("Not Found");
    }
}); 


// criar um novo registro
$router->post('/create', function (Request $request, Response $response) use ($db) {
    try {
        $data = $_POST;

        // Verifica se os índices estão definidos antes de acessá-los
        if (isset($data['name'], $data['age'], $data['description'], $data['office'])) {
            $insertQuery = $db->prepare("INSERT INTO api (name, age, description, office) VALUES (?, ?, ?, ?)");
            $insertQuery->execute([$data['name'], $data['age'], $data['description'], $data['office']]);

            $response->header('Content-Type', 'application/json; charset=utf-8');
            $response->write(json_encode(['status' => 201, 'message' => 'Registro criado com sucesso']));
            $response->end();
        } else {
            $response->status(400); // Bad Request
            $response->write(json_encode(['status' => 400, 'message' => 'Registro não foi criado']));
            $response->end();
        }
    } catch (PDOException $e) {
        $response->status(500);
        $response->write("Erro no banco de dados: " . $e->getMessage());
        $response->end();
    }
});

$router->put('/update/{id}', function (Request $request, Response $response, $id) use ($db) {
    try {
        $body = $request->getBody()->__toString();
        // decodificar os dados JSON
        $data = json_decode($body, true);

        //decodificação foi bem-sucedida
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Erro na decodificação JSON: ' . json_last_error_msg());
        }

        // Adicione logs para depuração
        if ($data === null) {
            error_log('Erro na decodificação JSON: ' . json_last_error_msg());
        }

        // Verifica se os dados são válidos
        $requiredFields = ['name', 'age', 'description', 'office'];
        if (array_diff($requiredFields, array_keys($data)) === []) {
            $updateQuery = $db->prepare("UPDATE api SET name = ?, age = ?, description = ?, office = ? WHERE id = ?");
            $updateQuery->execute([$data['name'], $data['age'], $data['description'], $data['office'], $id]);

            $response->header('Content-Type', 'application/json');
            $response->write(json_encode(['status' => 200, 'message' => 'Registro atualizado com sucesso']));
        } else {
            $response->status(400); // Bad Request
            $response->header('Content-Type', 'application/json');
            $response->write(json_encode(['status' => 400, 'message' => 'Parâmetros inválidos']));
        }
    } catch (PDOException $e) {
        $response->status(500);
        $response->header('Content-Type', 'application/json');
        $response->write(json_encode(['status' => 500, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]));
    } catch (Exception $e) {
        $response->status(400); // Bad Request
        $response->header('Content-Type', 'application/json');
        $response->write(json_encode(['status' => 400, 'message' => 'Erro na solicitação: ' . $e->getMessage()]));
    } finally {
        $response->end();
    }
});


// excluir um registro
$router->delete('/delete/{id}', function (Request $request, Response $response, $id) use ($db) {
    try {
        $deleteQuery = $db->prepare("DELETE FROM api WHERE id = ?");
        $deleteQuery->execute([$id]);

        $response->header('Content-Type', 'application/json');
        $response->write(json_encode(['status' => 200, 'message' => 'Registro excluído com sucesso']));
        $response->end();
    } catch (PDOException $e) {
        $response->status(500);
        $response->write("Erro no banco de dados: " . $e->getMessage());
        $response->end();
    }
});





$server->on("start", function (Server $server) {
    echo "OpenSwoole http server is started at http://localhost:9503\n";
});

$server->on(
    "request",
    function (Request $request, Response $response) use ($router) {
        try {
            $router->resolve($request, $response);
        } catch (Exception $e) {
            $response->status(500);
            $response->end("Server Error: " . $e->getMessage());
        }
    }
);

$server->start();
