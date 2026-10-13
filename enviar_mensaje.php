<?php

declare(strict_types=1);

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/functions.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(bool $ok, string $mensaje, ?int $id = null): void
{
    http_response_code($ok ? 200 : 400);
    echo json_encode(['ok' => $ok, 'mensaje' => $mensaje, 'id' => $id]);
    exit;
}

if (!is_logged_in()) {
    json_out(false, 'Sesion expirada. Recarga la pagina e intenta de nuevo.');
}

$usuarioId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Metodo no permitido.');
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    json_out(false, 'Sesion invalida. Recarga la pagina e intenta de nuevo.');
}

$asunto = trim((string) ($_POST['asunto'] ?? ''));
$cuerpo = trim((string) ($_POST['cuerpo'] ?? ''));

$lenAsunto = mb_strlen($asunto);
if ($lenAsunto < 1 || $lenAsunto > 50) {
    json_out(false, 'El asunto debe tener entre 1 y 50 caracteres.');
}

$lenCuerpo = mb_strlen($cuerpo);
if ($lenCuerpo < 1 || $lenCuerpo > 100) {
    json_out(false, 'El mensaje debe tener entre 1 y 100 caracteres.');
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM mensajes WHERE usuario_id = ? AND creado_en > (NOW() - INTERVAL 1 HOUR)');
$stmt->execute([$usuarioId]);
$enviadosUltimaHora = (int) $stmt->fetchColumn();

if ($enviadosUltimaHora >= MENSAJES_POR_HORA) {
    json_out(false, 'Enviaste demasiados mensajes en la ultima hora. Intenta mas tarde.');
}

$stmt = $pdo->prepare('INSERT INTO mensajes (usuario_id, asunto, cuerpo) VALUES (?, ?, ?)');
$stmt->execute([$usuarioId, $asunto, $cuerpo]);

json_out(true, 'Mensaje enviado al administrador.', (int) $pdo->lastInsertId());
