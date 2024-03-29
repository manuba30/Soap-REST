<?php
// Inclure le fichier de logique des utilisateurs
require "./users.php";
require "./security.php";
// Récupérer la méthode HTTP et l'URI
$uri = $_SERVER["REQUEST_URI"];
$method = $_SERVER["REQUEST_METHOD"];

// verification de JWT
$check = false;
function verify()
{
    global $check;
    $headers = getallheaders();
    $token = explode(' ', $headers['Authorization'])[1]; // On récupère le token en extraitant les espaces
    $secret_key = base64_encode("martin56@");
    try {
        $check = validate_token($token, $secret_key);  // Validation du jeton d'authentification
    } catch (\Throwable $th) {
        echo json_encode([
            "code" => 401,
            "message" => "Unauthorized: Bad or expired token."
        ]);
        die();
    }
}


// echo '<pre>';
// var_dump($token);
// echo '</pre>';
// die();
// Routeur pour les différentes opérations CRUD
switch ($method) {
    case 'GET':
        verify();
        if ($check) {
            preg_match("/^\/formation_php\/php-web-service\/REST\/users\/?(\d+)?$/", $uri, $matches);
            // var_dump($matches);
            if (!empty($matches) && !empty($users) && !array_key_exists(1, $matches)) {
                $users = getAll();
                echo json_encode($users);  // Envoyer une réponse au client en format JSON
                break;
                if (array_key_exists(1, $matches)) {
                    $user = getOne((int)$matches[1]);
                    var_dump("getOne", $matches);
                    echo json_encode($user);
                    break;
                    // echo '<pre>';
                    // var_dump($user);
                    // echo '</pre>';
                }
                if (empty($users)) {
                    json_encode([
                        "error" => "No user found",
                        "code" => 404
                    ]);
                }
            }
        }
        echo json_encode([
            'code' => 403,
            'message' => 'Forbidden',
        ]);
        break;
    case 'POST':
        $user = $_POST;
        preg_match("/^\/formation_php\/php-web-service\/REST\/(users||register||login)\/?(\d+)?$/", $uri, $matches);
        if ($matches[1] === "users") {
            $user = create($user);
            echo json_encode($user);
        }
        if ($matches[1] === "register") {
            $passwordEncoded = base64_encode($user['password']);
            try {
                $token = generateToken($passwordEncoded, $user["email"]);
                echo json_encode([
                    "token" => $token
                ]);
            } catch (\Throwable $th) {
                echo json_encode([
                    "code" => 500,
                    "message" => "Internal Server Error",
                ]);
            }
            if ($matches[1] === "login") {
                $passwordEncoded = null;
                foreach ($users as $item) {
                    if ($item["email"] === $user["email"]) {
                        if ($item["password"] === $user["password"]) {
                            $passwordEncoded = base64_encode($user['password']);
                        }
                    }
                    try {
                        $token = generateToken($passwordEncoded, $user["email"]);
                        echo json_encode([
                            "token" => $token
                        ]);
                    } catch (\Throwable $th) {
                        echo json_encode([
                            "code" => 500,
                            "message" => "Internal Server Error",
                        ]);
                    }
                }
            }
        }
        break;

    case "PATCH":
        preg_match("/^\/formation_php\/php-web-service\/REST\/users\/?(\d+)?$/", $uri, $matches);
        var_dump($matches);
        $id = (int)$matches[1];
        $updates = file_get_contents("php://input");
        $items = explode('&', $updates);
        $array = [];
        foreach ($items as $item) {
            $inputs = explode("=", $item);
            $array[$inputs[0]] = $inputs[1];
        }
        $result = update($id, $array);
        echo json_encode($result);

        break;


    case "PUT":
        preg_match("/^\/formation_php\/php-web-service\/REST\/users\/?(\d+)?$/", $uri, $matches);
        $id = (int)$matches[1];
        $updates = file_get_contents("php://input");
        $items = explode('&', $updates);
        $array = [];
        foreach ($items as $item) {
            $inputs = explode("=", $item);
            $array[$inputs[0]] = $inputs[1];
        }
        $result = remplace($id, $array);
        echo json_encode($result);

        break;

    case "DELETE":
        preg_match("/^\/formation_php\/php-web-service\/REST\/users\/?(\d+)?$/", $uri, $matches);
        $id = (int)$matches[1];
        $result = delete($id);
        echo json_encode($result);

        break;


    default:
        http_response_code(404);
        echo json_encode([
            'message' => 'ressource introuvable',
            'http_status_code' => 404
        ]);
        break;
}


// echo '<pre>';
// var_dump($uri, $method);
// echo '</pre>';