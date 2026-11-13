<?php
use App\Core\Security;
?>
<section class="page-heading">
    <div><p class="eyebrow">Inventario físico</p><h1>Libros</h1><p class="page-intro">Consulta títulos y ejemplares registrados en la biblioteca.</p></div>
    <div class="heading-actions">
        <a class="button button--quiet" href="<?= Security::escape(Security::url('books/export')) ?>">Exportar inventario Excel</a>
        <a class="button button--quiet" href="<?= Security::escape(Security::url('catalogs')) ?>">Administrar catálogos</a>
        <a class="button button--primary" href="<?= Security::escape(Security::url('books/create')) ?>">Registrar libro</a>
    </div>
</section>
<form class="filter-bar" method="get" action="<?= Security::escape(Security::asset('index.php')) ?>">
    <input type="hidden" name="r" value="books">
    <label class="filter-bar__search"><span>Buscar por título, ISBN o código de barras</span>
        <input type="search" name="q" value="<?= Security::escape($filters['q']) ?>" placeholder="Escribe para buscar">
    </label>
    <label><span>Editorial</span>
        <select name="editorial_id"><option value="">Todas</option>
            <?php foreach ($catalogs['editoriales'] as $entry): ?>
                <option value="<?= Security::escape($entry['id']) ?>" <?= (int)$filters['editorial_id'] === (int)$entry['id'] ? 'selected' : '' ?>><?= Security::escape($entry['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label><span>Autor</span>
        <select name="autor_id"><option value="">Todos</option>
            <?php foreach ($catalogs['autores'] as $entry): ?>
                <option value="<?= Security::escape($entry['id']) ?>" <?= (int)$filters['autor_id'] === (int)$entry['id'] ? 'selected' : '' ?>><?= Security::escape(trim($entry['nombres'] . ' ' . $entry['apellidos'])) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label><span>Género, tema o categoría</span>
        <select name="categoria_id"><option value="">Todos</option>
            <?php foreach ($catalogs['categorias'] as $entry): ?>
                <option value="<?= Security::escape($entry['id']) ?>" <?= (int)$filters['categoria_id'] === (int)$entry['id'] ? 'selected' : '' ?>><?= Security::escape(ucfirst($entry['tipo']) . ': ' . $entry['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="button button--quiet" type="submit">Filtrar</button>
</form>

<section class="content-section">
    <div class="section-heading section-heading--compact">
        <h2>Catálogo <span class="count-note"><?= count($books) ?> títulos</span></h2>
    </div>
    <?php if ($books === []): ?>
        <div class="empty-state"><span class="empty-state__mark" aria-hidden="true">⌕</span><div><h3>No hay resultados</h3><p>Prueba con otros términos o registra un libro nuevo.</p></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Título / autor</th><th>ISBN</th><th>Editorial</th><th>Ejemplares</th><th>Disponibles</th><th>En préstamo</th><th>Bueno</th><th>Intermedio</th><th>Arreglar</th><th><span class="sr-only">Acciones</span></th></tr></thead>
                <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><strong><?= Security::escape($book['titulo']) ?></strong><small class="cell-sub"><?= Security::escape($book['autores'] ?: 'Autor no asignado') ?></small></td>
                        <td><?= Security::escape($book['isbn'] ?: '—') ?></td>
                        <td><?= Security::escape($book['editorial'] ?: '—') ?></td>
                        <td><?= Security::escape($book['ejemplares']) ?></td>
                        <td><span class="availability"><?= Security::escape($book['disponibles']) ?> disponibles</span></td>
                        <td><?= Security::escape($book['prestados']) ?></td>
                        <td><?= Security::escape($book['buenos']) ?></td>
                        <td><?= Security::escape($book['intermedios']) ?></td>
                        <td><?= Security::escape($book['por_arreglar']) ?></td>
                        <td class="table-actions">
                            <a class="text-link" href="<?= Security::escape(Security::url('books/create', ['id' => $book['id']])) ?>">Editar</a>
                            <form method="post" action="<?= Security::escape(Security::url('books/deactivate')) ?>" data-confirm="¿Dar de baja este título?">
                                <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                                <input type="hidden" name="id" value="<?= Security::escape($book['id']) ?>">
                                <button class="text-button text-button--danger" type="submit">Dar de baja</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
