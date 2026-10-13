<?php

declare(strict_types=1);

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/functions.php';

header('Content-Type: application/json; charset=utf-8');

function json_out(bool $ok, string $mensaje, ?string $nombre = null, ?int $id = null): void
{
    http_response_code($ok ? 200 : 400);
    echo json_encode(['ok' => $ok, 'mensaje' => $mensaje, 'nombre' => $nombre, 'id' => $id]);
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

$nombre = trim((string) ($_POST['nombre'] ?? ''));

$len = mb_strlen($nombre);
if ($len < 2 || $len > 100) {
    json_out(false, 'El nombre del departamento debe tener entre 2 y 100 caracteres.');
}

$stmt = $pdo->prepare('SELECT id, nombre FROM departamentos WHERE nombre = ? LIMIT 1');
$stmt->execute([$nombre]);
$existente = $stmt->fetch();

if ($existente) {
    json_out(true, 'Ese departamento ya existe.', $existente['nombre'], (int) $existente['id']);
}

$stmt = $pdo->prepare('INSERT INTO departamentos (nombre) VALUES (?)');
$stmt->execute([$nombre]);

json_out(true, 'Departamento agregado.', $nombre, (int) $pdo->lastInsertId());
