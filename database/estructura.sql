CREATE DATABASE IF NOT EXISTS primera_base_datos
    CHARACTER SET utf8mb4;

USE primera_base_datos;

CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL
);
