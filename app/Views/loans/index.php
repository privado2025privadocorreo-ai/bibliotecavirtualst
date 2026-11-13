<?php
use App\Core\Security;
?>
<section class="page-heading">
    <div><p class="eyebrow">Circulación</p><h1>En préstamo</h1><p class="page-intro">Cada registro agrupa los títulos entregados; el estado y la devolución se controlan por ejemplar.</p></div>
    <a class="button button--primary" href="<?= Security::escape(Security::url('loans/create')) ?>">Nuevo préstamo</a>
</section>
<div class="legend" aria-label="Estados de préstamo">
    <span><i class="legend__dot legend__dot--late"></i> Vencido</span>
    <span><i class="legend__dot legend__dot--soon"></i> Próximo a vencer</span>
    <span><i class="legend__dot legend__dot--good"></i> A tiempo</span>
</div>
<form class="filter-bar" method="get" action="<?= Security::escape(Security::asset('index.php')) ?>">
    <input type="hidden" name="r" value="loans">
    <label><span>Fecha de préstamo</span><input type="date" name="fecha_prestamo" value="<?= Security::escape($filters['fecha_prestamo']) ?>"></label>
    <label><span>Fecha de devolución prevista o real</span><input type="date" name="fecha_devolucion" value="<?= Security::escape($filters['fecha_devolucion']) ?>"></label>
    <label><span>Grado escolar</span><select name="grado_id"><option value="">Todos</option><?php foreach ($grades as $grade): ?><option value="<?= Security::escape($grade['id']) ?>" <?= (int)$filters['grado_id'] === (int)$grade['id'] ? 'selected' : '' ?>><?= Security::escape($grade['nombre']) ?></option><?php endforeach; ?></select></label>
    <button class="button button--quiet" type="submit">Aplicar filtros</button>
</form>
<section class="content-section">
    <div class="section-heading section-heading--compact"><h2>Movimientos <span class="count-note"><?= count($loans) ?></span></h2></div>
    <?php if ($loans === []): ?>
        <div class="empty-state"><span class="empty-state__mark" aria-hidden="true">↗</span><div><h3>No hay préstamos para mostrar</h3><p>Al registrar una salida, aparecerá aquí con su fecha de devolución.</p></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Títulos / ejemplares</th><th>Usuario</th><th>Grado</th><th>Préstamo</th><th>Devolución prevista</th><th>Estado</th><th><span class="sr-only">Acción</span></th></tr></thead>
                <tbody>
                <?php foreach ($loans as $loan): ?>
                    <?php $groupIsLate = in_array('vencido', array_column($loan['items'], 'alerta'), true); ?>
                    <tr class="<?= $groupIsLate ? 'row--late' : '' ?>">
                        <td>
                            <div class="loan-items">
                                <?php foreach ($loan['items'] as $item): ?>
                                    <div class="loan-item">
                                        <strong><?= Security::escape($item['titulo']) ?></strong>
                                        <small class="cell-sub"><?= Security::escape($item['codigo_barras']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td><?= Security::escape($loan['nombres'] . ' ' . $loan['apellidos']) ?><small class="cell-sub"><?= Security::escape($loan['documento']) ?></small></td>
                        <td><?= Security::escape($loan['grado'] ?: '—') ?></td>
                        <td><?= Security::escape(date('d/m/Y', strtotime($loan['fecha_prestamo']))) ?></td>
                        <td><?= Security::escape(date('d/m/Y', strtotime($loan['fecha_devolucion_prevista']))) ?></td>
                        <td>
                            <div class="loan-items">
                                <?php foreach ($loan['items'] as $item): ?>
                                    <div class="loan-item">
                                        <?php if ($item['estado'] === 'activo'): ?>
                                            <span class="status status--<?= Security::escape($item['alerta']) ?>"><?= $item['alerta'] === 'vencido' ? 'Vencido' : ($item['alerta'] === 'por_vencer' ? 'Próximo' : 'A tiempo') ?></span>
                                        <?php else: ?>
                                            <span class="status status--closed"><?= Security::escape(ucfirst($item['estado'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <div class="loan-items">
                                <?php foreach ($loan['items'] as $item): ?>
                                    <div class="loan-item">
                                        <?php if ($item['estado'] === 'activo'): ?>
                                            <form method="post" action="<?= Security::escape(Security::url('loans/return')) ?>">
                                                <input type="hidden" name="_csrf" value="<?= Security::escape(Security::token()) ?>">
                                                <input type="hidden" name="id" value="<?= Security::escape($item['id']) ?>">
                                                <button class="button button--small button--quiet" type="submit">Registrar devolución</button>
                                            </form>
                                        <?php else: ?>
                                            <?php $returnDate = trim((string)($item['fecha_devolucion_real'] ?? '')); ?>
                                            <small><?= Security::escape($returnDate === '' || strtotime($returnDate) === false ? '—' : date('d/m/Y', strtotime($returnDate))) ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
