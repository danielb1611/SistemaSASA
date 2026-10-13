<?php

declare(strict_types=1);

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$stmt = $pdo->prepare('SELECT id, rol FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$actual = $stmt->fetch();

if (!$actual || $actual['rol'] !== 'admin') {
    redirect('dashboard.php');
}

$rolesValidos = ['usuario', 'admin'];

$departamentos = departamentos($pdo);

$modulosSeleccionables = MODULOS;
unset($modulosSeleccionables['dashboard']);

$errors = [];
$success = null;
$abrirModal = false;
$abrirEditar = false;
$abrirDepartamentos = false;
$successEnDepartamentos = false;
$edId = 0;
$edNombre = '';
$edApellido = '';
$edDepartamento = '';
$edEmail = '';
$edRol = 'usuario';
$edMio = false;
$nuevosModulos = [];
$edModulos = [];

if (isset($_GET['updated'])) {
    $success = 'Usuario actualizado correctamente.';
}

$busqueda = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$busquedaDep = trim((string) ($_GET['dep_q'] ?? ''));

$nombre = '';
$apellido = '';
$departamento = '';
$email = '';
$rol = 'usuario';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!csrf_validate($token)) {
        $errors[] = 'Sesion invalida. Recarga la pagina e intenta de nuevo.';
    } elseif ($action === 'create') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $apellido = trim((string) ($_POST['apellido'] ?? ''));
        $departamento = trim((string) ($_POST['departamento'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $rol = (string) ($_POST['rol'] ?? 'usuario');
        $nuevosModulos = array_values(array_intersect(array_keys($modulosSeleccionables), (array) ($_POST['modulos'] ?? [])));

        $lenNombre = mb_strlen($nombre);
        if ($lenNombre < 2 || $lenNombre > 100) {
            $errors[] = 'El nombre debe tener entre 2 y 100 caracteres.';
        }

        $lenApellido = mb_strlen($apellido);
        if ($lenApellido < 2 || $lenApellido > 100) {
            $errors[] = 'El apellido debe tener entre 2 y 100 caracteres.';
        }

        if (!in_array($departamento, $departamentos, true)) {
            $errors[] = 'Selecciona un departamento.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors[] = 'Ingresa un correo electronico valido.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
        }

        if (!in_array($rol, $rolesValidos, true)) {
            $errors[] = 'Rol invalido.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errors[] = 'Ese correo ya esta registrado.';
            }
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, apellido, departamento, email, password_hash, rol) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$nombre, $apellido, $departamento, $email, $hash, $rol]);

            if ($rol !== 'admin') {
                guardar_modulos_usuario($pdo, (int) $pdo->lastInsertId(), $nuevosModulos);
            }

            $success = 'Usuario "' . $nombre . ' ' . $apellido . '" creado con rol ' . $rol . '.';
            $nombre = '';
            $apellido = '';
            $departamento = '';
            $email = '';
            $rol = 'usuario';
            $nuevosModulos = [];
        }

        $abrirModal = (bool) $errors;
    } elseif ($action === 'update') {
        $edId = (int) ($_POST['id'] ?? 0);
        $edNombre = trim((string) ($_POST['nombre'] ?? ''));
        $edApellido = trim((string) ($_POST['apellido'] ?? ''));
        $edDepartamento = trim((string) ($_POST['departamento'] ?? ''));
        $edEmail = trim((string) ($_POST['email'] ?? ''));
        $edRol = (string) ($_POST['rol'] ?? 'usuario');
        $password = (string) ($_POST['password'] ?? '');
        $edMio = $edId > 0 && $edId === (int) $actual['id'];
        $edModulos = array_values(array_intersect(array_keys($modulosSeleccionables), (array) ($_POST['modulos'] ?? [])));

        $abrirEditar = true;

        if ($edId <= 0) {
            $errors[] = 'Usuario invalido.';
        } else {
            $stmt = $pdo->prepare('SELECT id, rol FROM usuarios WHERE id = ? LIMIT 1');
            $stmt->execute([$edId]);
            $objetivo = $stmt->fetch();

            if (!$objetivo) {
                $errors[] = 'Usuario invalido.';
            }
        }

        $lenNombre = mb_strlen($edNombre);
        if ($lenNombre < 2 || $lenNombre > 100) {
            $errors[] = 'El nombre debe tener entre 2 y 100 caracteres.';
        }

        $lenApellido = mb_strlen($edApellido);
        if ($lenApellido < 2 || $lenApellido > 100) {
            $errors[] = 'El apellido debe tener entre 2 y 100 caracteres.';
        }

        if (!in_array($edDepartamento, $departamentos, true)) {
            $errors[] = 'Selecciona un departamento.';
        }

        if (!filter_var($edEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($edEmail) > 150) {
            $errors[] = 'Ingresa un correo electronico valido.';
        }

        if ($edMio) {
            $edRol = $objetivo['rol'] ?? 'usuario';
        } elseif (!in_array($edRol, $rolesValidos, true)) {
            $errors[] = 'Rol invalido.';
        }

        if ($password !== '' && strlen($password) < 8) {
            $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
        }

        if (!$errors && $objetivo) {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1');
            $stmt->execute([$edEmail, $edId]);

            if ($stmt->fetch()) {
                $errors[] = 'Ese correo ya esta registrado por otro usuario.';
            }
        }

        if (!$errors && $objetivo) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, apellido = ?, departamento = ?, email = ?, rol = ?, password_hash = ? WHERE id = ?');
                $stmt->execute([$edNombre, $edApellido, $edDepartamento, $edEmail, $edRol, $hash, $edId]);
            } else {
                $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, apellido = ?, departamento = ?, email = ?, rol = ? WHERE id = ?');
                $stmt->execute([$edNombre, $edApellido, $edDepartamento, $edEmail, $edRol, $edId]);
            }

            guardar_modulos_usuario($pdo, $edId, $edRol === 'admin' ? [] : $edModulos);

            if ($edMio) {
                $_SESSION['nombre'] = $edNombre;
            }

            $success = 'Usuario "' . $edNombre . ' ' . $edApellido . '" actualizado correctamente.';
            $abrirEditar = false;
        }
    } elseif ($action === 'update_rol') {
        $id = (int) ($_POST['id'] ?? 0);
        $nuevoRol = (string) ($_POST['rol'] ?? '');

        if ($id <= 0) {
            $errors[] = 'Usuario invalido.';
        } elseif (!in_array($nuevoRol, $rolesValidos, true)) {
            $errors[] = 'Rol invalido.';
        } elseif ($id === (int) $actual['id']) {
            $errors[] = 'No podes cambiar tu propio rol.';
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios SET rol = ? WHERE id = ?');
            $stmt->execute([$nuevoRol, $id]);

            if ($nuevoRol === 'admin') {
                guardar_modulos_usuario($pdo, $id, []);
            }

            $success = 'Rol actualizado correctamente.';
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $errors[] = 'Usuario invalido.';
        } elseif ($id === (int) $actual['id']) {
            $errors[] = 'No podes eliminar tu propia cuenta.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Usuario eliminado correctamente.';
        }
    } elseif ($action === 'delete_departamento') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $errors[] = 'Departamento invalido.';
        } else {
            $stmt = $pdo->prepare('SELECT nombre FROM departamentos WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $dep = $stmt->fetch();

            if (!$dep) {
                $errors[] = 'Departamento invalido.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE departamento = ?');
                $stmt->execute([$dep['nombre']]);
                $asignados = (int) $stmt->fetchColumn();

                if ($asignados > 0) {
                    $errors[] = 'No se puede eliminar "' . $dep['nombre'] . '": ' . $asignados . ' usuario(s) asignado(s).';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM departamentos WHERE id = ?');
                    $stmt->execute([$id]);
                    $success = 'Departamento "' . $dep['nombre'] . '" eliminado correctamente.';
            $abrirDepartamentos = true;
            $successEnDepartamentos = true;
                }
            }
        }
    }
}

