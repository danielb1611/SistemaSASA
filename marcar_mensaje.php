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

$stmt = $pdo->prepare('SELECT id, rol FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$usuarioId]);
$actual = $stmt->fetch();

if (!$actual) {
    json_out(false, 'Sesion expirada. Recarga la pagina e intenta de nuevo.');
}

$esAdmin = $actual['rol'] === 'admin';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Metodo no permitido.');
}

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    json_out(false, 'Sesion invalida. Recarga la pagina e intenta de nuevo.');
}

$id = (int) ($_POST['id'] ?? 0);
$campo = (string) ($_POST['campo'] ?? '');
$valor = isset($_POST['valor']) ? (int) $_POST['valor'] : 1;
$valor = $valor === 0 ? 0 : 1;

if ($id <= 0) {
    json_out(false, 'Mensaje invalido.');
}

$stmt = $pdo->prepare('SELECT id, usuario_id FROM mensajes WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$mensaje = $stmt->fetch();

if (!$mensaje) {
    json_out(false, 'Mensaje invalido.');
}

$esDuenio = (int) $mensaje['usuario_id'] === $usuarioId;

if ($campo === 'leido') {
    if (!$esAdmin) {
        json_out(false, 'No tenes permisos para esta accion.');
    }

    if ($valor === 1) {
        $pdo->prepare('UPDATE mensajes SET leido = 1, leido_en = COALESCE(leido_en, NOW()) WHERE id = ?')->execute([$id]);
    } else {
        $pdo->prepare('UPDATE mensajes SET leido = 0, leido_en = NULL WHERE id = ?')->execute([$id]);
    }

    json_out(true, $valor === 1 ? 'Marcado como leido.' : 'Marcado como no leido.', $id);
}

if ($campo === 'respuesta_vista') {
    if (!$esDuenio) {
        json_out(false, 'No tenes permisos para esta accion.');
    }

    $pdo->prepare('UPDATE mensajes SET respuesta_vista = 1 WHERE id = ?')->execute([$id]);

    json_out(true, 'Respuesta vista.', $id);
}

if ($campo === 'archivado') {
    if (!$esAdmin && !$esDuenio) {
        json_out(false, 'No tenes permisos para esta accion.');
    }

    $pdo->prepare('UPDATE mensajes SET archivado = ? WHERE id = ?')->execute([$valor, $id]);

    json_out(true, $valor === 1 ? 'Mensaje archivado.' : 'Mensaje restaurado.', $id);
}

json_out(false, 'Accion invalida.');
