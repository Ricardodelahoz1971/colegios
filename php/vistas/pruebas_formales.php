<?php
declare(strict_types=1);

// PHP/VISTAS/PRUEBAS_FORMALES.PHP - TEST RUNNER DE MÉTODOS DE CALIFICACIÓN v1.0
require_once __DIR__ . '/../logica/calculadora_notas.php';

// Obtener datos reales de la base de datos para mostrar el estado actual
$escala_db = CalculadoraNotas::obtenerEscala($db);
$politica_db = CalculadoraNotas::obtenerPoliticaRecuperacion($db);

$casos_prueba = [
    [
        'politica' => 'reemplazo',
        'original' => 2.0,
        'recup' => 4.5,
        'aprobacion' => 3.0,
        'esperado' => 4.5,
        'desc' => 'Recuperación mayor, reemplazo directo'
    ],
    [
        'politica' => 'reemplazo',
        'original' => 3.5,
        'recup' => 2.0,
        'aprobacion' => 3.0,
        'esperado' => 3.5,
        'desc' => 'Recuperación menor, conserva original'
    ],
    [
        'politica' => 'reemplazo',
        'original' => 2.5,
        'recup' => null,
        'aprobacion' => 3.0,
        'esperado' => 2.5,
        'desc' => 'Sin recuperación registrada'
    ],
    [
        'politica' => 'promedio',
        'original' => 2.0,
        'recup' => 4.5,
        'aprobacion' => 3.0,
        'esperado' => 3.3,
        'desc' => 'Promedio simple con recuperación exitosa'
    ],
    [
        'politica' => 'promedio',
        'original' => 3.0,
        'recup' => 2.0,
        'aprobacion' => 3.0,
        'esperado' => 3.0,
        'desc' => 'Recuperación menor, no perjudica'
    ],
    [
        'politica' => 'promedio',
        'original' => 1.0,
        'recup' => 5.0,
        'aprobacion' => 3.0,
        'esperado' => 3.0,
        'desc' => 'Promedio simple en extremos'
    ],
    [
        'politica' => 'tope_aprobacion',
        'original' => 2.0,
        'recup' => 4.5,
        'aprobacion' => 3.0,
        'esperado' => 3.0,
        'desc' => 'Excede aprobación, se topa'
    ],
    [
        'politica' => 'tope_aprobacion',
        'original' => 2.0,
        'recup' => 2.5,
        'aprobacion' => 3.0,
        'esperado' => 2.5,
        'desc' => 'No alcanza aprobación, se mantiene recuperada'
    ],
    [
        'politica' => 'tope_aprobacion',
        'original' => 3.5,
        'recup' => 4.5,
        'aprobacion' => 3.0,
        'esperado' => 3.5,
        'desc' => 'Original aprobada, no aplica tope'
    ]
];

$res_totales = 0;
$res_exitosos = 0;
?>

