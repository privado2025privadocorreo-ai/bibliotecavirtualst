<?php
use App\Core\Security;

$values = [];
foreach ($settings as $setting) {
    $values[$setting['clave']] = $setting;
}
$institutionName = (string)($institution['nombre'] ?? '');
$institutionEmail = (string)($institution['correo'] ?? '');
$institutionAddress = (string)($institution['direccion'] ?? '');
$logoPath = (string)($institution['logotipo'] ?? '');
?>
<section class="page-heading">
    <div><p class="eyebrow">Administración</p><h1>Configuración</h1><p class="page-intro">Parámetros, personal y datos de la institución.</p></div>
</section>
<section class="content-section">
    <div class="section-heading section-heading--compact"><h2>Mi cuenta</h2></div>
    <div class="form-card form-card--narrow">
        <p class="muted">Puedes cambiar la contraseña de tu cuenta desde aquí.</p>
        <a class="button button--primary" href="<?= Security::escape(Security::url('account/password')) ?>">Cambiar mi contraseña</a>
    </div>
</section>
<div class="settings-grid">
    <section class="form-card">
        <div class="section-heading"><div><p class="eyebrow">Circulación</p><h2>Parámetros de préstamo</h2></div></div>
        <form method="post" action="<?= Security::escape(Security::url('settings')) ?>" class="form-stack">
            <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
            <input type="hidden" name="action" value="settings">
            <label for="dias_prestamo">Días para la devolución</label>
            <input id="dias_prestamo" type="number" min="1" name="dias_prestamo" value="<?= Security::escape($values['dias_prestamo']['valor'] ?? '') ?>" required>
            <label for="limite_ejemplares">Máximo de ejemplares por usuario</label>
            <input id="limite_ejemplares" type="number" min="1" name="limite_ejemplares" value="<?= Security::escape($values['limite_ejemplares']['valor'] ?? '') ?>" required>
            <label for="dias_alerta_vencimiento">Aviso de vencimiento (días)</label>
            <input id="dias_alerta_vencimiento" type="number" min="0" name="dias_alerta_vencimiento" value="<?= Security::escape($values['dias_alerta_vencimiento']['valor'] ?? '') ?>" required>
            <button class="button button--primary" type="submit">Guardar parámetros</button>
        </form>
    </section>

    <section class="form-card">
        <div class="section-heading"><div><p class="eyebrow">Institución</p><h2>Datos de contacto</h2></div></div>
        <?php if ($logoPath !== ''): ?><img class="institution-logo" src="<?= Security::escape(Security::asset($logoPath)) ?>" alt="Logotipo de <?= Security::escape($institutionName) ?>"><?php endif; ?>
        <form method="post" action="<?= Security::escape(Security::url('settings')) ?>" enctype="multipart/form-data" class="form-stack">
            <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
            <input type="hidden" name="action" value="institution">
            <label for="institution-name">Nombre de la institución</label>
            <input id="institution-name" name="nombre" maxlength="150" value="<?= Security::escape($institutionName) ?>" required>
            <label for="institution-email">Correo de contacto</label>
            <input id="institution-email" type="email" name="correo" maxlength="150" value="<?= Security::escape($institutionEmail) ?>" required>
            <label for="institution-address">Dirección física</label>
            <input id="institution-address" name="direccion" maxlength="255" value="<?= Security::escape($institutionAddress) ?>">
            <label for="institution-logo">Logotipo <small>PNG, JPG o WebP; máximo 2 MB</small></label>
            <input id="institution-logo" type="file" name="logotipo" accept="image/png,image/jpeg,image/webp">
            <button class="button button--primary" type="submit">Guardar datos</button>
        </form>
    </section>
</div>

<section class="content-section content-section--note">
    <div><p class="eyebrow">Acción permanente</p><h2>Eliminar institución</h2>
        <p>Eliminará también, de forma permanente, todo su personal, usuarios, libros, ejemplares, préstamos, catálogos y configuración. No se puede deshacer.</p>
        <form method="post" action="<?= Security::escape(Security::url('settings')) ?>" class="form-stack" data-confirm="Esta acción borrará permanentemente la institución y todos sus datos, incluidos los préstamos. ¿Deseas continuar?">
            <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
            <input type="hidden" name="action" value="institution-delete">
            <label for="confirmar-eliminacion">Escribe ELIMINAR para confirmar</label>
            <input id="confirmar-eliminacion" name="confirmar_eliminacion" autocomplete="off" required>
            <button class="button button--danger" type="submit">Eliminar cuenta institucional</button>
        </form>
    </div>
</section>

