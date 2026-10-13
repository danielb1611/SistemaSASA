<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function current_rol(): ?string
{
    return $_SESSION['rol'] ?? null;
}

const MODULOS = [
    'dashboard'   => 'Dashboard',
    'proveedores' => 'Proveedores',
    'marcas'      => 'Marcas',
    'insumos'     => 'Insumos',
    'precios'     => 'Precios',
    'compras'     => 'Compras',
    'comparativa' => 'Comparativa de precios',
    'reportes'    => 'Reportes',
];

function modulos_de_usuario(PDO $pdo, int $usuarioId, string $rol): array
{
    if ($rol === 'admin') {
        return array_keys(MODULOS);
    }

    $stmt = $pdo->prepare('SELECT modulo FROM usuario_modulos WHERE usuario_id = ?');
    $stmt->execute([$usuarioId]);
    $asignados = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return array_values(array_intersect(array_keys(MODULOS), $asignados));
}

function guardar_modulos_usuario(PDO $pdo, int $usuarioId, array $modulos): void
{
    $modulos = array_values(array_intersect(array_keys(MODULOS), $modulos));

    $pdo->prepare('DELETE FROM usuario_modulos WHERE usuario_id = ?')->execute([$usuarioId]);

    if ($modulos === []) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO usuario_modulos (usuario_id, modulo) VALUES (?, ?)');
    foreach ($modulos as $modulo) {
        $stmt->execute([$usuarioId, $modulo]);
    }
}

function puede_ver(string $modulo): bool
{
    if ($modulo === 'dashboard' || current_rol() === 'admin') {
        return true;
    }

    $modulos = $_SESSION['modulos'] ?? [];

    return in_array($modulo, $modulos, true);
}

function require_modulo(string $modulo): void
{
    require_login();

    if (!puede_ver($modulo)) {
        redirect('dashboard.php');
    }
}

function departamentos(PDO $pdo): array
{
    return $pdo->query('SELECT nombre FROM departamentos ORDER BY nombre')->fetchAll(PDO::FETCH_COLUMN);
}

const MENSAJES_POR_HORA = 10;

function contar_mensajes_sin_leer(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM mensajes WHERE leido = 0')->fetchColumn();
}

function contar_respuestas_nuevas(PDO $pdo, int $usuarioId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM mensajes WHERE usuario_id = ? AND respuesta IS NOT NULL AND respuesta_vista = 0');
    $stmt->execute([$usuarioId]);

    return (int) $stmt->fetchColumn();
}

function contar_buzon(PDO $pdo, int $usuarioId, ?string $rol): int
{
    if ($rol === 'admin') {
        return contar_mensajes_sin_leer($pdo);
    }

    return contar_respuestas_nuevas($pdo, $usuarioId);
}
