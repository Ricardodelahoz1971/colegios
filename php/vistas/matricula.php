<?php

// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);
// PHP/VISTAS/MATRICULA.PHP - REFACTORIZACIÓN BOOTSTRAP 5 ELITE
if (!tiene_permiso('matricula')) {
    echo "<div class='container py-5 text-center'><h2 class='text-danger fw-bold'>ACCESO DENEGADO</h2><p>No tienes privilegios para realizar matrículas.</p></div>";
    return;
}
$stmt_courses = $db->prepare("SELECT * FROM cursos ORDER BY nombre_curso ASC");
$stmt_courses->execute();

// Calcular el siguiente folio de matrícula autoincremental para el año actual (ej: 2026-0001)
$current_year = date('Y');
$stmt_f_next = $db->prepare("SELECT folio_matricula FROM estudiantes_datos_adicionales WHERE folio_matricula LIKE ? ORDER BY folio_matricula DESC LIMIT 1");
$stmt_f_next->execute([$current_year . '-%']);
$last_f_val = $stmt_f_next->fetchColumn();
$next_f_num = 1;
if ($last_f_val) {
    $f_parts = explode('-', $last_f_val);
    if (count($f_parts) === 2) {
        $next_f_num = (int)$f_parts[1] + 1;
    }
}
$next_folio_str = $current_year . '-' . str_pad((string)$next_f_num, 4, '0', STR_PAD_LEFT);
?>

