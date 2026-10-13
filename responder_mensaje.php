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

$stmt = $pdo->prepare('SELECT id, rol FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$actual = $stmt->fetch();

if (!$actual || $actual['rol'] !== 'admin') {
    json_out(false, 'No tenes permisos para esta accion.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Metodo no permitido.');
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    json_out(false, 'Sesion invalida. Recarga la pagina e intenta de nuevo.');
}

$id = (int) ($_POST['id'] ?? 0);
$respuesta = trim((string) ($_POST['respuesta'] ?? ''));

if ($id <= 0) {
    json_out(false, 'Mensaje invalido.');
}

$lenRespuesta = mb_strlen($respuesta);
if ($lenRespuesta < 1 || $lenRespuesta > 2000) {
    json_out(false, 'La respuesta debe tener entre 1 y 2000 caracteres.');
}

$stmt = $pdo->prepare('SELECT id FROM mensajes WHERE id = ? LIMIT 1');
$stmt->execute([$id]);

if (!$stmt->fetch()) {
    json_out(false, 'Mensaje invalido.');
}

$stmt = $pdo->prepare('UPDATE mensajes SET respuesta = ?, respondido_por = ?, respondido_en = NOW(), leido = 1, leido_en = COALESCE(leido_en, NOW()), respuesta_vista = 0 WHERE id = ?');
$stmt->execute([$respuesta, (int) $actual['id'], $id]);

json_out(true, 'Respuesta enviada.', $id);
