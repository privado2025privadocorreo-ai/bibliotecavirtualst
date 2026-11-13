<?php
use App\Core\Auth;
use App\Core\Security;

$flash = $_SESSION['_flash'] ?? null;
unset($_SESSION['_flash']);
$currentUser = Auth::user();
$role = (string)($currentUser['rol'] ?? '');
$navigation = [
    ['route' => 'dashboard', 'label' => 'Inicio', 'mark' => '⌂'],
    ['route' => 'loans', 'label' => 'En préstamo', 'mark' => '↗'],
    ['route' => 'books', 'label' => 'Libros', 'mark' => '▤'],
    ['route' => 'patrons', 'label' => 'Usuarios', 'mark' => '○'],
    ['route' => 'about', 'label' => 'Acerca de', 'mark' => 'i'],
];
if ($role === 'Administrador') {
    $navigation[] = ['route' => 'settings', 'label' => 'Configuración', 'mark' => '⚙'];
}
$navigation[] = ['route' => 'account/password', 'label' => 'Cambiar contraseña', 'mark' => '⌁'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= Security::escape($title ?? 'Biblioteca Virtual ST') ?> · Biblioteca Virtual ST</title>
    <link rel="stylesheet" href="<?= Security::escape(Security::asset('assets/app.css')) ?>">
    <script src="<?= Security::escape(Security::asset('assets/app.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Saltar al contenido</a>
<div class="shell">
    <aside class="sidebar" id="site-navigation" aria-label="Navegación principal">
        <a class="brand" href="<?= Security::escape(Security::url('dashboard')) ?>">
            <span class="brand__seal" aria-hidden="true">ST</span>
            <span class="brand__text">
                <strong>Biblioteca</strong>
                <small>Virtual ST</small>
            </span>
        </a>
        <div class="sidebar__caption">Biblioteca</div>
        <nav class="nav-list">
            <?php foreach ($navigation as $item): ?>
                <?php $active = ($route ?? '') === $item['route']
                    || ($item['route'] === 'books' && ($route ?? '') === 'catalogs'); ?>
                <a class="nav-link<?= $active ? ' is-active' : '' ?>"
                   href="<?= Security::escape(Security::url($item['route'])) ?>"
                   <?= $active ? 'aria-current="page"' : '' ?>>
                    <span class="nav-link__mark" aria-hidden="true"><?= Security::escape($item['mark']) ?></span>
                    <span><?= Security::escape($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar__bottom">
            <div class="signed-in">
                <span class="signed-in__dot" aria-hidden="true"></span>
                <span>
                    <strong><?= Security::escape(($currentUser['nombre'] ?? '') . ' ' . ($currentUser['apellidos'] ?? '')) ?></strong>
                    <small><?= Security::escape($role) ?></small>
                </span>
            </div>
            <form method="post" action="<?= Security::escape(Security::url('logout')) ?>">
                <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                <button class="logout-button" type="submit">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <button class="nav-toggle" type="button" aria-controls="site-navigation" aria-expanded="false">
                <span class="sr-only">Abrir navegación</span>
                <span aria-hidden="true">☰</span>
            </button>
            <div class="topbar__context">
                <span class="topbar__role"><?= Security::escape($role) ?></span>
                <span class="topbar__separator" aria-hidden="true">/</span>
                <span><?= Security::escape($title ?? '') ?></span>
            </div>
            <span class="topbar__date"><?= Security::escape(date('d/m/Y')) ?></span>
        </header>
        <main class="main-content" id="main-content">
            <?php if (is_array($flash)): ?>
                <div class="notice notice--<?= Security::escape($flash['type'] ?? 'info') ?>" role="status">
                    <?= Security::escape($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
        <footer class="footer">Biblioteca Virtual ST <span aria-hidden="true">·</span> Gestión institucional de recursos físicos</footer>
    </div>
</div>
</body>
</html>
