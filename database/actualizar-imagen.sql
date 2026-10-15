-- Ejecutar solo en una tabla existente que todavia no tenga imagen.
USE primera_base_datos;
ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL;
