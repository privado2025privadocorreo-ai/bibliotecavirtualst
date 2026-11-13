<?php
use App\Core\Security;

$value = static function (string $key) use ($patron): string {
    return (string)($patron[$key] ?? '');
};
?>
<section class="page-heading">
    <div><p class="eyebrow">Registro institucional</p><h1><?= Security::escape($title) ?></h1><p class="page-intro">Los datos de contacto permiten identificar a la persona responsable del préstamo.</p></div>
    <a class="button button--quiet" href="<?= Security::escape(Security::url('patrons')) ?>">Volver a usuarios</a>
</section>
<?php if ($errors !== []): ?><div class="notice notice--error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= Security::escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="form-card" method="post" action="<?= Security::escape(Security::url('patrons/create')) ?>">
    <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
    <?php if (!empty($patron['id'])): ?><input type="hidden" name="id" value="<?= Security::escape($patron['id']) ?>"><?php endif; ?>
    <div class="form-section"><h2>Identificación</h2>
        <div class="form-grid">
            <label><span>Tipo de documento</span><input name="tipo_documento" maxlength="20" value="<?= Security::escape($value('tipo_documento')) ?>" placeholder="CC, TI, CE"></label>
            <label><span>Número de documento *</span><input name="documento" maxlength="30" value="<?= Security::escape($value('documento')) ?>" required></label>
            <label><span>Nombres *</span><input name="nombres" maxlength="100" value="<?= Security::escape($value('nombres')) ?>" required></label>
            <label><span>Apellidos *</span><input name="apellidos" maxlength="100" value="<?= Security::escape($value('apellidos')) ?>" required></label>
            <label><span>Tipo de usuario</span><select name="tipo_usuario"><option value="">Sin especificar</option><?php foreach (['estudiante' => 'Estudiante', 'docente' => 'Docente', 'administrativo' => 'Administrativo'] as $key => $label): ?><option value="<?= Security::escape($key) ?>" <?= $value('tipo_usuario') === $key ? 'selected' : '' ?>><?= Security::escape($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Grado escolar</span><select name="grado_id"><option value="">Sin especificar</option><?php foreach ($grades as $grade): ?><option value="<?= Security::escape($grade['id']) ?>" <?= (int)$value('grado_id') === (int)$grade['id'] ? 'selected' : '' ?>><?= Security::escape($grade['nombre']) ?></option><?php endforeach; ?></select></label>
        </div>
    </div>
    <div class="form-section"><h2>Contacto</h2>
        <div class="form-grid">
            <label><span>Correo</span><input type="email" name="correo" maxlength="150" value="<?= Security::escape($value('correo')) ?>"></label>
            <label><span>Teléfono</span><input type="tel" name="telefono" maxlength="30" value="<?= Security::escape($value('telefono')) ?>"></label>
            <label class="field--wide"><span>Dirección</span><input name="direccion" maxlength="255" value="<?= Security::escape($value('direccion')) ?>"></label>
        </div>
    </div>
    <div class="form-actions"><a class="button button--quiet" href="<?= Security::escape(Security::url('patrons')) ?>">Cancelar</a><button class="button button--primary" type="submit">Guardar usuario</button></div>
</form>
