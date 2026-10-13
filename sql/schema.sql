CREATE DATABASE IF NOT EXISTS basedatossistema
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE basedatossistema;

CREATE TABLE IF NOT EXISTS usuarios (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100)  NOT NULL,
  apellido      VARCHAR(100)  NOT NULL DEFAULT '',
  departamento  VARCHAR(100)  NOT NULL DEFAULT '',
  email         VARCHAR(150)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  rol           ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
  creado_en     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuario_modulos (
  usuario_id INT         NOT NULL,
  modulo     VARCHAR(50) NOT NULL,
  PRIMARY KEY (usuario_id, modulo),
  CONSTRAINT fk_usuario_modulos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS departamentos (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE
);

INSERT IGNORE INTO departamentos (nombre) VALUES
  ('Administracion'), ('RRHH'), ('Tecnologia'), ('Ventas');

INSERT IGNORE INTO departamentos (nombre)
  SELECT DISTINCT departamento FROM usuarios WHERE departamento <> '';
