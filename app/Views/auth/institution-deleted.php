<?php
use App\Core\Security;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Institución eliminada · Biblioteca Virtual ST</title>
    <link rel="stylesheet" href="<?= Security::escape(Security::asset('assets/app.css')) ?>">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-panel">
        <div class="login-form-wrap">
            <p class="eyebrow">Cuenta institucional eliminada</p>
            <h1>La institución y sus datos fueron eliminados</h1>
            <p class="muted">La sesión se cerró. Esta acción eliminó permanentemente los usuarios, libros, ejemplares, préstamos, catálogos y la configuración de la institución.</p>
            <a class="button button--primary button--wide" href="<?= Security::escape(Security::url('setup')) ?>">Configurar de nuevo</a>
        </div>
    </section>
</main>
</body>
</html>
