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

CREATE TABLE IF NOT EXISTS mensajes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT           NOT NULL,
  asunto          VARCHAR(150)  NOT NULL DEFAULT '',
  cuerpo          TEXT          NOT NULL,
  leido           TINYINT(1)    NOT NULL DEFAULT 0,
  leido_en        TIMESTAMP     NULL,
  respuesta       TEXT          NULL,
  respondido_por  INT           NULL,
  respondido_en   TIMESTAMP     NULL,
  respuesta_vista TINYINT(1)    NOT NULL DEFAULT 0,
  archivado       TINYINT(1)    NOT NULL DEFAULT 0,
  creado_en       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mensajes_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_mensajes_admin
    FOREIGN KEY (respondido_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_mensajes_usuario (usuario_id),
  INDEX idx_mensajes_leido (leido),
  INDEX idx_mensajes_archivado (archivado),
  INDEX idx_mensajes_spam (usuario_id, creado_en)
);
