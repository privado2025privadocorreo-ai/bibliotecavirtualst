<?php
use App\Core\Security;

$value = static function (string $key, string $default = '') use ($book): string {
    return (string)($book[$key] ?? $default);
};
$authorsSelected = array_map('intval', $book['autor_ids'] ?? []);
$categoriesSelected = array_map('intval', $book['categoria_ids'] ?? []);
$copyCounts = $book['cantidad_ejemplares'] ?? ['bueno' => '1', 'intermedio' => '0', 'arreglar' => '0'];
?>
<section class="page-heading">
    <div><p class="eyebrow">Inventario bibliográfico</p><h1><?= Security::escape($title) ?></h1><p class="page-intro">Los datos descriptivos pertenecen al título; el código de barras identifica una copia física.</p></div>
    <a class="button button--quiet" href="<?= Security::escape(Security::url('books')) ?>">Volver al catálogo</a>
</section>
<?php if ($errors !== []): ?>
    <div class="notice notice--error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= Security::escape($error) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form class="form-card" method="post" action="<?= Security::escape(Security::url('books/create')) ?>">
    <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
    <?php if (!empty($book['id'])): ?><input type="hidden" name="id" value="<?= Security::escape($book['id']) ?>"><?php endif; ?>
    <div class="form-section"><h2>Datos del título</h2>
        <div class="form-grid">
            <label class="field field--wide"><span>Título *</span><input name="titulo" maxlength="255" value="<?= Security::escape($value('titulo')) ?>" required></label>
            <label><span>Subtítulo</span><input name="subtitulo" maxlength="255" value="<?= Security::escape($value('subtitulo')) ?>"></label>
            <label><span>ISBN</span><input name="isbn" maxlength="20" value="<?= Security::escape($value('isbn')) ?>"></label>
            <label><span>Editorial</span><select name="editorial_id"><option value="">Sin editorial</option><?php foreach ($catalogs['editoriales'] as $entry): ?><option value="<?= Security::escape($entry['id']) ?>" <?= (int)$value('editorial_id') === (int)$entry['id'] ? 'selected' : '' ?>><?= Security::escape($entry['nombre']) ?></option><?php endforeach; ?></select></label>
            <label><span>Año de publicación</span><input type="number" min="0" max="2100" name="anio_publicacion" value="<?= Security::escape($value('anio_publicacion')) ?>"></label>
            <label><span>Idioma</span><input name="idioma" maxlength="30" value="<?= Security::escape($value('idioma')) ?>"></label>
            <label class="field--wide"><span>Descripción</span><textarea name="descripcion" rows="3"><?= Security::escape($value('descripcion')) ?></textarea></label>
        </div>
    </div>
    <div class="form-section"><h2>Clasificación</h2>
        <div class="form-grid">
            <label class="field--wide"><span>Autores</span><select name="autores[]" multiple size="4"><?php foreach ($catalogs['autores'] as $entry): ?><option value="<?= Security::escape($entry['id']) ?>" <?= in_array((int)$entry['id'], $authorsSelected, true) ? 'selected' : '' ?>><?= Security::escape(trim($entry['nombres'] . ' ' . $entry['apellidos'])) ?></option><?php endforeach; ?></select><small>Usa Ctrl o Cmd para seleccionar más de uno.</small></label>
            <label class="field--wide"><span>Géneros, categorías y temas</span><select name="categorias[]" multiple size="4"><?php foreach ($catalogs['categorias'] as $entry): ?><option value="<?= Security::escape($entry['id']) ?>" <?= in_array((int)$entry['id'], $categoriesSelected, true) ? 'selected' : '' ?>><?= Security::escape(ucfirst($entry['tipo']) . ': ' . $entry['nombre']) ?></option><?php endforeach; ?></select><small>Usa Ctrl o Cmd para seleccionar más de uno.</small></label>
        </div>
        <p class="form-hint">¿Falta un autor, editorial o clasificación? <a class="text-link" href="<?= Security::escape(Security::url('catalogs')) ?>">Administra los catálogos</a>.</p>
    </div>
    <?php if (empty($book['id'])): ?>
        <div class="form-section"><h2>Ejemplares iniciales</h2><p class="muted">Indica cuántos ejemplares hay en cada condición. Los códigos de barras se generan automáticamente y puedes editarlos después.</p>
            <div class="form-grid">
                <label><span>En buen estado</span><input type="number" min="0" max="999" name="cantidad_bueno" value="<?= Security::escape($copyCounts['bueno'] ?? '0') ?>" required></label>
                <label><span>Estado intermedio</span><input type="number" min="0" max="999" name="cantidad_intermedio" value="<?= Security::escape($copyCounts['intermedio'] ?? '0') ?>" required></label>
                <label><span>Necesitan arreglo</span><input type="number" min="0" max="999" name="cantidad_arreglar" value="<?= Security::escape($copyCounts['arreglar'] ?? '0') ?>" required></label>
                <label><span>Formato</span><select name="formato_id"><option value="">Sin especificar</option><?php foreach ($catalogs['formatos'] as $entry): ?><option value="<?= Security::escape($entry['id']) ?>"><?= Security::escape($entry['nombre']) ?></option><?php endforeach; ?></select></label>
                <label><span>Ubicación</span><input name="ubicacion" maxlength="100" placeholder="Ej. Estante 4, nivel 2"></label>
            </div>
        </div>
    <?php endif; ?>
    <div class="form-actions"><a class="button button--quiet" href="<?= Security::escape(Security::url('books')) ?>">Cancelar</a><button class="button button--primary" type="submit">Guardar libro</button></div>
