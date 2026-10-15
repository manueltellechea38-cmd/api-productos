<?php

require_once __DIR__ . "/comun.php";

const TAMANO_MAXIMO = 2 * 1024 * 1024;
const LADO_MAXIMO = 4000;

const TIPOS_PERMITIDOS = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

function buscarProducto($conexion, $id) {
    $consulta = $conexion->prepare(
        "SELECT id, imagen FROM productos WHERE id = ?"
    );
    $consulta->execute([$id]);
    return $consulta->fetch(PDO::FETCH_ASSOC);
}

try {
    require_once __DIR__ . "/conexion.php";

    switch ($_SERVER["REQUEST_METHOD"]) {
        case "POST":
            $id = validarId();
            $producto = buscarProducto($conexion, $id);

            if (!$producto) {
                responder(404, ["mensaje" => "Producto no encontrado"]);
            }

            if (
                empty($_FILES) &&
                empty($_POST) &&
                (int) ($_SERVER["CONTENT_LENGTH"] ?? 0) > 0
            ) {
                responder(413, [
                    "error" => "El archivo es demasiado grande (maximo 2 MB)"
                ]);
            }

            if (
                !isset($_FILES["imagen"]) ||
                is_array($_FILES["imagen"]["error"])
            ) {
                responder(400, [
                    "error" => "Debe enviar un archivo en el campo imagen"
                ]);
            }

            $archivo = $_FILES["imagen"];

            if ($archivo["error"] === UPLOAD_ERR_NO_FILE) {
                responder(400, [
                    "error" => "Debe enviar un archivo en el campo imagen"
                ]);
            }

            if (
                $archivo["error"] === UPLOAD_ERR_INI_SIZE ||
                $archivo["error"] === UPLOAD_ERR_FORM_SIZE
            ) {
                responder(413, [
                    "error" => "El archivo es demasiado grande (maximo 2 MB)"
                ]);
            }

            if ($archivo["error"] !== UPLOAD_ERR_OK) {
                responder(500, ["error" => "No se pudo recibir el archivo"]);
            }

            if ($archivo["size"] > TAMANO_MAXIMO) {
                responder(413, [
                    "error" => "El archivo es demasiado grande (maximo 2 MB)"
                ]);
            }

            if (!is_uploaded_file($archivo["tmp_name"])) {
                responder(400, ["error" => "Archivo no valido"]);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($archivo["tmp_name"]);

            if (!isset(TIPOS_PERMITIDOS[$mime])) {
                responder(415, [
                    "error" => "Formato no permitido. Usa JPG, PNG o WEBP"
                ]);
            }

            $info = getimagesize($archivo["tmp_name"]);

            if ($info === false) {
                responder(415, ["error" => "El archivo no es una imagen valida"]);
            }

            if ($info[0] > LADO_MAXIMO || $info[1] > LADO_MAXIMO) {
                responder(400, [
                    "error" => "La imagen no puede superar 4000 x 4000 pixeles"
                ]);
            }

            $nombreNuevo = bin2hex(random_bytes(16))
                . "." . TIPOS_PERMITIDOS[$mime];

            if (!is_dir(CARPETA_UPLOADS)) {
                mkdir(CARPETA_UPLOADS, 0755, true);
            }

            if (!move_uploaded_file(
                $archivo["tmp_name"],
                CARPETA_UPLOADS . $nombreNuevo
            )) {
                responder(500, ["error" => "No se pudo guardar el archivo"]);
            }

            try {
                $consulta = $conexion->prepare(
                    "UPDATE productos SET imagen = ? WHERE id = ?"
                );
                $consulta->execute([$nombreNuevo, $id]);
            } catch (PDOException $error) {
                borrarArchivoImagen($nombreNuevo);
                throw $error;
            }

            borrarArchivoImagen($producto["imagen"]);

            responder(200, [
                "mensaje" => "Imagen guardada correctamente",
                "imagen" => $nombreNuevo
            ]);

        case "DELETE":
            $id = validarId();
            $producto = buscarProducto($conexion, $id);

            if (!$producto) {
                responder(404, ["mensaje" => "Producto no encontrado"]);
            }

            if (empty($producto["imagen"])) {
                responder(404, ["mensaje" => "El producto no tiene imagen"]);
            }

            $consulta = $conexion->prepare(
                "UPDATE productos SET imagen = NULL WHERE id = ?"
            );
            $consulta->execute([$id]);

            borrarArchivoImagen($producto["imagen"]);

            responder(200, [
                "mensaje" => "Imagen eliminada correctamente"
            ]);

        default:
            responder(405, ["error" => "Metodo no permitido"]);
    }
} catch (PDOException $error) {
    error_log($error->getMessage());
    responder(500, ["error" => "Error interno del servidor"]);
}
