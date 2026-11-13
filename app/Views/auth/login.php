<?php
use App\Core\Security;

$flash = $_SESSION['_flash'] ?? null;
unset($_SESSION['_flash']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Iniciar sesión · Biblioteca Virtual ST</title>
    <link rel="stylesheet" href="<?= Security::escape(Security::asset('assets/app.css')) ?>">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-aside" aria-label="Biblioteca Virtual ST">
        <a class="brand brand--light" href="<?= Security::escape(Security::url('login')) ?>">
            <span class="brand__seal" aria-hidden="true">ST</span>
            <span class="brand__text"><strong>Biblioteca</strong><small>Virtual ST</small></span>
        </a>
        <div class="login-aside__message">
            <span class="eyebrow">Colección institucional</span>
            <h1>Un lugar para cada libro. Un registro para cada préstamo.</h1>
            <p>Administra el catálogo físico y acompaña cada recurso a lo largo de su circulación.</p>
        </div>
        <div class="shelf-lines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
    </section>
    <section class="login-panel">
        <div class="login-form-wrap">
            <p class="eyebrow">Acceso institucional</p>
            <h2>Iniciar sesión</h2>
            <p class="muted">Ingresa con la cuenta asignada por tu institución.</p>
            <?php if (is_array($flash)): ?>
                <div class="notice notice--<?= Security::escape($flash['type'] ?? 'info') ?>" role="alert">
                    <?= Security::escape($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= Security::escape(Security::url('login')) ?>" class="form-stack">
                <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                <label for="correo">Correo institucional</label>
                <input id="correo" name="correo" type="email" autocomplete="username" maxlength="150" required>
                <label for="contrasena">Contraseña</label>
                <input id="contrasena" name="contrasena" type="password" autocomplete="current-password" required>
                <button class="button button--primary button--wide" type="submit">Entrar a la biblioteca</button>
            </form>
            <p class="login-note">Aplicación local de escritorio. Solicita al administrador de este equipo la configuración de acceso.</p>
        </div>
        <span class="login-panel__footer">Biblioteca Virtual ST</span>
    </section>
</main>
</body>
</html>