<div class="container py-4">
    
    <div class="col-lg-11 col-xl-10 mx-auto">
        <div class="card shadow-lg border-0 rounded-4 overflow-visible">
            <!-- CABECERA -->
            <div class="card-header bg-primary text-white py-4 px-5">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 fw-bold mb-0 text-white">MATRÍCULA ACADÉMICA</h1>
                        <p class="mb-0 opacity-75">Gestión Oficial de Registro de Estudiantes - <?php echo $cfg_school; ?></p>
                    </div>
                    <div class="bg-white text-primary px-3 py-2 rounded-3 fw-bold shadow-sm">
                        AÑO 2026
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4 p-md-5">
                <form id="formMatricula" method="post" class="needs-validation" novalidate>
                    <!-- 🛡️ BLINDAJE CSRF ANTE PROTECCIÓN EXTREMA -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    
                    <!-- I. IDENTIDAD -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2 border-topbar-elite">
                            <span class="matricula-step-badge">1</span>
                            <h2 class="h5 fw-bold text-primary mb-0 text-uppercase letter-spacing-1">Identidad del Estudiante</h2>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-secondary">Tipo de Documento</label>
                                <select name="tipo_documento" class="select-elite" required>
                                    <option value="" disabled selected>Seleccione...</option>
                                    <option value="RC">Registro Civil (RC)</option>
                                    <option value="TI">Tarjeta de Identidad (TI)</option>
                                    <option value="CC">Cédula de Ciudadanía (CC)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-secondary">Identificación</label>
                                <input type="text" name="identificacion" placeholder="Sin puntos" class="input-elite" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-secondary">RH / Grupo Sanguíneo</label>
                                <select name="tipo_sangre" class="select-elite" required>
                                    <option value="" disabled selected>Seleccione...</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Nombres Completos</label>
                                <input type="text" name="nombre" placeholder="Nombre(s)" class="input-elite" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Apellidos Completos</label>
                                <input type="text" name="apellido" placeholder="Apellido(s)" class="input-elite" required>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-secondary mb-2">Género</label>
                                <div class="d-flex gap-4 p-2 px-4 bg-white rounded-pill border w-fit-content border-topbar-elite">
                                    <label class="radio-elite">
                                        <input type="radio" name="genero" value="M" required>
                                        <span class="radio-mark"></span>
                                        <span class="small">Masculino</span>
                                    </label>
                                    <label class="radio-elite">
                                        <input type="radio" name="genero" value="F" required>
                                        <span class="radio-mark"></span>
                                        <span class="small">Femenino</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-secondary mb-1"><i class="bi bi-camera me-1 text-primary"></i> Fotografía Digital 3x4 (Formato Oficial)</label>
                                <input type="file" name="foto" accept="image/*" class="input-elite">
                                <div class="form-text fs-nano">Suba la fotografía oficial del estudiante para el expediente, carnés y formatos de matrícula.</div>
                            </div>
                        </div>
                    </div>

                    <!-- II. CONTACTO -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2 border-topbar-elite">
                            <span class="matricula-step-badge">2</span>
                            <h2 class="h5 fw-bold text-primary mb-0 text-uppercase letter-spacing-1">Información de Contacto</h2>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Correo Institucional</label>
                                <div class="position-relative">
                                    <i class="bi bi-envelope input-icon-elite text-accent"></i>
                                    <input type="email" name="email" placeholder="estudiante@dominio.com" class="input-elite input-with-icon-elite" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Número de Celular</label>
                                <div class="position-relative">
                                    <i class="bi bi-phone input-icon-elite text-accent"></i>
                                    <input type="tel" name="celular" placeholder="300 000 0000" class="input-elite input-with-icon-elite" pattern="3[0-9]{9}" required>
                                </div>
                                <div class="form-text small opacity-75">Formato: 10 dígitos iniciando en 3.</div>
                            </div>
                        </div>
                    </div>

                    <!-- III. INFORMACIÓN ADICIONAL DEL ESTUDIANTE -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2 border-topbar-elite">
                            <span class="matricula-step-badge">3</span>
                            <h2 class="h5 fw-bold text-primary mb-0 text-uppercase letter-spacing-1">Información Adicional del Estudiante</h2>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-secondary">Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="input-elite">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-secondary">Edad</label>
                                <input type="number" name="edad" min="0" max="99" class="input-elite">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-secondary">Lugar de Nacimiento</label>
                                <input type="text" name="lugar_nacimiento" placeholder="Ciudad, Dpto." class="input-elite">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-secondary">Nacionalidad</label>
                                <input type="text" name="nacionalidad" placeholder="Ej: Colombiana" class="input-elite">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-secondary">Colegio Anterior</label>
                                <input type="text" name="colegio_anterior" placeholder="Institución previa" class="input-elite">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-secondary">Dirección del Estudiante</label>
                                <input type="text" name="direccion_estudiante" placeholder="Calle/Cra #..." class="input-elite">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-secondary">Folio Matrícula</label>
                                <input type="text" name="folio_matricula" value="<?php echo htmlspecialchars($next_folio_str); ?>" class="input-elite" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- IV. INFORMACIÓN DE PADRES Y ACUDIENTES -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2 border-topbar-elite">
                            <span class="matricula-step-badge">4</span>
                            <h2 class="h5 fw-bold text-primary mb-0 text-uppercase letter-spacing-1">Información de Padres / Acudiente</h2>
                        </div>
                        
                        <div class="row g-4">
                            <!-- PADRE -->
                            <div class="col-lg-6 border-end border-topbar-elite pe-lg-4">
                                <h3 class="h6 fw-bold text-secondary text-uppercase mb-3 border-bottom pb-1"><i class="bi bi-person me-2"></i>Datos del Padre</h3>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted">Nombre Completo</label>
                                        <input type="text" name="padre_nombre" placeholder="Nombre completo del padre" class="input-elite">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted">Tipo Documento</label>
                                        <select name="padre_tipo_documento" class="select-elite">
                                            <option value="CC" selected>C.C.</option>
                                            <option value="CE">C.E.</option>
                                            <option value="TI">T.I.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted">Identificación</label>
                                        <input type="text" name="padre_documento" placeholder="Número de documento" class="input-elite">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted">Expedida en</label>
                                        <input type="text" name="padre_documento_expedicion" placeholder="Lugar de expedición" class="input-elite">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Nacionalidad</label>
                                        <input type="text" name="padre_nacionalidad" placeholder="Nacionalidad" class="input-elite">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Celular</label>
                                        <input type="tel" name="padre_celular" placeholder="Celular" class="input-elite">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Teléfono Fijo</label>
                                        <input type="tel" name="padre_telefono" placeholder="Teléfono" class="input-elite">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Profesión / Ocupación</label>
                                        <input type="text" name="padre_profesion" placeholder="Ocupación" class="input-elite">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted">Dirección de Residencia</label>
                                        <input type="text" name="padre_direccion" placeholder="Dirección del padre" class="input-elite">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted">Correo Electrónico</label>
                                        <input type="email" name="padre_email" placeholder="correo@ejemplo.com" class="input-elite">
                                    </div>
                                </div>
                            </div>

                            <!-- MADRE -->
                            <div class="col-lg-6 ps-lg-4">
                                <h3 class="h6 fw-bold text-secondary text-uppercase mb-3 border-bottom pb-1"><i class="bi bi-person me-2"></i>Datos de la Madre</h3>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted">Nombre Completo</label>
                                        <input type="text" name="madre_nombre" placeholder="Nombre completo de la madre" class="input-elite">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted">Tipo Documento</label>
                                        <select name="madre_tipo_documento" class="select-elite">
                                            <option value="CC" selected>C.C.</option>
                                            <option value="CE">C.E.</option>
                                            <option value="TI">T.I.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted">Identificación</label>
                                        <input type="text" name="madre_documento" placeholder="Número de documento" class="input-elite">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted">Expedida en</label>
                                        <input type="text" name="madre_documento_expedicion" placeholder="Lugar de expedición" class="input-elite">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Nacionalidad</label>
                                        <input type="text" name="madre_nacionalidad" placeholder="Nacionalidad" class="input-elite">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Celular</label>
                                        <input type="tel" name="madre_celular" placeholder="Celular" class="input-elite">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Teléfono Fijo</label>
                                        <input type="tel" name="madre_telefono" placeholder="Teléfono" class="input-elite">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Profesión / Ocupación</label>
                                        <input type="text" name="madre_profesion" placeholder="Ocupación" class="input-elite">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted">Dirección de Residencia</label>
                                        <input type="text" name="madre_direccion" placeholder="Dirección de la madre" class="input-elite">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-muted">Correo Electrónico</label>
                                        <input type="email" name="madre_email" placeholder="correo@ejemplo.com" class="input-elite">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- V. ACADÉMICO -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4 border-bottom pb-2 border-topbar-elite">
                            <span class="matricula-step-badge">5</span>
                            <h2 class="h5 fw-bold text-primary mb-0 text-uppercase letter-spacing-1">Asignación</h2>
                        </div>
                        
                        <div class="row g-4 align-items-center">
                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-secondary">Grado a Matricular</label>
                                <select name="curso_id" class="select-elite text-primary fw-bold" required>
                                    <option value="" disabled selected>Seleccione curso...</option>
                                    <?php while ($c = $stmt_courses->fetch(PDO::FETCH_ASSOC)) { ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre_curso']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4 mt-md-5">
                                <div class="d-flex align-items-center p-2 px-3 bg-light rounded-3 border border-topbar-elite">
                                    <label class="switch-elite me-3">
                                        <input type="checkbox" name="es_antiguo" value="1">
                                        <span class="switch-slider"></span>
                                    </label>
                                    <span class="fw-bold text-uppercase small text-secondary">Estudiante Antiguo</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 text-center">
                        <button type="submit" class="btn-elite btn-elite--primary px-4">
                            <i class="bi bi-person-check-fill me-2"></i>
                            FINALIZAR MATRÍCULA
                        </button>
                    </div>

                    <div class="py-5"></div>
                </form>

                <script src="../js/matricula.js" defer></script>
            </div>
        </div>
    </div>
</div>