<section class="content-section">
    <div class="section-heading"><div><p class="eyebrow">Acceso al sistema</p><h2>Personal</h2></div></div>
    <form class="staff-create form-card" method="post" action="<?= Security::escape(Security::url('settings')) ?>">
        <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
        <input type="hidden" name="action" value="staff-save">
        <div class="form-grid">
            <label><span>Nombres</span><input name="nombre" maxlength="100" required></label>
            <label><span>Apellidos</span><input name="apellidos" maxlength="100" required></label>
            <label><span>Correo institucional</span><input type="email" name="correo" maxlength="150" required></label>
            <label><span>Rol</span><select name="rol_id" required><?php foreach ($roles as $role): ?><option value="<?= Security::escape($role['id']) ?>"><?= Security::escape($role['nombre']) ?></option><?php endforeach; ?></select></label>
            <label><span>Contraseña inicial</span><input type="password" name="contrasena" minlength="8" autocomplete="new-password" required></label>
        </div>
        <div class="form-actions"><span class="form-hint">La contraseña se almacena mediante un hash seguro.</span><button class="button button--primary" type="submit">Crear cuenta</button></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Personal</th><th>Correo</th><th>Rol y estado</th><th>Restablecer contraseña</th><th><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            <?php foreach ($staff as $member): ?>
                <tr>
                    <td>
                        <form id="staff-update-<?= Security::escape($member['id']) ?>" method="post" action="<?= Security::escape(Security::url('settings')) ?>" class="hidden-form">
                            <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                            <input type="hidden" name="action" value="staff-save">
                            <input type="hidden" name="id" value="<?= Security::escape($member['id']) ?>">
                            <?php if ((int)$member['id'] === $currentStaffId): ?><input type="hidden" name="rol_id" value="<?= Security::escape($member['rol_id']) ?>"><?php endif; ?>
                        </form>
                        <input form="staff-update-<?= Security::escape($member['id']) ?>" name="nombre" maxlength="100" value="<?= Security::escape($member['nombre']) ?>" aria-label="Nombres de <?= Security::escape($member['correo']) ?>" required>
                        <input form="staff-update-<?= Security::escape($member['id']) ?>" name="apellidos" maxlength="100" value="<?= Security::escape($member['apellidos']) ?>" aria-label="Apellidos de <?= Security::escape($member['correo']) ?>" required>
                    </td>
                    <td><input form="staff-update-<?= Security::escape($member['id']) ?>" type="email" name="correo" maxlength="150" value="<?= Security::escape($member['correo']) ?>" aria-label="Correo de <?= Security::escape($member['nombre']) ?>" required></td>
                    <td>
                        <select name="rol_id" form="staff-update-<?= Security::escape($member['id']) ?>" aria-label="Rol de <?= Security::escape($member['nombre']) ?>" <?= (int)$member['id'] === $currentStaffId ? 'disabled' : '' ?>>
                            <?php foreach ($roles as $role): ?><option value="<?= Security::escape($role['id']) ?>" <?= (int)$member['rol_id'] === (int)$role['id'] ? 'selected' : '' ?>><?= Security::escape($role['nombre']) ?></option><?php endforeach; ?>
                        </select>
                        <span class="status status--<?= (int)$member['activo'] === 1 ? 'active' : 'inactive' ?>"><?= (int)$member['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span>
                    </td>
                    <td>
                        <?php if ((int)$member['id'] === $currentStaffId): ?>
                            <span class="form-hint">Usa «Cambiar mi contraseña».</span>
                        <?php else: ?>
                            <input form="staff-update-<?= Security::escape($member['id']) ?>" name="contrasena" type="password" minlength="8" autocomplete="new-password" placeholder="Dejar vacío para conservar" aria-label="Nueva contraseña de <?= Security::escape($member['nombre']) ?>">
                        <?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <button class="text-button" type="submit" form="staff-update-<?= Security::escape($member['id']) ?>">Guardar cambios</button>
                        <?php if ((int)$member['id'] !== $currentStaffId): ?>
                            <form method="post" action="<?= Security::escape(Security::url('settings')) ?>" data-confirm="¿Eliminar permanentemente esta cuenta del personal?">
                                <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                                <input type="hidden" name="action" value="staff-delete">
                                <input type="hidden" name="id" value="<?= Security::escape($member['id']) ?>">
                                <button class="text-button text-button--danger" type="submit">Eliminar cuenta</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="form-hint">Deja el campo de contraseña vacío para conservarla. Solo el Administrador puede cambiar roles o restablecer credenciales.</p>
</section>

<section class="content-section content-section--note">
    <div><p class="eyebrow">Respaldo de datos</p><h2>Exportación pendiente de política</h2><p>La documentación contempla copias de seguridad, pero falta definir destino, permisos y protección del archivo. Esa exportación no está habilitada todavía.</p></div>
</section>