if ($busquedaDep !== '' || ($_GET['dep'] ?? '') === '1') {
    $abrirDepartamentos = true;
}

$urlLimpiarDep = 'admin.php?dep=1' . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : '');

if ($busqueda !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busqueda) . '%';
    $stmt = $pdo->prepare('SELECT id, nombre, apellido, departamento, email, rol, creado_en FROM usuarios WHERE nombre LIKE ? OR apellido LIKE ? OR email LIKE ? ORDER BY creado_en DESC');
    $stmt->execute([$like, $like, $like]);
    $usuarios = $stmt->fetchAll();
} else {
    $stmt = $pdo->query('SELECT id, nombre, apellido, departamento, email, rol, creado_en FROM usuarios ORDER BY creado_en DESC');
    $usuarios = $stmt->fetchAll();
}

$modulosPorUsuario = [];
$filasModulos = $pdo->query('SELECT usuario_id, modulo FROM usuario_modulos')->fetchAll();
foreach ($filasModulos as $filaModulo) {
    $modulosPorUsuario[(int) $filaModulo['usuario_id']][] = $filaModulo['modulo'];
}

$conteoDepartamentos = array_fill_keys($departamentos, 0);
$filasConteo = $pdo->query("SELECT departamento, COUNT(*) AS total FROM usuarios WHERE departamento <> '' GROUP BY departamento")->fetchAll();
foreach ($filasConteo as $filaConteo) {
    if (array_key_exists($filaConteo['departamento'], $conteoDepartamentos)) {
        $conteoDepartamentos[$filaConteo['departamento']] = (int) $filaConteo['total'];
    }
}

