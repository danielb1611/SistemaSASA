<?php

declare(strict_types=1);

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$email = $_COOKIE['recordar_email'] ?? '';
$recordar = isset($_COOKIE['recordar_email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $recordar = !empty($_POST['recordar']);
    $token = $_POST['csrf_token'] ?? null;

    if (!csrf_validate($token)) {
        $errors[] = 'Sesion invalida. Recarga la pagina e intenta de nuevo.';
    }

    if ($email === '' || $password === '') {
            $errors[] = 'Completa el correo y la contrasena.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, nombre, email, password_hash, rol FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['modulos'] = modulos_de_usuario($pdo, (int) $usuario['id'], $usuario['rol']);

            if ($recordar) {
                setcookie('recordar_email', $email, [
                    'expires' => time() + 60 * 60 * 24 * 30,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            } else {
                setcookie('recordar_email', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            redirect('dashboard.php');
        }

        $errors[] = 'Correo o contrasena incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion · Gestion Insumos</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= e((string) @filemtime(__DIR__ . '/assets/css/style.css')) ?>">
</head>
<body>
    <main class="auth">
        <section class="card">
            <img src="LogoEmpresa.png?v=<?= e((string) @filemtime(__DIR__ . '/LogoEmpresa.png')) ?>" alt="Logo Empresa" class="auth__logo">

            <?php if ($errors): ?>
                <div class="alert alert--error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" class="form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="field">
                    <label for="email"><svg class="field__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg> Correo electronico</label>
                    <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
                </div>

                <div class="field">
                    <label for="password"><svg class="field__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Contraseña</label>
                    <div class="field__password">
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="field__toggle" aria-label="Ver contrasena" aria-pressed="false">
                            <svg class="field__toggle-icon field__toggle-icon--show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            <svg class="field__toggle-icon field__toggle-icon--hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><path d="m1 1 22 22"></path></svg>
                        </button>
                    </div>
                </div>

                <label class="remember">
                    <input type="checkbox" name="recordar" value="1" <?= $recordar ? 'checked' : '' ?>>
                    Recordar usuario
                </label>

                <button type="submit" class="btn btn--primary btn--icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>Entrar</button>
            </form>
        </section>
    </main>
    <script>
        const togglePassword = document.querySelector('#password').parentElement.querySelector('.field__toggle');
        const passwordInput = document.querySelector('#password');
        togglePassword.addEventListener('click', () => {
            const visible = passwordInput.type === 'text';
            passwordInput.type = visible ? 'password' : 'text';
            togglePassword.classList.toggle('is-visible', !visible);
            togglePassword.setAttribute('aria-pressed', String(!visible));
            togglePassword.setAttribute('aria-label', visible ? 'Ver contrasena' : 'Ocultar contrasena');
        });

        const loginForm = document.querySelector('.form');
        const loginBtn = loginForm.querySelector('.btn--primary');
        loginForm.addEventListener('submit', (event) => {
            event.preventDefault();
            loginBtn.disabled = true;
            loginBtn.classList.add('is-loading');
            loginBtn.innerHTML = '<span class="btn__spinner" aria-hidden="true"></span>Ingresando...';
            setTimeout(() => loginForm.submit(), 2000);
        });
    </script>
</body>
</html>
