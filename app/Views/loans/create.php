<?php
use App\Core\Security;

$selectedUser = null;
foreach ($choices['usuarios'] as $user) {
    if ((int)$user['id'] === (int)$selectedPatronId) {
        $selectedUser = $user;
        break;
    }
}
$selectedUserLabel = $selectedUser === null
    ? ''
    : $selectedUser['nombres'] . ' ' . $selectedUser['apellidos'] . ' · ' . $selectedUser['documento'];
$canRegister = $choices['usuarios'] !== [] && $choices['libros'] !== [];
?>
<section class="page-heading">
    <div><p class="eyebrow">Circulación</p><h1>Nuevo préstamo</h1><p class="page-intro">Selecciona una persona y uno o más títulos. Se presta una copia de cada título seleccionado.</p></div>
    <a class="button button--quiet" href="<?= Security::escape(Security::url('loans')) ?>">Volver a préstamos</a>
</section>
<form class="form-card form-card--narrow" method="post" action="<?= Security::escape(Security::url('loans/create')) ?>" data-loan-form>
    <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
    <div class="form-section"><h2>Datos del movimiento</h2>
        <div class="form-stack">
            <label for="usuario_busqueda">Usuario responsable *</label>
            <div class="autocomplete" data-user-limit="<?= Security::escape($choices['limite_ejemplares']) ?>">
                <input id="usuario_busqueda"
                       type="search"
                       autocomplete="off"
                       placeholder="Busca por documento o nombre completo"
                       role="combobox"
                       aria-autocomplete="list"
                       aria-expanded="false"
                       aria-controls="usuario_sugerencias"
                       aria-required="true"
                       required
                       value="<?= Security::escape($selectedUserLabel) ?>">
                <input id="usuario_id" type="hidden" name="usuario_id" value="<?= Security::escape($selectedPatronId ?? '') ?>">
                <div id="usuario_sugerencias" class="autocomplete-list" role="listbox" aria-label="Coincidencias de usuarios" hidden>
                    <?php foreach ($choices['usuarios'] as $user): ?>
                        <?php $label = $user['nombres'] . ' ' . $user['apellidos'] . ' · ' . $user['documento']; ?>
                        <button class="autocomplete-option"
                                id="usuario_opcion_<?= Security::escape($user['id']) ?>"
                                type="button"
                                role="option"
                                aria-selected="false"
                                data-user-id="<?= Security::escape($user['id']) ?>"
                                data-active-loans="<?= Security::escape($user['prestamos_activos']) ?>">
                            <?= Security::escape($label) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p id="usuario_sin_resultados" class="form-hint" aria-live="polite" hidden>No se encontraron coincidencias.</p>
                <p id="usuario_limite" class="form-hint" aria-live="polite">
                    <?php if ($selectedUser !== null): ?>
                        <?= Security::escape($selectedUser['prestamos_activos']) ?> de <?= Security::escape($choices['limite_ejemplares']) ?> ejemplares activos.
                    <?php else: ?>
                        Selecciona una coincidencia para ver el límite disponible.
                    <?php endif; ?>
                </p>
            </div>

            <fieldset class="book-picker" aria-describedby="libros_limite">
                <legend>Títulos disponibles *</legend>
                <p id="libros_limite" class="form-hint" aria-live="polite">Selecciona primero el usuario responsable.</p>
                <?php if ($choices['libros'] === []): ?>
                    <p class="form-hint">No hay títulos con ejemplares disponibles.</p>
                <?php else: ?>
                    <div class="book-choice-list">
                        <?php foreach ($choices['libros'] as $book): ?>
                            <?php $bookId = (int)$book['id']; ?>
                            <label class="book-choice" for="libro_<?= Security::escape($bookId) ?>">
                                <input id="libro_<?= Security::escape($bookId) ?>"
                                       type="checkbox"
                                       name="libro_ids[]"
                                       value="<?= Security::escape($bookId) ?>"
                                       data-book-option
                                       <?= in_array($bookId, $selectedBookIds, true) ? 'checked' : '' ?>>
                                <span class="book-choice__title"><?= Security::escape($book['titulo']) ?></span>
                                <span class="book-choice__availability"><?= Security::escape($book['disponibles']) ?> disponibles</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>

            <label for="nota">Nota (opcional)</label>
            <textarea id="nota" name="nota" maxlength="500" rows="3"><?= Security::escape($note) ?></textarea>
            <?php if (!$canRegister): ?>
                <p class="form-hint">Para registrar un préstamo debe haber al menos un usuario activo y un título con copias disponibles.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="form-actions"><a class="button button--quiet" href="<?= Security::escape(Security::url('loans')) ?>">Cancelar</a><button class="button button--primary" type="submit" <?= !$canRegister ? 'disabled' : '' ?>>Registrar préstamo</button></div>
</form>
