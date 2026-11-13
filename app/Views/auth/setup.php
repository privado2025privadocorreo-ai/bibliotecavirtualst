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
    <title>Configuración inicial · Biblioteca Virtual ST</title>
    <link rel="stylesheet" href="<?= Security::escape(Security::asset('assets/app.css')) ?>">
</head>
<body class="login-page register-page">
<main class="login-shell">
    <section class="login-aside" aria-label="Biblioteca Virtual ST">
        <a class="brand brand--light" href="<?= Security::escape(Security::url('login')) ?>">
            <span class="brand__seal" aria-hidden="true">ST</span>
            <span class="brand__text"><strong>Biblioteca</strong><small>Virtual ST</small></span>
        </a>
        <div class="login-aside__message">
            <span class="eyebrow">Biblioteca Virtual ST</span>
            <h1>Tu biblioteca, en este equipo.</h1>
            <p>Configura una sola vez la institución local y su cuenta administradora.</p>
        </div>
        <div class="shelf-lines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div>
    </section>
    <section class="login-panel">
        <div class="login-form-wrap">
            <p class="eyebrow">Configuración local · primer inicio</p>
            <h2>Configura la biblioteca</h2>
            <p class="muted">Este asistente solo está disponible en este equipo y desaparece al completar la configuración.</p>
            <?php if (is_array($flash)): ?>
                <div class="notice notice--<?= Security::escape($flash['type'] ?? 'info') ?>" role="alert">
                    <?= Security::escape($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>
            <form method="post" action="<?= Security::escape(Security::url('setup')) ?>" class="form-stack">
                <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                <fieldset class="register-group">
                    <legend>Datos de la institución</legend>
                    <label>
                        <span>Nombre de la institución</span>
                        <input name="institution_name" type="text" maxlength="150" autocomplete="organization" required>
                    </label>
                    <label>
                        <span>Correo institucional</span>
                        <input name="institution_email" type="email" maxlength="150" autocomplete="email" required>
                    </label>
                    <label>
                        <span>Dirección <small>(opcional)</small></span>
                        <input name="institution_address" type="text" maxlength="255" autocomplete="street-address">
                    </label>
                </fieldset>
                <fieldset class="register-group">
                    <legend>Cuenta administradora</legend>
                    <div class="form-grid">
                        <label>
                            <span>Nombre</span>
                            <input name="admin_first_name" type="text" maxlength="100" autocomplete="given-name" required>
                        </label>
                        <label>
                            <span>Apellidos</span>
                            <input name="admin_last_name" type="text" maxlength="100" autocomplete="family-name" required>
                        </label>
                        <label class="field--wide">
                            <span>Correo de acceso</span>
                            <input name="admin_email" type="email" maxlength="150" autocomplete="username" required>
                        </label>
                        <label>
                            <span>Contraseña <small>(mínimo 8 caracteres)</small></span>
                            <input name="password" type="password" minlength="8" autocomplete="new-password" required>
                        </label>
                        <label>
                            <span>Confirmar contraseña</span>
                            <input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                        </label>
                    </div>
                </fieldset>
                <button class="button button--primary button--wide" type="submit">Guardar configuración local</button>
            </form>
        </div>
        <span class="login-panel__footer">Biblioteca Virtual ST</span>
    </section>
</main>
</body>
</html>
