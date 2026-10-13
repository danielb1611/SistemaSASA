<?php

declare(strict_types=1);

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/functions.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(bool $ok, string $mensaje, ?int $id = null, ?string $nombre = null): void
{
    http_response_code($ok ? 200 : 400);
    echo json_encode(['ok' => $ok, 'mensaje' => $mensaje, 'id' => $id, 'nombre' => $nombre]);
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
if ($id <= 0) {
    json_out(false, 'Departamento invalido.');
}

$stmt = $pdo->prepare('SELECT nombre FROM departamentos WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$dep = $stmt->fetch();

if (!$dep) {
    json_out(false, 'Departamento invalido.');
}

$anterior = $dep['nombre'];
$nuevo = trim((string) ($_POST['nombre'] ?? ''));

$len = mb_strlen($nuevo);
if ($len < 2 || $len > 100) {
    json_out(false, 'El nombre del departamento debe tener entre 2 y 100 caracteres.');
}

$stmt = $pdo->prepare('SELECT id FROM departamentos WHERE nombre = ? AND id <> ? LIMIT 1');
$stmt->execute([$nuevo, $id]);

if ($stmt->fetch()) {
    json_out(false, 'Ya existe otro departamento con ese nombre.');
}

if ($anterior === $nuevo) {
    json_out(true, 'Sin cambios.', $id, $nuevo);
}

$stmt = $pdo->prepare('UPDATE departamentos SET nombre = ? WHERE id = ?');
$stmt->execute([$nuevo, $id]);

$stmt = $pdo->prepare('UPDATE usuarios SET departamento = ? WHERE departamento = ?');
$stmt->execute([$nuevo, $anterior]);

json_out(true, 'Departamento actualizado.', $id, $nuevo);