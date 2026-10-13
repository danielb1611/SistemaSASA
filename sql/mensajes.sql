USE basedatossistema;

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