<div class="pruebas-master animate__animated animate__fadeIn">
    <!-- MIGA DE PAN (BREADCRUMB ELITE) -->
    <div class="mb-3">
        <nav class="breadcrumb-elite" aria-label="Ruta de navegación">
            <a href="javascript:void(0)" onclick="navegarModulo('inicio')" class="breadcrumb-link-elite"><i class="bi bi-house-door me-1"></i>Inicio</a>
            <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
            <span class="breadcrumb-item-elite">Evaluación</span>
            <i class="bi bi-chevron-right breadcrumb-separator-elite"></i>
            <span class="breadcrumb-current-elite">Pruebas Formales</span>
        </nav>
    </div>

    <!-- PANEL DE SOBERANÍA E INFORMACIÓN DE CONFIGURACIÓN -->
    <div class="pruebas-master__header-card">
        <div class="pruebas-master__title-section">
            <h4 class="pruebas-master__title">Oráculo de Calificaciones: Pruebas Formales</h4>
            <p class="pruebas-master__subtitle">Auditoría en tiempo real del motor matemático de notas (Perseus Engine)</p>
        </div>
        
        <div class="pruebas-grid">
            <div class="pruebas-grid__card">
                <span class="pruebas-grid__label">Escala Activa</span>
                <div class="pruebas-grid__value">
                    <?= htmlspecialchars((string)$escala_db['nota_minima']) ?> - <?= htmlspecialchars((string)$escala_db['nota_maxima']) ?>
                </div>
            </div>
            <div class="pruebas-grid__card">
                <span class="pruebas-grid__label">Nota Aprobación</span>
                <div class="pruebas-grid__value text-primary">
                    <?= htmlspecialchars((string)$escala_db['nota_aprobacion']) ?>
                </div>
            </div>
            <div class="pruebas-grid__card">
                <span class="pruebas-grid__label">Política Global</span>
                <div class="pruebas-grid__value text-success text-uppercase">
                    <?= htmlspecialchars((string)$politica_db) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA DE CASOS DE PRUEBA -->
    <div class="pruebas-master__content-card">
        <div class="pruebas-master__table-header">
            <h5 class="pruebas-master__table-title">Casos de Prueba de Fórmulas Matemáticas</h5>
            <div class="pruebas-master__buttons-group">
                <a href="dashboard.php?p=configuracion" class="btn-elite btn-elite--secondary text-decoration-none pruebas-master__back-btn">
                    <i class="bi bi-arrow-left me-2"></i>Volver a Herramientas
                </a>
                <button onclick="window.location.reload()" class="btn-elite btn-elite--primary pruebas-master__reload-btn">
                    <i class="bi bi-arrow-clockwise me-2"></i>Volver a Ejecutar
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="pruebas-table">
                <thead>
                    <tr class="pruebas-table__header-row">
                        <th class="pruebas-table__header-cell text-start">Política</th>
                        <th class="pruebas-table__header-cell">Descripción</th>
                        <th class="pruebas-table__header-cell">Nota Orig.</th>
                        <th class="pruebas-table__header-cell">Nota Recup.</th>
                        <th class="pruebas-table__header-cell">Aprobación</th>
                        <th class="pruebas-table__header-cell">Esperado</th>
                        <th class="pruebas-table__header-cell">Calculado</th>
                        <th class="pruebas-table__header-cell">Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($casos_prueba as $caso): 
                        $res_totales++;
                        $calculado = CalculadoraNotas::aplicarPoliticaRecuperacion(
                            (float)$caso['original'],
                            $caso['recup'] !== null ? (float)$caso['recup'] : null,
                            $caso['politica'],
                            (float)$caso['aprobacion']
                        );
                        $exitoso = (abs($calculado - $caso['esperado']) < 0.001);
                        if ($exitoso) {
                            $res_exitosos++;
                        }
                    ?>
                    <tr class="pruebas-table__row">
                        <td class="pruebas-table__cell text-start">
                            <span class="pruebas-table__badge-policy"><?= htmlspecialchars($caso['politica']) ?></span>
                        </td>
                        <td class="pruebas-table__cell text-start text-muted text-truncate-1">
                            <?= htmlspecialchars($caso['desc']) ?>
                        </td>
                        <td class="pruebas-table__cell fw-bold">
                            <?= htmlspecialchars(number_format((float)$caso['original'], 1)) ?>
                        </td>
                        <td class="pruebas-table__cell text-muted">
                            <?= $caso['recup'] !== null ? htmlspecialchars(number_format((float)$caso['recup'], 1)) : 'null' ?>
                        </td>
                        <td class="pruebas-table__cell text-muted">
                            <?= htmlspecialchars(number_format((float)$caso['aprobacion'], 1)) ?>
                        </td>
                        <td class="pruebas-table__cell fw-bold text-dark">
                            <?= htmlspecialchars(number_format((float)$caso['esperado'], 1)) ?>
                        </td>
                        <td class="pruebas-table__cell fw-bold text-primary">
                            <?= htmlspecialchars(number_format((float)$calculado, 1)) ?>
                        </td>
                        <td class="pruebas-table__cell">
                            <?php if ($exitoso): ?>
                                <span class="pruebas-table__badge-success">
                                    <i class="bi bi-shield-check me-1"></i>PASÓ
                                </span>
                            <?php else: ?>
                                <span class="pruebas-table__badge-danger">
                                    <i class="bi bi-shield-x me-1"></i>FALLÓ
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- RESUMEN FINAL -->
        <div class="pruebas-master__summary">
            <span class="pruebas-master__summary-text">
                Resumen de Calidad: <strong><?= intval($res_exitosos) ?></strong> de <strong><?= intval($res_totales) ?></strong> pruebas pasadas con éxito.
            </span>
            <div class="pruebas-master__summary-bar">
                <?php $porcentaje = ($res_totales > 0) ? ($res_exitosos / $res_totales) * 100 : 0; ?>
                <div id="pruebas-progress" class="pruebas-master__summary-progress" data-width="<?= floatval($porcentaje) ?>%"></div>
            </div>
        </div>
    </div>
</div>

<script src="../js/pruebas_formales.js" defer></script>

