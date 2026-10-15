<?php
require_once __DIR__ . "/comun.php";

function leerProducto() {
    $datos = leerJson();

    if (!is_array($datos)) {
        responder(400, ["error" => "El cuerpo debe ser un JSON valido"]);
    }

    if (
        !isset($datos["nombre"]) ||
        !is_string($datos["nombre"]) ||
        trim($datos["nombre"]) === ""
    ) {
        responder(400, ["error" => "El nombre es obligatorio"]);
    }

    $nombre = trim($datos["nombre"]);

    if (mb_strlen($nombre) > 100) {
        responder(400, ["error" => "El nombre no puede superar 100 caracteres"]);
    }

    if (
        !isset($datos["precio"]) ||
        !is_numeric($datos["precio"]) ||
        $datos["precio"] < 0 ||
        $datos["precio"] > 99999999.99
    ) {
        responder(400, ["error" => "Precio fuera del rango permitido"]);
    }

    return [$nombre, $datos["precio"]];
}

function comprobarNombreUnico($conexion, $nombre, $idExcluir = null) {
    $sql = "SELECT id FROM productos WHERE nombre = ?";
    $parametros = [$nombre];

    if ($idExcluir !== null) {
        $sql .= " AND id <> ?";
        $parametros[] = $idExcluir;
    }

    $consulta = $conexion->prepare($sql);
    $consulta->execute($parametros);

    if ($consulta->fetch() !== false) {
        responder(409, ["error" => "Ya existe un producto con ese nombre"]);
    }
}

function comprobarProducto($conexion, $id) {
    $consulta = $conexion->prepare(
        "SELECT id FROM productos WHERE id = ?"
    );
    $consulta->execute([$id]);

    if ($consulta->fetch() === false) {
        responder(404, ["error" => "Producto no encontrado"]);
    }
}

try {
    require_once __DIR__ . "/conexion.php";

    switch ($_SERVER["REQUEST_METHOD"]) {
        case "GET":
            $condiciones = [];
            $parametros = [];

            if (isset($_GET["id"])) {
                $id = validarId();
                $condiciones[] = "id = ?";
                $parametros[] = $id;
            }

            if (isset($_GET["nombre"])) {
                if (!is_string($_GET["nombre"])) {
                    responder(400, ["error" => "Nombre invalido"]);
                }

                $condiciones[] = "nombre LIKE ?";
                $parametros[] = "%" . trim($_GET["nombre"]) . "%";
            }

            $sql = "SELECT id, nombre, precio, imagen FROM productos";

            if ($condiciones) {
                $sql .= " WHERE " . implode(" AND ", $condiciones);
            }

            if (isset($_GET["orden"]) && $_GET["orden"] !== "") {
                $orden = $_GET["orden"];
                $permitidos = ["id", "nombre", "precio"];

                if (!in_array($orden, $permitidos, true)) {
                    responder(400, ["error" => "Criterio de ordenamiento no valido"]);
                }

                $direccion = $_GET["direccion"] ?? "asc";

                if (!is_string($direccion)) {
                    responder(400, ["error" => "Direccion no valida"]);
                }

                $direccion = strtolower($direccion);

                if (!in_array($direccion, ["asc", "desc"], true)) {
                    responder(400, ["error" => "Direccion no valida"]);
                }

                $sql .= " ORDER BY " . $orden . " " . strtoupper($direccion);
            }

            $consulta = $conexion->prepare($sql);
            $consulta->execute($parametros);
            $productos = $consulta->fetchAll();

            if (!$productos) {
                responder(404, ["error" => "No se encontraron productos"]);
            }

            if (isset($_GET["id"])) {
                responder(200, $productos[0]);
            }

            responder(200, $productos);
        case "POST":
            [$nombre, $precio] = leerProducto();
            comprobarNombreUnico($conexion, $nombre);

            $consulta = $conexion->prepare(
                "INSERT INTO productos (nombre, precio) VALUES (?, ?)"
            );
            $consulta->execute([$nombre, $precio]);

            responder(201, [
                "mensaje" => "Producto creado correctamente",
                "id" => $conexion->lastInsertId()
            ]);

        case "PUT":
            $id = validarId();
            [$nombre, $precio] = leerProducto();
            comprobarProducto($conexion, $id);
            comprobarNombreUnico($conexion, $nombre, $id);

            $consulta = $conexion->prepare(
                "UPDATE productos SET nombre = ?, precio = ? WHERE id = ?"
            );
            $consulta->execute([$nombre, $precio, $id]);

            responder(200, [
                "mensaje" => "Producto actualizado correctamente"
            ]);

        case "DELETE":
            $id = validarId();

            $consulta = $conexion->prepare(
                "SELECT imagen FROM productos WHERE id = ?"
            );
            $consulta->execute([$id]);
            $producto = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                responder(404, ["mensaje" => "Producto no encontrado"]);
            }

            $consulta = $conexion->prepare(
                "DELETE FROM productos WHERE id = ?"
            );
            $consulta->execute([$id]);

            borrarArchivoImagen($producto["imagen"]);

            responder(200, [
                "mensaje" => "Producto eliminado correctamente"
            ]);


        default:
            responder(405, ["error" => "Metodo no permitido"]);
    }
} catch (PDOException $error) {
    responder(500, ["error" => "Error interno del servidor"]);
}




