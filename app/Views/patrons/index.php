<?php
use App\Core\Security;
?>
<section class="page-heading">
    <div><p class="eyebrow">Comunidad institucional</p><h1>Usuarios</h1><p class="page-intro">Personas autorizadas para recibir material de la biblioteca.</p></div>
    <a class="button button--primary" href="<?= Security::escape(Security::url('patrons/create')) ?>">Registrar usuario</a>
</section>
<form class="filter-bar filter-bar--simple" method="get" action="<?= Security::escape(Security::asset('index.php')) ?>">
    <input type="hidden" name="r" value="patrons">
    <label class="filter-bar__search"><span>Buscar por documento, nombre o correo</span><input type="search" name="q" value="<?= Security::escape($term) ?>" placeholder="Buscar usuario"></label>
    <button class="button button--quiet" type="submit">Buscar</button>
</form>
<section class="content-section">
    <div class="section-heading section-heading--compact"><h2>Usuarios registrados <span class="count-note"><?= count($patrons) ?></span></h2></div>
    <?php if ($patrons === []): ?>
        <div class="empty-state"><span class="empty-state__mark" aria-hidden="true">○</span><div><h3>No hay usuarios registrados</h3><p>Registra estudiantes o integrantes de la institución para asignar préstamos.</p></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nombre</th><th>Documento</th><th>Grado</th><th>Correo y teléfono</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
                <tbody>
                <?php foreach ($patrons as $patron): ?>
                    <tr>
                        <td><strong><?= Security::escape($patron['nombres'] . ' ' . $patron['apellidos']) ?></strong><small class="cell-sub"><?= Security::escape(ucfirst((string)$patron['tipo_usuario'])) ?></small></td>
                        <td><?= Security::escape($patron['documento']) ?></td>
                        <td><?= Security::escape($patron['grado'] ?: '—') ?></td>
                        <td><?= Security::escape($patron['correo'] ?: '—') ?><small class="cell-sub"><?= Security::escape($patron['telefono'] ?: '') ?></small></td>
                        <td><span class="status status--<?= (int)$patron['activo'] === 1 ? 'active' : 'inactive' ?>"><?= (int)$patron['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                        <td class="table-actions">
                            <a class="text-link" href="<?= Security::escape(Security::url('patrons/create', ['id' => $patron['id']])) ?>">Editar</a>
                            <?php if ((int)$patron['activo'] === 1): ?>
                                <form method="post" action="<?= Security::escape(Security::url('patrons/deactivate')) ?>" data-confirm="¿Dar de baja a esta persona?">
                                    <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                                    <input type="hidden" name="id" value="<?= Security::escape($patron['id']) ?>">
                                    <button class="text-button text-button--danger" type="submit">Dar de baja</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