</form>
    <?php if (!empty($book['id'])): ?>
        <section class="form-card copy-management"><div class="form-section"><h2>Ejemplares</h2>
            <?php if ($copies === []): ?><p class="muted">Este título aún no tiene ejemplares físicos.</p><?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Código</th><th>Formato</th><th>Ubicación</th><th>Condición</th><th>Circulación</th><th>Acción</th></tr></thead>
                        <tbody>
                        <?php foreach ($copies as $copy): ?>
                            <?php
                            $copyStatus = $copy['estado'] === 'baja'
                                ? 'Baja'
                                : ($copy['estado'] === 'prestado'
                                    ? 'En préstamo'
                                    : ($copy['condicion'] === 'arreglar' ? 'Arreglar' : 'Disponible'));
                            $copyStatusClass = $copy['estado'] === 'baja'
                                ? 'inactive'
                                : ($copy['estado'] === 'prestado' || $copy['condicion'] === 'arreglar'
                                    ? 'closed'
                                    : 'active');
                            ?>
                            <tr>
                                <td>
                                    <form id="copy-update-<?= Security::escape($copy['id']) ?>" method="post" action="<?= Security::escape(Security::url('books/copy')) ?>" class="hidden-form">
                                        <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="book_id" value="<?= Security::escape($book['id']) ?>">
                                        <input type="hidden" name="copy_id" value="<?= Security::escape($copy['id']) ?>">
                                    </form>
                                    <input form="copy-update-<?= Security::escape($copy['id']) ?>" name="codigo_barras" maxlength="50" value="<?= Security::escape($copy['codigo_barras']) ?>" aria-label="Código de barras del ejemplar">
                                </td>
                                <td><select form="copy-update-<?= Security::escape($copy['id']) ?>" name="formato_id" aria-label="Formato del ejemplar"><option value="">Sin especificar</option><?php foreach ($catalogs['formatos'] as $entry): ?><option value="<?= Security::escape($entry['id']) ?>" <?= (int)$copy['formato_id'] === (int)$entry['id'] ? 'selected' : '' ?>><?= Security::escape($entry['nombre']) ?></option><?php endforeach; ?></select></td>
                                <td>
                                    <input form="copy-update-<?= Security::escape($copy['id']) ?>" name="ubicacion" maxlength="100" value="<?= Security::escape($copy['ubicacion']) ?>" aria-label="Ubicación del ejemplar">
                                    <input form="copy-update-<?= Security::escape($copy['id']) ?>" name="observaciones" maxlength="255" value="<?= Security::escape($copy['observaciones']) ?>" placeholder="Observaciones" aria-label="Observaciones del ejemplar">
                                </td>
                                <td><select form="copy-update-<?= Security::escape($copy['id']) ?>" name="condicion" aria-label="Condición física del ejemplar">
                                    <option value="bueno" <?= $copy['condicion'] === 'bueno' ? 'selected' : '' ?>>Bueno</option>
                                    <option value="intermedio" <?= $copy['condicion'] === 'intermedio' ? 'selected' : '' ?>>Intermedio</option>
                                    <option value="arreglar" <?= $copy['condicion'] === 'arreglar' ? 'selected' : '' ?>>Arreglar</option>
                                </select></td>
                                <td><span class="status status--<?= Security::escape($copyStatusClass) ?>"><?= Security::escape($copyStatus) ?></span></td>
                                <td>
                                    <?php if ($copy['estado'] !== 'prestado'): ?>
                                        <button class="text-button" type="submit" form="copy-update-<?= Security::escape($copy['id']) ?>">Guardar datos</button>
                                    <?php endif; ?>
                                    <?php if ($copy['estado'] !== 'prestado'): ?>
                                        <form method="post" action="<?= Security::escape(Security::url('books/copy')) ?>" class="copy-state-form" data-confirm="<?= $copy['estado'] === 'baja' ? '¿Reactivar este ejemplar?' : '¿Dar de baja este ejemplar?' ?>">
                                            <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                                            <input type="hidden" name="action" value="status">
                                            <input type="hidden" name="book_id" value="<?= Security::escape($book['id']) ?>">
                                            <input type="hidden" name="copy_id" value="<?= Security::escape($copy['id']) ?>">
                                            <input type="hidden" name="estado" value="<?= $copy['estado'] === 'baja' ? 'disponible' : 'baja' ?>">
                                            <button class="text-button" type="submit"><?= $copy['estado'] === 'baja' ? 'Reactivar' : 'Dar de baja' ?></button>
                                        </form>
                                        <form method="post" action="<?= Security::escape(Security::url('books/copy')) ?>" data-confirm="¿Eliminar permanentemente este ejemplar? Si tiene historial de préstamos, no se podrá eliminar.">
                                            <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="book_id" value="<?= Security::escape($book['id']) ?>">
                                            <input type="hidden" name="copy_id" value="<?= Security::escape($copy['id']) ?>">
                                            <button class="text-button text-button--danger" type="submit">Eliminar</button>
                                        </form>
                                    <?php else: ?><span class="muted">En circulación</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <h3 class="subsection-title">Agregar otro ejemplar</h3>
            <form method="post" action="<?= Security::escape(Security::url('books/copy')) ?>" class="form-grid">
                <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="book_id" value="<?= Security::escape($book['id']) ?>">
                <label><span>Código de barras *</span><input name="codigo_barras" maxlength="50" required></label>
                <label><span>Formato</span><select name="formato_id"><option value="">Sin especificar</option><?php foreach ($catalogs['formatos'] as $entry): ?><option value="<?= Security::escape($entry['id']) ?>"><?= Security::escape($entry['nombre']) ?></option><?php endforeach; ?></select></label>
                <label><span>Condición física</span><select name="condicion"><option value="bueno">Bueno</option><option value="intermedio">Intermedio</option><option value="arreglar">Arreglar</option></select></label>
                <label><span>Ubicación</span><input name="ubicacion" maxlength="100"></label>
                <label><span>Observaciones</span><input name="observaciones" maxlength="255"></label>
                <div class="form-actions"><button class="button button--quiet" type="submit">Agregar ejemplar</button></div>
            </form>
        </div></section>
    <?php endif; ?>
