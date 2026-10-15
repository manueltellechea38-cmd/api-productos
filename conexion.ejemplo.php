<?php
$conexion = new PDO(
    "mysql:host=127.0.0.1;dbname=primera_base_datos;charset=utf8mb4",
    "root",
    "1234",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
