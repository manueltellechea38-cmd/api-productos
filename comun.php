<?php

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

function responder($codigo, $datos) {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function leerJson() {
    $datos = json_decode(file_get_contents("php://input"), true);

    if (!is_array($datos)) {
        responder(400, [
            "error" => "El cuerpo de la peticion debe ser un JSON valido"
        ]);
    }

    return $datos;
}

function validarId() {
    if (!isset($_GET["id"])) {
        responder(400, ["error" => "Debe indicar el ID del producto"]);
    }

    $id = $_GET["id"];

    if (!is_string($id) || !ctype_digit($id) || (int) $id < 1) {
        responder(400, ["error" => "El ID debe ser un entero positivo"]);
    }

    return (int) $id;
}

const CARPETA_UPLOADS = __DIR__ . "/uploads/";

function borrarArchivoImagen($nombre) {
    if (!empty($nombre)) {
        $ruta = CARPETA_UPLOADS . basename($nombre);

        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}