$filasDepartamentos = [];
$depCtlSql = 'SELECT id, nombre FROM departamentos';
$depCtlParams = [];
if ($busquedaDep !== '') {
    $likeDep = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busquedaDep) . '%';
    $depCtlSql .= ' WHERE nombre LIKE ?';
    $depCtlParams[] = $likeDep;
}
$depCtlSql .= ' ORDER BY nombre';
$stmtDepCtl = $pdo->prepare($depCtlSql);
$stmtDepCtl->execute($depCtlParams);
$departamentosCtl = $stmtDepCtl->fetchAll();
foreach ($departamentosCtl as $depCtl) {
    $filasDepartamentos[] = [
        'id' => (int) $depCtl['id'],
        'nombre' => $depCtl['nombre'],
        'usuarios' => $conteoDepartamentos[$depCtl['nombre']] ?? 0,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios · Gestion Insumos</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= e((string) @filemtime(__DIR__ . '/assets/css/style.css')) ?>">
</head>
<body class="app">
    <?php include 'partials/header.php'; ?>

    <main class="dashboard dashboard--wide dashboard--usuarios">
        <section class="card card--wide">
            <div class="section-head">
                <div>
                    <h1 class="card__title card__title--icon"><svg class="card__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>Usuarios</h1>
                </div>
                <div class="section-head__tools">
                    <button type="button" class="btn btn--blue btn--icon" data-modal-open="modal-departamentos"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>Departamentos</button>
                    <button type="button" class="btn btn--blue btn--icon" data-modal-open="modal-nuevo"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>Usuario</button>
                </div>
            </div>

            <?php if ($success !== null && !$successEnDepartamentos): ?>
                <div class="alert alert--success" role="status"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if ($errors && !$abrirModal && !$abrirEditar): ?>
                <div class="alert alert--error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="get" action="admin.php" class="search-form search-form--table">
                <input type="search" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar por nombre, apellido o correo..." class="search-form__input" aria-label="Buscar usuarios">
                <button type="submit" class="btn btn--primary btn--sm btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>Buscar</button>
                <?php if ($busqueda !== ''): ?>
                    <a href="admin.php" class="btn btn--outline btn--sm btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if ($usuarios): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Departamento</th>
                            <th>Correo</th>
                            <th>Registro</th>
                            <th>Rol</th>
                            <th>Modulos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $fila): ?>
                            <tr>
                                <td><?= e($fila['nombre']) ?></td>
                                <td><?= e($fila['apellido']) ?></td>
                                <td class="dep-cell"><?= e($fila['departamento']) ?></td>
                                <td><?= e($fila['email']) ?></td>
                                <td><?= e($fila['creado_en']) ?></td>
                                <td>
                                    <?php if ((int) $fila['id'] === (int) $actual['id']): ?>
                                        <span class="badge badge--admin">vos (admin)</span>
                                    <?php else: ?>
                                        <form method="post" action="admin.php" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="update_rol">
                                            <input type="hidden" name="id" value="<?= e((string) $fila['id']) ?>">
                                            <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                                            <select name="rol" aria-label="Rol de <?= e($fila['nombre']) ?>">
                                                <option value="admin" <?= $fila['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                                <option value="usuario" <?= $fila['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                                            </select>
                                            <button type="submit" class="btn btn--primary btn--sm">Guardar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-modulos">
                                    <?php if ($fila['rol'] === 'admin'): ?>
                                        <span class="badge badge--admin">Todos</span>
                                    <?php else: ?>
                                        <?php $modsFila = $modulosPorUsuario[(int) $fila['id']] ?? []; ?>
                                        <?php if (!$modsFila): ?>
                                            <span class="muted">Solo Dashboard</span>
                                        <?php else: ?>
                                            <div class="tags">
                                                <?php foreach (array_intersect_key(MODULOS, array_flip($modsFila)) as $modLabel): ?>
                                                    <span class="tag"><?= e($modLabel) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-actions">
                                <button type="button" class="btn btn--outline btn--sm" data-modal-open="modal-editar" data-editar="<?= e(json_encode([
                                    'id' => (int) $fila['id'],
                                    'nombre' => $fila['nombre'],
                                    'apellido' => $fila['apellido'],
                                    'departamento' => $fila['departamento'],
                                    'email' => $fila['email'],
                                    'rol' => $fila['rol'],
                                    'mio' => (int) $fila['id'] === (int) $actual['id'],
                                    'modulos' => array_values($modulosPorUsuario[(int) $fila['id']] ?? []),
                                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">Editar</button>
                                    <?php if ((int) $fila['id'] !== (int) $actual['id']): ?>
                                        <button type="button" class="btn btn--danger btn--sm" data-modal-open="modal-eliminar" data-eliminar="<?= e(json_encode([
                                            'id' => (int) $fila['id'],
                                            'nombre' => $fila['nombre'] . ' ' . $fila['apellido'],
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">Eliminar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="table__empty">No se encontraron usuarios.</p>
        <?php endif; ?>
        </section>
    </main>

    <div class="modal" id="modal-nuevo" data-modal <?= $abrirModal ? 'data-open' : '' ?>>
        <div class="modal__backdrop"></div>
        <div class="modal__dialog modal__dialog--wide modal__dialog--scroll" role="dialog" aria-modal="true" aria-labelledby="modal-nuevo-titulo">
            <header class="modal__header">
                <h2 class="card__title card__title--icon" id="modal-nuevo-titulo"><svg class="card__title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="22" y1="11" x2="16" y2="11"></line></svg>Nuevo usuario</h2>
                <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">&times;</button>
            </header>

            <?php if ($abrirModal && $errors): ?>
                <div class="alert alert--error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="admin.php" class="form form--grid" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="q" value="<?= e($busqueda) ?>">

                <div class="field">
                    <label for="nuevo-nombre">Nombre</label>
                    <input type="text" id="nuevo-nombre" name="nombre" value="<?= e($nombre) ?>" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="nuevo-apellido">Apellido</label>
                    <input type="text" id="nuevo-apellido" name="apellido" value="<?= e($apellido) ?>" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="nuevo-departamento">Departamento</label>
                    <select id="nuevo-departamento" name="departamento" required>
                        <option value="" disabled <?= $departamento === '' ? 'selected' : '' ?>>Selecciona un departamento</option>
                        <?php foreach ($departamentos as $dep): ?>
                            <option value="<?= e($dep) ?>" <?= $departamento === $dep ? 'selected' : '' ?>><?= e($dep) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="nuevo-email">Correo electronico</label>
                    <input type="email" id="nuevo-email" name="email" value="<?= e($email) ?>" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="nuevo-password">Contraseña</label>
                    <div class="field__password">
                        <input type="password" id="nuevo-password" name="password" autocomplete="new-password" required>
                        <button type="button" class="field__toggle" aria-label="Ver contrasena" aria-pressed="false">
                            <svg class="field__toggle-icon field__toggle-icon--show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="field__toggle-icon field__toggle-icon--hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><path d="m1 1 22 22"></path></svg>
                        </button>
                    </div>
                    <small class="field__hint">Minimo 8 caracteres.</small>
                </div>

                <div class="field">
                    <label for="nuevo-rol">Rol</label>
                    <select id="nuevo-rol" name="rol">
                        <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        <option value="usuario" <?= $rol === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                    </select>
                </div>

                <div class="field field--full">
                    <span class="field__label">Modulos permitidos</span>
                    <small class="field__hint">El Dashboard siempre esta disponible. Un Administrador accede a todos los modulos.</small>
                    <div class="checks" id="nuevo-modulos">
                        <?php foreach ($modulosSeleccionables as $moduloClave => $moduloLabel): ?>
                            <label class="checks__item">
                                <input type="checkbox" name="modulos[]" value="<?= e($moduloClave) ?>" <?= in_array($moduloClave, $nuevosModulos, true) ? 'checked' : '' ?>>
                                <?= e($moduloLabel) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form__actions">
                    <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn btn--primary">Crear usuario</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="modal-editar" data-modal <?= $abrirEditar ? 'data-open' : '' ?>>
        <div class="modal__backdrop"></div>
        <div class="modal__dialog modal__dialog--wide modal__dialog--scroll" role="dialog" aria-modal="true" aria-labelledby="modal-editar-titulo">
            <header class="modal__header">
                <h2 class="card__title card__title--icon" id="modal-editar-titulo"><svg class="card__title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"></path></svg>Editar usuario</h2>
                <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">&times;</button>
            </header>

            <?php if ($abrirEditar && $errors): ?>
                <div class="alert alert--error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="admin.php" class="form form--grid" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editar-id" value="<?= e((string) $edId) ?>">
                <input type="hidden" name="q" value="<?= e($busqueda) ?>">

                <div class="field">
                    <label for="editar-nombre">Nombre</label>
                    <input type="text" id="editar-nombre" name="nombre" value="<?= e($edNombre) ?>" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="editar-apellido">Apellido</label>
                    <input type="text" id="editar-apellido" name="apellido" value="<?= e($edApellido) ?>" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="editar-departamento">Departamento</label>
                    <select id="editar-departamento" name="departamento" required>
                        <option value="" disabled <?= $edDepartamento === '' ? 'selected' : '' ?>>Selecciona un departamento</option>
                        <?php foreach ($departamentos as $dep): ?>
                            <option value="<?= e($dep) ?>" <?= $edDepartamento !== '' && $edDepartamento === $dep ? 'selected' : '' ?>><?= e($dep) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="editar-email">Correo electronico</label>
                    <input type="email" id="editar-email" name="email" value="<?= e($edEmail) ?>" autocomplete="off" required>
                </div>

                <div class="field">
                    <label for="editar-password">Contraseña</label>
                    <div class="field__password">
                        <input type="password" id="editar-password" name="password" autocomplete="new-password">
                        <button type="button" class="field__toggle" aria-label="Ver contrasena" aria-pressed="false">
                            <svg class="field__toggle-icon field__toggle-icon--show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="field__toggle-icon field__toggle-icon--hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><path d="m1 1 22 22"></path></svg>
                        </button>
                    </div>
                    <small class="field__hint">Dejala en blanco para no cambiarla.</small>
                </div>

                <div class="field">
                    <label for="editar-rol">Rol</label>
                    <select id="editar-rol" name="rol" <?= $edMio ? 'disabled' : '' ?>>
                        <option value="admin" <?= $edRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        <option value="usuario" <?= $edRol === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                    </select>
                    <small class="field__hint" id="editar-rol-hint" style="<?= $edMio ? '' : 'display:none;' ?>">No podes cambiar tu propio rol.</small>
                </div>

                <div class="field field--full">
                    <span class="field__label">Modulos permitidos</span>
                    <small class="field__hint">El Dashboard siempre esta disponible. Un Administrador accede a todos los modulos.</small>
                    <div class="checks" id="editar-modulos">
                        <?php foreach ($modulosSeleccionables as $moduloClave => $moduloLabel): ?>
                            <label class="checks__item">
                                <input type="checkbox" name="modulos[]" value="<?= e($moduloClave) ?>" data-modulo="<?= e($moduloClave) ?>" <?= in_array($moduloClave, $edModulos, true) ? 'checked' : '' ?> <?= $edMio ? 'disabled' : '' ?>>
                                <?= e($moduloLabel) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form__actions">
                    <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
                    <button type="submit" class="btn btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="modal-eliminar" data-modal>
        <div class="modal__backdrop"></div>
        <div class="modal__dialog modal__dialog--sm" role="dialog" aria-modal="true" aria-labelledby="modal-eliminar-titulo">
            <header class="modal__header">
                <h2 class="card__title card__title--icon" id="modal-eliminar-titulo"><svg class="card__title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="17" y1="8" x2="22" y2="13"></line><line x1="22" y1="8" x2="17" y2="13"></line></svg>Eliminar usuario</h2>
            </header>

            <p class="modal__text">Seguro que queres eliminar a <strong id="eliminar-nombre"></strong>? Esta accion no se puede deshacer.</p>

            <form method="post" action="admin.php" class="form__actions">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="eliminar-id" value="">
                <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn--danger">Si, eliminar</button>
            </form>
        </div>
    </div>

    <div class="modal" id="modal-departamentos" data-modal <?= $abrirDepartamentos ? 'data-open' : '' ?>>
        <div class="modal__backdrop"></div>
        <div class="modal__dialog modal__dialog--wide modal__dialog--scroll modal__dialog--fill" role="dialog" aria-modal="true" aria-labelledby="modal-departamentos-titulo">
            <header class="modal__header">
                <h2 class="card__title card__title--icon" id="modal-departamentos-titulo"><svg class="card__title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>Departamentos</h2>
                <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">&times;</button>
            </header>

            <?php if ($abrirDepartamentos && $success !== null): ?>
                <div class="alert alert--success" role="status"><?= e($success) ?></div>
            <?php endif; ?>

            <div class="table-scroll">
                <p class="field__hint" id="dep-tabla-aviso">Los departamentos con usuarios asignados no se pueden eliminar.</p>
                <form method="get" action="admin.php" class="search-form search-form--table">
                    <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                    <input type="search" id="dep-buscar" name="dep_q" value="<?= e($busquedaDep) ?>" class="search-form__input" placeholder="Buscar departamento..." aria-label="Buscar departamento">
                    <button type="submit" class="btn btn--primary btn--sm btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>Buscar</button>
                    <?php if ($busquedaDep !== ''): ?>
                        <a href="<?= e($urlLimpiarDep) ?>" class="btn btn--outline btn--sm btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>Limpiar</a>
                    <?php endif; ?>
                </form>
                <?php if (!$filasDepartamentos && $busquedaDep !== ''): ?>
                    <p class="table__empty">No se encontraron departamentos.</p>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Departamento</th>
                            <th>Usuarios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                <tbody id="dep-tabla-body">
                    <?php if (!$filasDepartamentos): ?>
                        <tr><td colspan="3" class="table__empty">No hay departamentos cargados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($filasDepartamentos as $filaDep): ?>
                            <tr>
                                <td class="dep-name"><?= e($filaDep['nombre']) ?></td>
                                <td>
                                    <?php if ($filaDep['usuarios'] > 0): ?>
                                        <span class="badge badge--admin"><?= $filaDep['usuarios'] ?> asignado(s)</span>
                                    <?php else: ?>
                                        <span class="muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-actions">
                                    <button type="button" class="btn btn--primary btn--sm" data-modal-open="modal-agregar-departamento" data-dep-editar="<?= e(json_encode([
                                        'id' => $filaDep['id'],
                                        'nombre' => $filaDep['nombre'],
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">Editar</button>
                                    <?php if ($filaDep['usuarios'] > 0): ?>
                                        <button type="button" class="btn btn--danger btn--sm" disabled title="Tiene usuarios asignados">Eliminar</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn--danger btn--sm" data-modal-open="modal-eliminar-departamento" data-dep-eliminar="<?= e(json_encode([
                                            'id' => $filaDep['id'],
                                            'nombre' => $filaDep['nombre'],
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">Eliminar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
                <?php endif; ?>
        </div>

            <div class="form__actions dep-actions">
                <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
                <button type="button" class="btn btn--primary" data-modal-open="modal-agregar-departamento">Nuevo departamento</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-agregar-departamento" data-modal>
        <div class="modal__backdrop"></div>
        <div class="modal__dialog modal__dialog--sm" role="dialog" aria-modal="true" aria-labelledby="modal-agregar-departamento-titulo">
            <header class="modal__header">
                <h2 class="card__title card__title--icon" id="modal-agregar-departamento-titulo"><svg class="card__title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg><span id="dep-modal-nombre">Agregar departamento</span></h2>
            </header>

            <form action="admin.php" method="post" class="form">
                <input type="hidden" id="dep-csrf" value="<?= e(csrf_token()) ?>">
                <div class="field">
                    <label for="dep-nuevo-nombre">Nombre del departamento</label>
                    <input type="text" id="dep-nuevo-nombre" autocomplete="off">
                </div>
                <small class="field__hint" id="dep-nuevo-msg" hidden></small>
                <div class="form__actions">
                    <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
                    <button type="button" class="btn btn--primary" id="dep-nuevo-btn">Agregar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="modal-eliminar-departamento" data-modal>
        <div class="modal__backdrop"></div>
        <div class="modal__dialog modal__dialog--sm" role="dialog" aria-modal="true" aria-labelledby="modal-eliminar-departamento-titulo">
            <header class="modal__header">
                <h2 class="card__title card__title--icon" id="modal-eliminar-departamento-titulo"><svg class="card__title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"></path><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"></path><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"></path><path d="M10 6h4"></path><path d="M10 10h4"></path><path d="M10 14h4"></path><path d="M10 18h4"></path></svg>Eliminar departamento</h2>
            </header>

            <p class="modal__text">Seguro que queres eliminar el departamento <strong id="dep-eliminar-nombre"></strong>? Esta accion no se puede deshacer.</p>

            <form method="post" action="admin.php" class="form__actions">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete_departamento">
                <input type="hidden" name="id" id="dep-eliminar-id" value="">
                <input type="hidden" name="q" value="<?= e($busqueda) ?>">
                <button type="button" class="btn btn--outline" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn--danger">Si, eliminar</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-modal-open]');
            if (!trigger) {
                return;
            }
            var modal = document.getElementById(trigger.getAttribute('data-modal-open'));
            if (!modal) {
                return;
            }
            var alertError = modal.querySelector('.alert--error');
            if (alertError) {
                alertError.remove();
            }
            modal.classList.add('is-open');

            if (trigger.getAttribute('data-modal-open') === 'modal-eliminar-departamento') {
                var d = JSON.parse(trigger.getAttribute('data-dep-eliminar'));
                document.getElementById('dep-eliminar-id').value = d.id;
                document.getElementById('dep-eliminar-nombre').textContent = d.nombre;
            }

            if (trigger.getAttribute('data-modal-open') === 'modal-agregar-departamento') {
                var editarDep = trigger.getAttribute('data-dep-editar');
                abrirModalDep(editarDep ? JSON.parse(editarDep) : null, trigger.closest('tr'));
                depInp.focus();
            }
        });

        var nuevoRol = document.getElementById('nuevo-rol');

        function syncNuevoModulos() {
            if (!nuevoRol) {
                return;
            }
            var esAdmin = nuevoRol.value === 'admin';
            document.querySelectorAll('#nuevo-modulos input').forEach(function (cb) {
                cb.disabled = esAdmin;
            });
        }

        if (nuevoRol) {
            nuevoRol.addEventListener('change', syncNuevoModulos);
            syncNuevoModulos();
        }

        var depInp = document.getElementById('dep-nuevo-nombre');
        var depBtnAdd = document.getElementById('dep-nuevo-btn');
        var depMsg = document.getElementById('dep-nuevo-msg');
        var depTbody = document.getElementById('dep-tabla-body');
        var depTituloTxt = document.getElementById('dep-modal-nombre');
        var depEditando = null;

        function mostrarDepMsg(texto, esError) {
            if (!depMsg) {
                return;
            }
            depMsg.textContent = texto;
            depMsg.style.color = esError ? '#b91c1c' : '';
            depMsg.hidden = false;
        }

        function abrirModalDep(editando, fila) {
            depEditando = editando ? { id: editando.id, nombre: editando.nombre, fila: fila } : null;
            if (depEditando) {
                depTituloTxt.textContent = 'Editar departamento';
                depBtnAdd.textContent = 'Guardar';
                depInp.value = depEditando.nombre;
            } else {
                depTituloTxt.textContent = 'Agregar departamento';
                depBtnAdd.textContent = 'Agregar';
                depInp.value = '';
            }
            depMsg.hidden = true;
        }

        function crearFilaDepartamento(d) {
            var tr = document.createElement('tr');

            var tdNom = document.createElement('td');
            tdNom.className = 'dep-name';
            tdNom.textContent = d.nombre;

            var tdUsu = document.createElement('td');
            var spanCero = document.createElement('span');
            spanCero.className = 'muted';
            spanCero.textContent = '0';
            tdUsu.appendChild(spanCero);

            var tdAcc = document.createElement('td');
            tdAcc.className = 'cell-actions';
            var btnEdit = document.createElement('button');
            btnEdit.type = 'button';
            btnEdit.className = 'btn btn--primary btn--sm';
            btnEdit.setAttribute('data-modal-open', 'modal-agregar-departamento');
            btnEdit.setAttribute('data-dep-editar', JSON.stringify({ id: d.id, nombre: d.nombre }));
            btnEdit.textContent = 'Editar';
            var btnDel = document.createElement('button');
            btnDel.type = 'button';
            btnDel.className = 'btn btn--danger btn--sm';
            btnDel.setAttribute('data-modal-open', 'modal-eliminar-departamento');
            btnDel.setAttribute('data-dep-eliminar', JSON.stringify({ id: d.id, nombre: d.nombre }));
            btnDel.textContent = 'Eliminar';
            tdAcc.appendChild(btnEdit);
            tdAcc.appendChild(btnDel);

            tr.appendChild(tdNom);
            tr.appendChild(tdUsu);
            tr.appendChild(tdAcc);
            return tr;
        }

        function renombrarEnUsers(viejo, nuevo) {
            document.querySelectorAll('.dep-cell').forEach(function (td) {
                if (td.textContent === viejo) {
                    td.textContent = nuevo;
                }
            });
            document.querySelectorAll('[data-editar]').forEach(function (btn) {
                var d = JSON.parse(btn.getAttribute('data-editar'));
                if (d.departamento === viejo) {
                    d.departamento = nuevo;
                    btn.setAttribute('data-editar', JSON.stringify(d));
                }
            });
        }

        function syncSelectsDepartamento(nombre) {
            ['nuevo-departamento', 'editar-departamento'].forEach(function (selId) {
                var sel = document.getElementById(selId);
                if (!sel) {
                    return;
                }
                var existe = Array.prototype.some.call(sel.options, function (op) {
                    return op.value === nombre;
                });
                if (!existe) {
                    var opNueva = document.createElement('option');
                    opNueva.value = nombre;
                    opNueva.textContent = nombre;
                    sel.appendChild(opNueva);
                }
            });
        }

        function renombrarEnSelects(viejo, nuevo) {
            ['nuevo-departamento', 'editar-departamento'].forEach(function (selId) {
                var sel = document.getElementById(selId);
                if (!sel) {
                    return;
                }
                var encontrada = false;
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].value === viejo) {
                        sel.options[i].value = nuevo;
                        sel.options[i].textContent = nuevo;
                        encontrada = true;
                        break;
                    }
                }
                if (!encontrada) {
                    var opNueva = document.createElement('option');
                    opNueva.value = nuevo;
                    opNueva.textContent = nuevo;
                    sel.appendChild(opNueva);
                }
            });
        }

        function agregarDepartamento() {
            var nombre = depInp.value.trim();
            if (nombre.length < 2) {
                mostrarDepMsg('Ingresa un nombre de al menos 2 caracteres.', true);
                depInp.focus();
                return;
            }

            var esEdicion = depEditando !== null;
            var url = esEdicion ? 'editar_departamento.php' : 'agregar_departamento.php';

            var tokenInput = document.getElementById('dep-csrf');
            var body = new URLSearchParams();
            body.append('csrf_token', tokenInput ? tokenInput.value : '');
            body.append('nombre', nombre);
            if (esEdicion) {
                body.append('id', String(depEditando.id));
            }

            depBtnAdd.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    depBtnAdd.disabled = false;

                    if (!data.ok) {
                        mostrarDepMsg(data.mensaje || 'No se pudo guardar.', true);
                        return;
                    }

                    if (esEdicion) {
                        var nombreCelda = depEditando.fila ? depEditando.fila.querySelector('.dep-name') : null;
                        if (nombreCelda) {
                            nombreCelda.textContent = data.nombre;
                        }
                        if (depEditando.fila) {
                            var btnEditarRow = depEditando.fila.querySelector('[data-dep-editar]');
                            if (btnEditarRow) {
                                btnEditarRow.setAttribute('data-dep-editar', JSON.stringify({ id: depEditando.id, nombre: data.nombre }));
                            }
                            var btnEliminarRow = depEditando.fila.querySelector('[data-dep-eliminar]');
                            if (btnEliminarRow) {
                                btnEliminarRow.setAttribute('data-dep-eliminar', JSON.stringify({ id: depEditando.id, nombre: data.nombre }));
                            }
                        }
                        renombrarEnSelects(depEditando.nombre, data.nombre);
                        renombrarEnUsers(depEditando.nombre, data.nombre);
                    } else {
                        var yaEnTabla = Array.prototype.some.call(depTbody.querySelectorAll('.dep-name'), function (td) {
                            return td.textContent === data.nombre;
                        });
                        if (!yaEnTabla) {
                            var vacio = depTbody.querySelector('.table__empty');
                            if (vacio) {
                                vacio.parentElement.remove();
                            }
                            depTbody.appendChild(crearFilaDepartamento(data));
                        }
                        syncSelectsDepartamento(data.nombre);
                    }

                    depInp.value = '';
                    depMsg.hidden = true;
                    depEditando = null;
                    var modalDepNuevo = document.getElementById('modal-agregar-departamento');
                    if (modalDepNuevo) {
                        modalDepNuevo.classList.remove('is-open');
                    }
                })
                .catch(function () {
                    depBtnAdd.disabled = false;
                    mostrarDepMsg('Error de conexion. Intenta de nuevo.', true);
                });
        }

        if (depBtnAdd) {
            depBtnAdd.addEventListener('click', agregarDepartamento);
        }

        if (depInp) {
            depInp.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    agregarDepartamento();
                }
            });
        }

        document.querySelectorAll('[data-modal-open="modal-editar"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var d = JSON.parse(btn.getAttribute('data-editar'));

                document.getElementById('editar-id').value = d.id;
                document.getElementById('editar-nombre').value = d.nombre;
                document.getElementById('editar-apellido').value = d.apellido;
                document.getElementById('editar-email').value = d.email;
                document.getElementById('editar-password').value = '';

                var selDep = document.getElementById('editar-departamento');
                var tieneOpcion = Array.prototype.some.call(selDep.options, function (op) {
                    return op.value === d.departamento;
                });
                if (d.departamento !== '' && !tieneOpcion) {
                    var opNueva = document.createElement('option');
                    opNueva.value = d.departamento;
                    opNueva.textContent = d.departamento;
                    selDep.appendChild(opNueva);
                }
                selDep.value = d.departamento;

                var inpRol = document.getElementById('editar-rol');
                inpRol.disabled = d.mio;
                inpRol.value = d.rol;
                document.getElementById('editar-rol-hint').style.display = d.mio ? '' : 'none';

                var esAdminTarget = d.rol === 'admin';
                document.querySelectorAll('#editar-modulos input[data-modulo]').forEach(function (cb) {
                    cb.checked = !esAdminTarget && Array.isArray(d.modulos) && d.modulos.indexOf(cb.value) !== -1;
                });
                syncEditarModulos();
            });
        });

        var editarRol = document.getElementById('editar-rol');

        function syncEditarModulos() {
            if (!editarRol) {
                return;
            }
            var esAdmin = editarRol.value === 'admin';
            document.querySelectorAll('#editar-modulos input[data-modulo]').forEach(function (cb) {
                cb.disabled = esAdmin;
            });
        }

        if (editarRol) {
            editarRol.addEventListener('change', syncEditarModulos);
        }

        document.querySelectorAll('[data-modal-open="modal-eliminar"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var d = JSON.parse(btn.getAttribute('data-eliminar'));
                document.getElementById('eliminar-id').value = d.id;
                document.getElementById('eliminar-nombre').textContent = d.nombre;
            });
        });

        document.querySelectorAll('[data-modal-open="modal-eliminar"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var d = JSON.parse(btn.getAttribute('data-eliminar'));
                document.getElementById('eliminar-id').value = d.id;
                document.getElementById('eliminar-nombre').textContent = d.nombre;
            });
        });

        document.querySelectorAll('[data-modal]').forEach(function (modal) {
            modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
                el.addEventListener('click', function () {
                    modal.classList.remove('is-open');
                    modal.removeAttribute('data-open');
                });
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('[data-modal].is-open').forEach(function (modal) {
                    modal.classList.remove('is-open');
                });
            }
        });

        document.querySelectorAll('.field__password').forEach(function (wrap) {
            var input = wrap.querySelector('input');
            var toggle = wrap.querySelector('.field__toggle');
            toggle.addEventListener('click', function () {
                var visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                toggle.classList.toggle('is-visible', !visible);
                toggle.setAttribute('aria-pressed', String(!visible));
                toggle.setAttribute('aria-label', visible ? 'Ver contrasena' : 'Ocultar contrasena');
            });
        });

        document.querySelectorAll('.search-form--table').forEach(function (buscarForm) {
            var buscarBtn = buscarForm.querySelector('button[type="submit"]');
            var buscarInput = buscarForm.querySelector('input[type="search"]');
            if (!buscarBtn) {
                return;
            }

            if (buscarInput) {
                var actualizarBotonBuscar = function () {
                    buscarBtn.disabled = buscarInput.value.trim().length < 1;
                };
                buscarInput.addEventListener('input', actualizarBotonBuscar);
                actualizarBotonBuscar();
            }

            buscarForm.addEventListener('submit', function (event) {
                event.preventDefault();
                if (buscarBtn.classList.contains('is-loading')) {
                    return;
                }
                buscarBtn.classList.add('is-loading');
                buscarBtn.innerHTML = '<span class="btn__spinner" aria-hidden="true"></span>Buscando...';
                setTimeout(function () {
                    buscarForm.submit();
                }, 1000);
            });
        });

        if (new URLSearchParams(window.location.search).get('dep') === '1') {
            var urlDep = new URL(window.location.href);
            urlDep.searchParams.delete('dep');
            window.history.replaceState(null, '', urlDep.pathname + (urlDep.search ? urlDep.search : ''));
        }

        var successAlert = document.querySelector('.alert--success');
        if (successAlert) {
            setTimeout(function () {
                successAlert.style.opacity = '0';
                setTimeout(function () {
                    successAlert.remove();
                }, 300);
            }, 5000);
        }
    </script>
</body>
</html>
