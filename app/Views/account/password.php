<?php
use App\Core\Security;
?>
<section class="page-heading">
    <div><p class="eyebrow">Mi cuenta</p><h1>Cambiar contraseña</h1><p class="page-intro">Actualiza la contraseña que usas para entrar a Biblioteca Virtual ST.</p></div>
</section>
<form method="post" action="<?= Security::escape(Security::url('account/password')) ?>" class="form-card form-card--narrow form-stack">
    <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
    <label for="contrasena_actual">Contraseña actual</label>
    <input id="contrasena_actual" name="contrasena_actual" type="password" autocomplete="current-password" required>
    <label for="contrasena_nueva">Nueva contraseña <small>Mínimo 8 caracteres</small></label>
    <input id="contrasena_nueva" name="contrasena_nueva" type="password" minlength="8" autocomplete="new-password" required>
    <label for="confirmar_contrasena">Confirmar nueva contraseña</label>
    <input id="confirmar_contrasena" name="confirmar_contrasena" type="password" minlength="8" autocomplete="new-password" required>
    <div class="form-actions">
        <a class="button button--quiet" href="<?= Security::escape(Security::url('dashboard')) ?>">Cancelar</a>
        <button class="button button--primary" type="submit">Guardar contraseña</button>
    </div>
</form>
