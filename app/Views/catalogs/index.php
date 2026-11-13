<?php
use App\Core\Security;

$labels = [
    'autor' => 'Autores',
    'editorial' => 'Editoriales',
    'categoria' => 'Géneros, categorías y temas',
    'formato' => 'Formatos físicos',
];
?>
<section class="page-heading">
    <div><p class="eyebrow">Estructura del catálogo</p><h1>Administrar catálogos</h1><p class="page-intro">Organiza los valores que se reutilizan al registrar libros y ejemplares.</p></div>
    <a class="button button--quiet" href="<?= Security::escape(Security::url('books')) ?>">Volver a libros</a>
</section>
<div class="catalog-grid">
    <?php foreach ($labels as $type => $label): ?>
        <section class="form-card catalog-panel">
            <div class="section-heading"><h2><?= Security::escape($label) ?></h2></div>
            <?php if ($selectedType === $type): ?>
                <form method="post" action="<?= Security::escape(Security::url('catalogs')) ?>" class="form-stack catalog-form">
                    <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="tipo_catalogo" value="<?= Security::escape($type) ?>">
                    <?php if (!empty($selected['id'])): ?><input type="hidden" name="id" value="<?= Security::escape($selected['id']) ?>"><?php endif; ?>
                    <?php if ($type === 'autor'): ?>
                        <label>Nombres<input name="nombres" maxlength="100" value="<?= Security::escape($selected['nombres'] ?? '') ?>" required></label>
                        <label>Apellidos<input name="apellidos" maxlength="100" value="<?= Security::escape($selected['apellidos'] ?? '') ?>"></label>
                    <?php elseif ($type === 'categoria'): ?>
                        <label>Tipo<select name="tipo" required><?php foreach (['genero' => 'Género', 'categoria' => 'Categoría', 'tema' => 'Tema'] as $key => $name): ?><option value="<?= Security::escape($key) ?>" <?= ($selected['tipo'] ?? '') === $key ? 'selected' : '' ?>><?= Security::escape($name) ?></option><?php endforeach; ?></select></label>
                        <label>Nombre<input name="nombre" maxlength="100" value="<?= Security::escape($selected['nombre'] ?? '') ?>" required></label>
                        <label>Descripción<input name="descripcion" maxlength="255" value="<?= Security::escape($selected['descripcion'] ?? '') ?>"></label>
                    <?php else: ?>
                        <label>Nombre<input name="nombre" maxlength="<?= $type === 'editorial' ? '150' : '50' ?>" value="<?= Security::escape($selected['nombre'] ?? '') ?>" required></label>
                    <?php endif; ?>
                    <div class="form-actions"><a class="text-link" href="<?= Security::escape(Security::url('catalogs')) ?>">Cancelar</a><button class="button button--small button--primary" type="submit"><?= empty($selected['id']) ? 'Agregar' : 'Guardar' ?></button></div>
                </form>
            <?php else: ?>
                <a class="text-link catalog-add" href="<?= Security::escape(Security::url('catalogs', ['tipo' => $type])) ?>">Agregar elemento</a>
            <?php endif; ?>
            <?php if (($entries[$type] ?? []) === []): ?>
                <p class="muted">Aún no hay elementos registrados.</p>
            <?php else: ?>
                <ul class="catalog-list">
                    <?php foreach ($entries[$type] as $entry): ?>
                        <li>
                            <span>
                                <strong><?= Security::escape($type === 'autor' ? trim($entry['nombre'] . ' ' . $entry['apellidos']) : $entry['nombre']) ?></strong>
                                <?php if ($type === 'categoria'): ?><small><?= Security::escape(ucfirst($entry['tipo'])) ?></small><?php endif; ?>
                            </span>
                            <span class="table-actions">
                                <a class="text-link" href="<?= Security::escape(Security::url('catalogs', ['tipo' => $type, 'id' => $entry['id']])) ?>">Editar</a>
                                <form method="post" action="<?= Security::escape(Security::url('catalogs')) ?>" data-confirm="¿Eliminar este elemento del catálogo? Solo se permite si no está asociado.">
                                    <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="tipo_catalogo" value="<?= Security::escape($type) ?>">
                                    <input type="hidden" name="id" value="<?= Security::escape($entry['id']) ?>">
                                    <button class="text-button text-button--danger" type="submit">Eliminar</button>
                                </form>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
