<?php
use App\Core\Security;

$counts = $summary['counts'] ?? [];
$alerts = $summary['alertas'] ?? [];
?>
<section class="page-heading">
    <div>
        <p class="eyebrow">Registro y circulación</p>
        <h1>Inicio</h1>
        <p class="page-intro">Vista general del movimiento de la biblioteca.</p>
    </div>
    <div class="heading-actions">
        <a class="button button--quiet" href="<?= Security::escape(Security::url('loans')) ?>">Registrar devolución</a>
        <a class="button button--primary" href="<?= Security::escape(Security::url('loans/create')) ?>">Nuevo préstamo</a>
    </div>
</section>

<section class="metric-strip" aria-label="Resumen de la biblioteca">
    <div class="metric">
        <span class="metric__value"><?= Security::escape($counts['activos'] ?? 0) ?></span>
        <span class="metric__label">Préstamos activos</span>
    </div>
    <div class="metric metric--late">
        <span class="metric__value"><?= Security::escape($counts['vencidos'] ?? 0) ?></span>
        <span class="metric__label">Vencidos</span>
    </div>
    <div class="metric">
        <span class="metric__value"><?= Security::escape($counts['disponibles'] ?? 0) ?></span>
        <span class="metric__label">Ejemplares disponibles</span>
    </div>
    <div class="metric">
        <span class="metric__value"><?= Security::escape($counts['usuarios_activos'] ?? 0) ?></span>
        <span class="metric__label">Usuarios registrados</span>
    </div>
</section>

<section class="content-section">
    <div class="section-heading">
        <div>
            <p class="eyebrow">Seguimiento</p>
            <h2>Próximos a vencer y vencidos</h2>
        </div>
        <a class="text-link" href="<?= Security::escape(Security::url('loans')) ?>">Ver todos los préstamos</a>
    </div>
    <?php if ($alerts === []): ?>
        <div class="empty-state">
            <span class="empty-state__mark" aria-hidden="true">✓</span>
            <div><h3>Todo al día</h3><p>No hay préstamos vencidos ni próximos a vencer.</p></div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Ejemplar</th><th>Persona</th><th>Fecha límite</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($alerts as $loan): ?>
                    <tr>
                        <td><strong><?= Security::escape($loan['titulo']) ?></strong><small class="cell-sub"><?= Security::escape($loan['codigo_barras']) ?></small></td>
                        <td><?= Security::escape($loan['nombres'] . ' ' . $loan['apellidos']) ?></td>
                        <td><?= Security::escape(date('d/m/Y', strtotime($loan['fecha_devolucion_prevista']))) ?></td>
                        <td><span class="status status--<?= Security::escape($loan['alerta']) ?>"><?= $loan['alerta'] === 'vencido' ? 'Vencido' : 'Próximo' ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
