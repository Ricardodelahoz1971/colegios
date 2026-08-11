<?php
declare(strict_types=1);
// Elite UI Kit v9.2 - Laboratorio Maestro de Ingeniería Élite
require_once 'auth.php';

// 🛡️ PROTOCOLO DE INTIMIDACIÓN ÉLITE: Acceso no autorizado
if (!tiene_permiso('personal')) {
    ejecutar_protocolo_intimidacion();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite UI Kit - Laboratorio Maestro v9.1</title>
    
    <!-- Fuentes: Roboto & Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Estilos Nucleares y Modularidad Showroom -->
    <link rel="stylesheet" href="../styles/ui_kit.css">
    <link rel="stylesheet" href="../styles/utilities.css">
    <link rel="stylesheet" href="../styles/elite_showroom.css">

    <style>
        /* --- MODELO A: NAVEGACIÓN AERO (GLASSMOPRHISM) --- */
        .nav-aero-elite {
            background: rgba(32, 65, 146, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            max-width: 280px;
            margin: 20px auto;
        }
        .aero-item-elite {
            list-style: none;
            padding: 12px 18px;
            margin-bottom: 8px;
            border-radius: 14px;
            color: rgba(255,255,255,0.8);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        .aero-item-elite:hover {
            background: rgba(255,255,255,0.1);
            color: #ffffff;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .aero-item-elite.active {
            background: var(--el-accent, #f5bd1f);
            color: #000000;
            box-shadow: 0 8px 20px rgba(var(--el-accent-rgb, 245, 189, 31), 0.4);
        }

        /* Submenú Aero (Fly-out) */
        .aero-item-wrapper-elite { position: relative; }
        .aero-submenu-elite {
            position: absolute;
            left: calc(100% + 15px);
            top: 0;
            background: rgba(32, 65, 146, 0.85);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 18px;
            padding: 10px;
            min-width: 180px;
            box-shadow: 15px 15px 40px rgba(0,0,0,0.3);
            display: none;
            z-index: 1000;
        }
        /* Puente invisible para cubrir la brecha de 15px */
        .aero-submenu-elite::before {
            content: "";
            position: absolute;
            left: -25px; /* Cubre el hueco de 15px + margen de seguridad */
            top: 0;
            width: 25px;
            height: 100%;
            background: transparent;
        }
        .aero-item-wrapper-elite:hover .aero-submenu-elite { 
            display: block; 
            animation: aeroFlyIn 0.3s ease-out;
        }
        
        @keyframes aeroFlyIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .aero-subitem-elite {
            padding: 8px 12px;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.6);
            transition: 0.3s;
            cursor: pointer;
            border-radius: 8px;
        }
        .aero-subitem-elite:hover { color: #fff; background: rgba(255,255,255,0.05); }

        /* --- MODELO B: NAVEGACIÓN BLADE (HIGH-DENSITY PRECISION) --- */
        .nav-blade-elite {
            background: #0f172a;
            border-radius: 12px;
            /* overflow: hidden;  <-- ELIMINADO PARA PERMITIR FLY-OUT */
            border-left: 5px solid var(--el-accent, #f5bd1f);
            box-shadow: 10px 0 30px rgba(0,0,0,0.3);
            max-width: 260px;
            margin: 20px auto;
        }
        .blade-header-elite {
            padding: 15px 20px;
            background: rgba(255,255,255,0.03);
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--el-accent, #f5bd1f);
            font-weight: 800;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .blade-item-elite {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.2s ease;
            border-bottom: 1px solid rgba(255,255,255,0.02);
        }
        .blade-item-elite:hover {
            background: #1e293b;
            color: #ffffff;
            padding-left: 25px;
        }
        .blade-item-elite.active {
            background: rgba(var(--el-accent-rgb, 245, 189, 31), 0.1);
            color: var(--el-accent, #f5bd1f);
            font-weight: 700;
        }

        .blade-item-wrapper-elite { position: relative; }
        .blade-submenu-elite {
            position: absolute;
            left: calc(100% - 12px); /* Superposición táctica para fusión de capas */
            top: 0;
            background: #0f172a;
            border-left: 4px solid var(--el-accent, #f5bd1f);
            border-radius: 0 15px 15px 0; /* Curvatura solo en el lado exterior para mayor fluidez */
            padding: 8px 0;
            min-width: 210px;
            display: none;
            box-shadow: 20px 0 45px rgba(0,0,0,0.4);
            z-index: 1000;
        }
        /* Puente invisible para que el mouse no pierda el hover al transicionar */
        .blade-submenu-elite::before {
            content: "";
            position: absolute;
            left: -20px;
            top: 0;
            width: 20px;
            height: 100%;
            background: transparent;
        }
        
        .blade-item-wrapper-elite:hover .blade-submenu-elite { 
            display: block; 
            animation: bladeFlyIn 0.25s ease-out;
        }

        @keyframes bladeFlyIn {
            from { opacity: 0; transform: translateX(-5px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .blade-subitem-elite {
            display: block;
            padding: 10px 20px;
            font-size: 0.75rem;
            color: #94a3b8;
            text-decoration: none;
            transition: 0.2s;
        }
        .blade-subitem-elite:hover { color: var(--el-accent, #f5bd1f); background: rgba(255,255,255,0.02); }
        .blade-icon-elite {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            margin-right: 12px;
            color: var(--el-accent, #f5bd1f);
        }
        /* 19. Elite Select (Listas Desplegables) */
        .select-elite-wrapper { position: relative; width: 100%; }
        .select-elite {
            appearance: none;
            width: 100%;
            padding: 10px 40px 10px 15px;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .select-elite:focus {
            outline: none;
            border-color: var(--el-primary);
            box-shadow: 0 0 0 4px rgba(var(--el-primary-rgb), 0.1);
            transform: translateY(-1px);
        }
        .select-elite-wrapper::after {
            content: "";
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 10px; height: 6px;
            background-color: #64748b;
            clip-path: polygon(100% 0%, 0 0%, 50% 100%);
            pointer-events: none;
            transition: 0.3s;
        }
        
        /* Variante Aero-Glass */
        .select-elite--aero {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
        }
        .select-elite--aero option { background: #1e293b; color: white; }
        .select-elite--aero:focus {
            background: rgba(255,255,255,0.1);
            border-color: var(--el-accent);
            box-shadow: 0 0 20px rgba(var(--el-accent-rgb), 0.2);
        }

        /* Variante Blade (Industrial Precision) */
        .select-elite--blade {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 4px; /* Bordes afilados */
            color: #94a3b8;
            font-family: 'JetBrains Mono', 'Consolas', monospace;
            letter-spacing: 0.5px;
        }
        .select-elite--blade:focus {
            border-color: var(--el-accent);
            color: white;
            box-shadow: inset 0 0 10px rgba(var(--el-accent-rgb), 0.1), 0 0 15px rgba(var(--el-accent-rgb), 0.3);
        }

        /* Variante Aurora (Cinematic Glow) */
        .select-elite--aurora {
            background: #ffffff;
            border: 2px solid transparent;
            background-image: linear-gradient(white, white), linear-gradient(135deg, var(--el-primary), var(--el-accent));
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .select-elite--aurora:focus {
            box-shadow: 0 0 25px rgba(var(--el-primary-rgb), 0.4);
            transform: scale(1.02);
        }

        /* --- VARIANTES GLOBALES PARA INPUTS Y BOTONES --- */
        /* Inputs Aero */
        .input-elite--aero {
            background: rgba(255,255,255,0.1) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2) !important;
            color: white !important;
        }
        .input-elite--aero::placeholder { color: rgba(255,255,255,0.4); }

        /* Inputs Blade */
        .input-elite--blade {
            background: #0f172a !important;
            border: 1px solid #334155 !important;
            border-radius: 4px !important;
            color: #94a3b8 !important;
            font-family: 'Consolas', monospace;
        }

        /* Botones Aero */
        .btn-elite--aero {
            background: rgba(255,255,255,0.15) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3) !important;
            color: white !important;
            border-radius: 15px !important;
        }

        /* Botones Blade */
        .btn-elite--blade {
            background: #1e293b !important;
            border: 1px solid var(--el-accent) !important;
            border-radius: 4px !important;
            color: var(--el-accent) !important;
            text-transform: uppercase;
            font-weight: 800;
        }
        .btn-elite--blade:hover {
            box-shadow: 0 0 15px rgba(var(--el-accent-rgb), 0.4);
            background: var(--el-accent) !important;
            color: #000 !important;
        }

        /* --- VARIANTES PARA TABLAS --- */
        .tabla-maestra--aero {
            background: rgba(255,255,255,0.05) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: white;
        }
        .tabla-maestra--aero th { background: rgba(255,255,255,0.1) !important; color: var(--el-accent) !important; }

        .tabla-maestra--blade {
            background: #0f172a !important;
            border: 1px solid #334155 !important;
            border-radius: 0 !important;
        }
        .tabla-maestra--blade th { background: #1e293b !important; color: var(--el-accent) !important; border-bottom: 2px solid var(--el-accent); }
        .tabla-maestra--blade td { border-bottom: 1px solid #1e293b; font-family: 'Consolas', monospace; font-size: 0.75rem; }

        /* --- VARIANTES PARA CARDS --- */
        .card-elite--aero {
            background: rgba(255,255,255,0.1) !important;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2) !important;
            border-radius: 20px !important;
            color: white;
        }

        .card-elite--blade {
            background: #0f172a !important;
            border-left: 4px solid var(--el-accent) !important;
            border-radius: 4px !important;
            color: #94a3b8;
        }

        /* --- VARIANTES PARA BUSCADOR --- */
        .search-elite--aero { background: rgba(255,255,255,0.1) !important; border-radius: 50rem !important; border: 1px solid rgba(255,255,255,0.3) !important; }
        .search-elite--aero input { color: white !important; }

        .search-elite--blade { background: #0f172a !important; border-radius: 4px !important; border: 1px solid #334155 !important; }
        .search-elite--blade input { font-family: 'Consolas', monospace; color: var(--el-accent) !important; }
    </style>
</head>
<body class="font-roboto">

    <div class="container">
        <!-- Cabecera de Identidad Visual -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-black hero-title-elite">ELITE UI KIT</h1>
            <p class="text-secondary fw-bold">SISTEMA INTEGRAL DE DISEÑO INSTITUCIONAL • v9.1</p>
            <div class="d-flex justify-content-center gap-3">
                <button onclick="changeFont('Roboto')" class="btn-elite btn-elite--sm btn-dark-elite" data-bs-toggle="tooltip" title="Cambiar a tipografía técnica Roboto">MODO ROBOTO</button>
                <button onclick="changeFont('Montserrat')" class="btn-elite btn-elite--sm btn-dark-elite" data-bs-toggle="tooltip" title="Cambiar a tipografía prestigiosa Montserrat">MODO MONTSERRAT</button>
                <button onclick="document.body.classList.toggle('dark-theme-mode')" class="btn-elite btn-elite--sm btn-primary-lite-elite" data-bs-toggle="tooltip" title="Alternar entre modo claro y oscuro">🌓 MODO OSCURO NATIVO</button>
            </div>
        </div>

        <!-- 01. Paleta y Tipografía -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">01. Tokens de Identidad Institucional</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite">
                            <span class="ficha-nombre-elite">Color Primario</span>
                            <code class="ficha-clase-elite">palette-01-c1</code>
                        </div>
                        <div class="ficha-demo-elite">
                            <div class="tema-pildora-elite w-100" data-bs-toggle="tooltip" title="Color base institucional"><div class="pildora-color-box palette-01-c1"></div><span class="small fw-bold">NÚCLEO ÉLITE</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite">
                            <span class="ficha-nombre-elite">Color de Acento</span>
                            <code class="ficha-clase-elite">palette-01-c2</code>
                        </div>
                        <div class="ficha-demo-elite">
                            <div class="tema-pildora-elite w-100" data-bs-toggle="tooltip" title="Color de acento y prestigio"><div class="pildora-color-box palette-01-c2"></div><span class="small fw-bold">PRESTIGIO ORO</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite">
                            <span class="ficha-nombre-elite">Color Vital</span>
                            <code class="ficha-clase-elite">palette-01-c3</code>
                        </div>
                        <div class="ficha-demo-elite">
                            <div class="tema-pildora-elite w-100" data-bs-toggle="tooltip" title="Color informativo y vital"><div class="pildora-color-box palette-01-c3"></div><span class="small fw-bold">VITALIDAD AZUL</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 02. Navegación Lateral -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">02. Sistemas de Navegación</h5>
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite">
                            <span class="ficha-nombre-elite">Menú con Subniveles</span>
                            <code class="ficha-clase-elite">.menu-elite</code>
                        </div>
                        <div class="ficha-demo-elite d-block p-4">
                            <ul class="menu-elite mb-0">
                                <li class="menu-item-elite">
                                    <a href="#" class="menu-link-elite active">
                                        <span class="d-flex align-items-center gap-3">
                                            <svg class="icon-elite text-primary" viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                            Tablero Principal
                                        </span>
                                    </a>
                                </li>
                                <li class="menu-item-elite">
                                    <a class="menu-link-elite">
                                        <span class="d-flex align-items-center gap-3">
                                            <svg class="icon-elite text-primary" viewBox="0 0 24 24" width="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                                            Gestión Académica
                                        </span>
                                        <svg class="icon-elite arrow-icon" viewBox="0 0 24 24" width="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"></path></svg>
                                    </a>
                                    <ul class="submenu-elite">
                                        <li><a href="#" class="submenu-link-elite">Listado de Alumnos</a></li>
                                        <li><a href="#" class="submenu-link-elite">Control de Notas</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 alert-elite-standard">
                        <h6 class="fw-bold"><svg width="20" class="me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Manifiesto de Ingeniería v9.1</h6>
                        <p class="small mb-0">Se ha implementado la <strong>Modularidad Absoluta</strong>. El HTML está libre de estilos inline, delegando toda la estética al CSS dinámico.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 03. Control de Formularios -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">03. Controles Quirúrgicos (Formularios)</h5>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite">
                            <span class="ficha-nombre-elite">Input Estándar</span>
                            <code class="ficha-clase-elite">.input-elite</code>
                        </div>
                        <div class="ficha-demo-elite">
                            <input type="text" class="input-elite" placeholder="Escriba aquí...">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Input Aero</span></div>
                        <div class="ficha-demo-elite bg-blade rounded-bottom-elite">
                            <input type="text" class="input-elite input-elite--aero" placeholder="Efecto cristal...">
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Input Blade</span></div>
                        <div class="ficha-demo-elite bg-blade-light rounded-bottom-elite">
                            <input type="text" class="input-elite input-elite--blade" placeholder="DATO_SISTEMA_V1">
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="ficha-tecnica-elite">
                                <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Check Estándar</span></div>
                                <div class="ficha-demo-elite">
                                    <label class="checkbox-elite"><input type="checkbox" checked><span class="checkbox-mark"></span></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="ficha-tecnica-elite bg-blade rounded-elite">
                                <div class="ficha-meta-elite"><span class="ficha-nombre-elite text-white">Check Aero</span></div>
                                <div class="ficha-demo-elite">
                                    <label class="checkbox-elite"><input type="checkbox" checked><span class="checkbox-mark bg-aero-glass"></span></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="ficha-tecnica-elite bg-blade-light rounded-elite">
                                <div class="ficha-meta-elite"><span class="ficha-nombre-elite text-accent">Check Blade</span></div>
                                <div class="ficha-demo-elite">
                                    <label class="checkbox-elite"><input type="checkbox" checked><span class="checkbox-mark rounded-1"></span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 04. Gestión de Datos (Tablas Mutantes) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">04. Gestión de Datos (Tablas)</h5>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Tabla Estándar</span></div>
                        <div class="table-responsive p-3">
                            <table class="tabla-maestra small w-100">
                                <thead><tr><th>ID</th><th>ESTADO</th></tr></thead>
                                <tbody><tr><td>#2024</td><td><span class="badge-elite badge-elite--success">ACTIVO</span></td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite bg-blade rounded-3">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite text-white">Tabla Aero</span></div>
                        <div class="table-responsive p-3">
                            <table class="tabla-maestra tabla-maestra--aero small w-100">
                                <thead><tr><th>ID</th><th>ESTADO</th></tr></thead>
                                <tbody><tr><td>#2024</td><td><span class="badge-elite badge-elite--success">ACTIVO</span></td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite bg-blade-light rounded-3">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite text-white">Tabla Blade</span></div>
                        <div class="table-responsive p-3">
                            <table class="tabla-maestra tabla-maestra--blade small w-100">
                                <thead><tr><th>ID</th><th>ESTADO</th></tr></thead>
                                <tbody><tr><td>#2024</td><td><span class="badge-elite badge-elite--success">ACTIVO</span></td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 05. Módulos de Contenedores (Cards) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">05. Módulos de Contenedores (Cards)</h5>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="card-elite-depth p-4 h-100">
                        <h6 class="fw-bold small mb-2">ESTÁNDAR</h6>
                        <p class="text-muted fs-nano mb-0">Elegancia equilibrada.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-elite--aero p-4 h-100 bg-blade">
                        <h6 class="fw-bold small mb-2">AERO-GLASS</h6>
                        <p class="text-white-50 fs-nano mb-0">Translucidez VIP.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-elite--blade p-4 h-100 bg-blade-light">
                        <h6 class="fw-bold small mb-2 text-accent">BLADE-SYSTEM</h6>
                        <p class="text-muted fs-nano mb-0">Precisión técnica.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 06. Control de Búsqueda (Search) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">06. Control de Búsqueda (Search)</h5>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="search-elite-container w-100">
                        <input type="text" class="search-elite" placeholder="Estándar...">
                        <div class="search-icon-elite text-primary"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></div>
                    </div>
                </div>
                <div class="col-md-4 p-3 bg-blade rounded-elite">
                    <div class="search-elite-container search-elite--aero w-100">
                        <input type="text" class="search-elite" placeholder="Aero Search...">
                        <div class="search-icon-elite text-white-50"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></div>
                    </div>
                </div>
                <div class="col-md-4 p-3 bg-blade-light rounded-elite">
                    <div class="search-elite-container search-elite--blade w-100">
                        <input type="text" class="search-elite" placeholder="BLADE_QUERRY_">
                        <div class="search-icon-elite text-accent"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 07. Simulador de Identidad -->
        <div class="component-section-elite simulator-box-elite">
            <h5 class="section-title-elite">07. Propagación de ADN (Simulador Maestro)</h5>
            <p class="text-muted small mb-4">Haz clic para propagar el ADN de identidad a cada píxel de la interfaz.</p>
            <div class="row align-items-center">
                <div class="col-md-5">
                    <div class="palette-container-elite">
                        <div class="palette-swatch-elite" onclick="setGlobalPalette(1)" data-bs-toggle="tooltip" title="Aplicar Identidad Prestigio">
                            <div class="palette-colors-row">
                                <div class="color-circle-elite palette-01-c1"></div>
                                <div class="color-circle-elite palette-01-c2"></div>
                                <div class="color-circle-elite palette-01-c3"></div>
                            </div>
                            <div class="palette-name-elite small fw-bold">01. PRESTIGIO</div>
                        </div>
                        <div class="palette-swatch-elite" onclick="setGlobalPalette(2)" data-bs-toggle="tooltip" title="Aplicar Identidad Vitalidad">
                            <div class="palette-colors-row">
                                <div class="color-circle-elite palette-02-c1"></div>
                                <div class="color-circle-elite palette-02-c2"></div>
                                <div class="color-circle-elite palette-02-c3"></div>
                            </div>
                            <div class="palette-name-elite small fw-bold">02. VITALIDAD</div>
                        </div>
                        <div class="palette-swatch-elite" onclick="setGlobalPalette(3)" data-bs-toggle="tooltip" title="Aplicar Identidad Vanguardia">
                            <div class="palette-colors-row">
                                <div class="color-circle-elite palette-03-c1"></div>
                                <div class="color-circle-elite palette-03-c2"></div>
                                <div class="color-circle-elite palette-03-c3"></div>
                            </div>
                            <div class="palette-name-elite small fw-bold">03. VANGUARDIA</div>
                        </div>
                        <div class="palette-swatch-elite" onclick="setGlobalPalette(4)" data-bs-toggle="tooltip" title="Aplicar Identidad Nobleza">
                            <div class="palette-colors-row">
                                <div class="color-circle-elite palette-04-c1"></div>
                                <div class="color-circle-elite palette-04-c2"></div>
                                <div class="color-circle-elite palette-04-c3"></div>
                            </div>
                            <div class="palette-name-elite small fw-bold">04. NOBLEZA</div>
                        </div>
                    </div>
                </div>
                <!-- Simulador en Vivo de ADN -->
                <div class="col-md-7">
                    <h6 class="small fw-black mb-3 text-secondary text-uppercase">Simulador en Vivo</h6>
                    <div class="live-sim-container">
                        <div class="sim-sidebar-elite">
                            <div class="sim-nav-item"></div>
                            <div class="sim-nav-item sim-nav-item--short"></div>
                        </div>
                        <div class="sim-content-elite">
                            <div class="sim-header-line"></div>
                            <div class="sim-card-mockup">
                                <div class="sim-card-bar-elite"></div>
                                <div class="sim-card-text"></div>
                                <div class="sim-card-text"></div>
                                <div class="sim-card-text sim-card-text--short"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 08. Micro-Botonería de Precisión (Acciones Quirúrgicas) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">08. Catálogo de Botonería Táctica</h5>
            
            <div class="row g-3 mb-4">
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Primario</span><code class="ficha-clase-elite">--primary</code></div>
                        <div class="ficha-demo-elite"><button class="btn-elite btn-elite--sm btn-elite--primary" data-bs-toggle="tooltip" title="Acción principal">BUSCAR</button></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Éxito</span><code class="ficha-clase-elite">--success</code></div>
                        <div class="ficha-demo-elite"><button class="btn-elite btn-elite--sm btn-elite--success" data-bs-toggle="tooltip" title="Confirmar">OPERAR</button></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Peligro</span><code class="ficha-clase-elite">--danger</code></div>
                        <div class="ficha-demo-elite"><button class="btn-elite btn-elite--sm btn-elite--danger" data-bs-toggle="tooltip" title="Borrar">ELIMINAR</button></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Aviso</span><code class="ficha-clase-elite">--warning</code></div>
                        <div class="ficha-demo-elite"><button class="btn-elite btn-elite--sm btn-elite--warning" data-bs-toggle="tooltip" title="Alerta">AVISO</button></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Info</span><code class="ficha-clase-elite">--info</code></div>
                        <div class="ficha-demo-elite"><button class="btn-elite btn-elite--sm btn-elite--info" data-bs-toggle="tooltip" title="Info">DETALLES</button></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Acento</span><code class="ficha-clase-elite">--accent</code></div>
                        <div class="ficha-demo-elite"><button class="btn-elite btn-elite--sm btn-elite--accent" data-bs-toggle="tooltip" title="Acento">ÉLITE</button></div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Botonería Estándar</span></div>
                        <div class="ficha-demo-elite">
                            <button class="btn-elite btn-elite--sm btn-elite--primary">BOTÓN ESTÁNDAR</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Botonería Aero</span></div>
                        <div class="ficha-demo-elite bg-blade rounded-bottom-elite">
                            <button class="btn-elite btn-elite--sm btn-elite--aero">BOTÓN CRISTAL</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Botonería Blade</span></div>
                        <div class="ficha-demo-elite bg-blade-light rounded-bottom-elite">
                            <button class="btn-elite btn-elite--sm btn-elite--blade">EJECUTAR_ACCION</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 09. Sistemas de Carga (Skeletons de Precisión) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">09. Sistemas de Carga (Skeletons)</h5>
            <div class="row g-4">
                <div class="col-md-4">
                    <h6 class="small fw-black mb-3 text-secondary text-uppercase">Tipos de Estructura</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="ficha-tecnica-elite">
                                <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Avatar</span><code class="ficha-clase-elite">--circle</code></div>
                                <div class="ficha-demo-elite"><div class="skeleton-elite skeleton-elite--circle"></div></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="ficha-tecnica-elite">
                                <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Texto</span><code class="ficha-clase-elite">--text</code></div>
                                <div class="ficha-demo-elite"><div class="skeleton-elite skeleton-elite--text w-75"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <h6 class="small fw-black mb-3 text-secondary text-uppercase">Simulación de Carga Asíncrona</h6>
                    <div class="card-elite-depth p-4 shadow-lg border-0">
                        <div class="d-flex gap-4">
                            <div class="skeleton-elite skeleton-elite--rect size-120"></div>
                            <div class="flex-grow-1">
                                <div class="skeleton-elite skeleton-elite--text mb-3 w-40 h-18"></div>
                                <div class="skeleton-elite skeleton-elite--text w-100"></div>
                                <div class="skeleton-elite skeleton-elite--text w-100"></div>
                                <div class="skeleton-elite skeleton-elite--text w-80"></div>
                                <div class="d-flex gap-2 mt-4">
                                    <div class="skeleton-elite w-80 h-32 rounded-elite-lg"></div>
                                    <div class="skeleton-elite w-80 h-32 rounded-elite-lg"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 10. Micro-Indicadores de Carga (Eficiencia Mutante) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">10. Indicadores de Carga (Micro-Loaders)</h5>
            <div class="row g-4 align-items-center text-center">
                <div class="col-md-4">
                    <p class="small fw-bold text-muted mb-3">LOADER ESTÁNDAR</p>
                    <div class="loader-elite-orbital mx-auto"></div>
                </div>
                <div class="col-md-4 p-3 bg-blade rounded-elite">
                    <p class="small fw-bold text-white-50 mb-3">LOADER AERO-GLASS</p>
                    <div class="loader-elite-orbital mx-auto border-top-white border-left-aero"></div>
                </div>
                <div class="col-md-4 p-3 bg-blade-light rounded-elite">
                    <p class="small fw-bold text-accent mb-3">LOADER BLADE-PULSE</p>
                    <div class="loader-elite-pulsar mx-auto bg-accent"></div>
                </div>
            </div>
        </div>
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Mesh Rotate</span><code class="ficha-clase-elite">.loader-elite-mesh</code></div>
                        <div class="ficha-demo-elite"><div class="loader-elite-mesh"></div></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Geometric Wave</span><code class="ficha-clase-elite">.loader-elite-wave</code></div>
                        <div class="ficha-demo-elite">
                            <div class="loader-elite-wave">
                                <div class="loader-elite-wave-inner"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5 pt-4 border-top">
                <h6 class="small fw-bold mb-4 text-secondary text-uppercase">Integración en Botonería Táctica</h6>
                <div class="d-flex gap-3">
                    <button class="btn-elite btn-elite--primary" disabled>
                        <div class="loader-elite-orbital size-16 border-white"></div>
                        PROCESANDO...
                    </button>
                    <button class="btn-elite btn-elite--success">
                        GUARDAR CAMBIOS
                        <div class="loader-elite-pulsar size-8 bg-white"></div>
                    </button>
                </div>
            </div>
        </div>

        <!-- 11. Micro-Badges de Estatus (Píldoras) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">11. Catálogo de Estatus (Badges)</h5>
            <div class="row g-3">
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite text-center">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Éxito</span><code class="ficha-clase-elite">--success</code></div>
                        <div class="ficha-demo-elite"><span class="badge-elite badge-elite--success">ACTIVO</span></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite text-center">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Peligro</span><code class="ficha-clase-elite">--danger</code></div>
                        <div class="ficha-demo-elite"><span class="badge-elite badge-elite--danger">BLOQUEADO</span></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite text-center">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Aviso</span><code class="ficha-clase-elite">--warning</code></div>
                        <div class="ficha-demo-elite"><span class="badge-elite badge-elite--warning">PENDIENTE</span></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="ficha-tecnica-elite text-center">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Acento</span><code class="ficha-clase-elite">--accent</code></div>
                        <div class="ficha-demo-elite"><span class="badge-elite badge-elite--accent">PRESTIGIO</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 12. Notificaciones (Toasts de Precisión) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">12. Sistema de Notificaciones (Toasts)</h5>
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Glass Éxito</span><code class="ficha-clase-elite">--success</code></div>
                        <div class="ficha-demo-elite">
                            <div class="toast-elite toast-elite--success w-100">
                                <div class="loader-elite-pulsar"></div>
                                <div><strong class="d-block small text-dark">Operación Exitosa</strong><span class="small text-muted">Registro guardado.</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Glass Error</span><code class="ficha-clase-elite">--danger</code></div>
                        <div class="ficha-demo-elite">
                            <div class="toast-elite toast-elite--danger w-100">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                                <div><strong class="d-block small text-dark">Error Crítico</strong><span class="small text-muted">Fallo de conexión.</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 13. Data-Cards (Perfiles de Élite) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">13. Módulos de Información (Data Cards)</h5>
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Tarjeta de Perfil</span><code class="ficha-clase-elite">.card-profile-elite</code></div>
                        <div class="ficha-demo-elite d-block p-4">
                            <div class="card-profile-elite shadow-sm border-0">
                                <div class="d-flex align-items-center gap-4 mb-3">
                                    <div class="profile-avatar-wrapper-elite">
                                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-primary bg-light rounded-circle p-2">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                    <div><h6 class="mb-1 fw-bold">Ing. Ricardo</h6><div class="badge-elite badge-elite--accent">ADMIN MASTER</div></div>
                                </div>
                                <p class="text-muted small mb-4">Responsable de la dirección técnica y arquitectura final del ecosistema Elite UI.</p>
                                <div class="d-flex gap-2"><button class="btn-elite btn-elite--sm btn-elite--primary">GESTIONAR</button><button class="btn-elite btn-elite--sm btn-elite--outline">MENSAJE</button></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Carga de Perfil</span><code class="ficha-clase-elite">.skeleton-elite</code></div>
                        <div class="ficha-demo-elite d-block p-4">
                            <div class="card-profile-elite shadow-sm border-0">
                                <div class="d-flex align-items-center gap-4 mb-3">
                                    <div class="skeleton-elite skeleton-elite--circle size-60"></div>
                                    <div class="flex-grow-1"><div class="skeleton-elite skeleton-elite--text mb-2 w-80"></div><div class="skeleton-elite skeleton-elite--text w-80"></div></div>
                                </div>
                                <div class="skeleton-elite skeleton-elite--text w-100 mb-2"></div><div class="skeleton-elite skeleton-elite--text w-75"></div>
                                <div class="d-flex gap-2 mt-4"><div class="skeleton-elite w-80 h-30 rounded-elite-lg"></div><div class="skeleton-elite w-80 h-30 rounded-elite-lg"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 14. Historial (Timeline Académico) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">14. Gestión de Historial (Timeline)</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Timeline Académico</span><code class="ficha-clase-elite">.timeline-elite</code></div>
                        <div class="ficha-demo-elite d-block px-4 py-3">
                            <div class="timeline-elite">
                                <div class="timeline-item-elite">
                                    <div class="timeline-date-elite">20 ABRIL, 2026</div><h6 class="fw-bold mb-1">Matriculación Completada</h6>
                                    <p class="text-muted small mb-2">El aspirante ha superado todos los filtros institucionales.</p>
                                    <span class="badge-elite badge-elite--success">TOTALMENTE OPERATIVO</span>
                                </div>
                                <div class="timeline-item-elite">
                                    <div class="timeline-date-elite">15 MARZO, 2026</div><h6 class="fw-bold mb-1">Evaluación de Aptitud</h6>
                                    <p class="text-muted small mb-2">Resultados sobresalientes en la arquitectura de datos.</p>
                                    <div class="badge-elite badge-elite--accent">PRESTIGIO ALCANZADO</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 15. Segmented Controls (Tabuladores Tácticos) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">15. Segmented Controls (Tabs Tácticos)</h5>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Triple Selector</span><code class="ficha-clase-elite">.segmented-control-elite</code></div>
                        <div class="ficha-demo-elite">
                            <div class="segmented-control-elite">
                                <input type="radio" name="seg_cat_fin" id="sf1" checked><label for="sf1">DIARIO</label>
                                <input type="radio" name="seg_cat_fin" id="sf2"><label for="sf2">MENSUAL</label>
                                <input type="radio" name="seg_cat_fin" id="sf3"><label for="sf3">ANUAL</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 16. Elite Dropzone (Gestión Doc.) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">16. Control de Carga (Dropzone)</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Cargador de Docs</span><code class="ficha-clase-elite">.dropzone-elite</code></div>
                        <div class="ficha-demo-elite d-block p-4">
                            <div class="dropzone-elite">
                                <div class="dropzone-icon-elite"><svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg></div>
                                <div class="text-center">
                                    <h6 class="fw-bold mb-1">Arrastre sus documentos aquí</h6>
                                    <p class="small text-muted mb-3">Soporta: <strong>PDF, JPG, PNG</strong> (Máx 10MB)</p>
                                    <button class="btn-elite btn-elite--sm btn-elite--outline">EXPLORAR ARCHIVOS</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 17. Navegación Aero (Glassmorphism) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">17. Navegación Aero (Glassmorphism Floating)</h5>
            <div class="row">
                <div class="col-md-5">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Modelo Aero-Glass</span><code class="ficha-clase-elite">.nav-aero-elite</code></div>
                        <div class="ficha-demo-elite d-block p-4 bg-blade rounded-bottom-elite">
                            <div class="nav-aero-elite">
                                <div class="aero-item-elite active">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                    Dashboard
                                </div>
                                <div class="aero-item-wrapper-elite">
                                    <div class="aero-item-elite">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        Comunidad
                                    </div>
                                    <div class="aero-submenu-elite">
                                        <div class="aero-subitem-elite">Estudiantes</div>
                                        <div class="aero-subitem-elite">Padres de Familia</div>
                                        <div class="aero-subitem-elite">Egresados</div>
                                    </div>
                                </div>
                                <div class="aero-item-wrapper-elite">
                                    <div class="aero-item-elite">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        Ajustes
                                    </div>
                                    <div class="aero-submenu-elite">
                                        <div class="aero-subitem-elite">Perfil Institucional</div>
                                        <div class="aero-subitem-elite">Seguridad ADN</div>
                                        <div class="aero-subitem-elite">Preferencias</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 18. Navegación Blade (High-Density) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">18. Navegación Blade (High-Density Precision)</h5>
            <div class="row">
                <div class="col-md-5">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Modelo Blade-Industrial</span><code class="ficha-clase-elite">.nav-blade-elite</code></div>
                        <div class="ficha-demo-elite d-block p-4 bg-elite-light rounded-bottom-elite">
                            <div class="nav-blade-elite">
                                <div class="blade-header-elite">Módulos Administrativos</div>
                                <div class="blade-item-wrapper-elite">
                                    <a href="#" class="blade-item-elite active">
                                        <div class="blade-icon-elite">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                        </div>
                                        Control de Notas
                                    </a>
                                    <div class="blade-submenu-elite">
                                        <a href="#" class="blade-subitem-elite">Planillas Mensuales</a>
                                        <a href="#" class="blade-subitem-elite">Consolidados Finales</a>
                                    </div>
                                </div>
                                <div class="blade-item-wrapper-elite">
                                    <a href="#" class="blade-item-elite">
                                        <div class="blade-icon-elite">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        Horarios Khronos
                                    </a>
                                    <div class="blade-submenu-elite">
                                        <a href="#" class="blade-subitem-elite">Mañana</a>
                                        <a href="#" class="blade-subitem-elite">Tarde</a>
                                        <a href="#" class="blade-subitem-elite">Nocturna</a>
                                    </div>
                                </div>
                                <div class="blade-header-elite">Gestión de Personal</div>
                                <div class="blade-item-wrapper-elite">
                                    <a href="#" class="blade-item-elite">
                                        <div class="blade-icon-elite">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        </div>
                                        Talento Humano
                                    </a>
                                    <div class="blade-submenu-elite">
                                        <a href="#" class="blade-subitem-elite">Docentes</a>
                                        <a href="#" class="blade-subitem-elite">Administrativos</a>
                                        <a href="#" class="blade-subitem-elite">Servicios</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 19. Elite Selectors (Listas Desplegables) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">19. Selectores de Precisión (Custom Select)</h5>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Modelo Estándar</span><code class="ficha-clase-elite">.select-elite</code></div>
                        <div class="ficha-demo-elite d-block p-4">
                            <div class="select-elite-wrapper">
                                <select class="select-elite">
                                    <option value="" disabled selected>Seleccione un módulo...</option>
                                    <option value="1">Gestión de Personal</option>
                                    <option value="2">Configuración Maestro</option>
                                    <option value="3">Auditoría de Sistemas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Modelo Aero-Glass</span><code class="ficha-clase-elite">.select-elite--aero</code></div>
                        <div class="ficha-demo-elite d-block p-4 bg-blade rounded-bottom-elite">
                            <div class="select-elite-wrapper">
                                <select class="select-elite select-elite--aero">
                                    <option value="" disabled selected>Filtro de Seguridad...</option>
                                    <option value="1">Nivel Alto (Cifrado)</option>
                                    <option value="2">Nivel Medio (Estándar)</option>
                                    <option value="3">Nivel Bajo (Invitado)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Modelo Blade-Industrial</span><code class="ficha-clase-elite">.select-elite--blade</code></div>
                        <div class="ficha-demo-elite d-block p-4 bg-blade-light rounded-bottom-elite">
                            <div class="select-elite-wrapper">
                                <select class="select-elite select-elite--blade">
                                    <option value="" disabled selected>EJECUTAR PROTOCOLO...</option>
                                    <option value="1">ACCESO NIVEL 1</option>
                                    <option value="2">ACCESO NIVEL 2</option>
                                    <option value="3">ACCESO MAESTRO</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ficha-tecnica-elite">
                        <div class="ficha-meta-elite"><span class="ficha-nombre-elite">Modelo Aurora-Glow</span><code class="ficha-clase-elite">.select-elite--aurora</code></div>
                        <div class="ficha-demo-elite d-block p-4">
                            <div class="select-elite-wrapper">
                                <select class="select-elite select-elite--aurora">
                                    <option value="" disabled selected>Inspiración Visual...</option>
                                    <option value="1">Paleta Prestigio</option>
                                    <option value="2">Paleta Nobleza</option>
                                    <option value="3">Paleta Vanguardia</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 20. Elite Toggles (Interruptores de Estado) -->
        <div class="component-section-elite">
            <h5 class="section-title-elite">20. Interruptores de Estado (Toggles)</h5>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <p class="small fw-bold text-muted mb-3 text-uppercase">Modelo Estándar</p>
                    <label class="switch-elite">
                        <input type="checkbox" checked>
                        <span class="switch-slider"></span>
                    </label>
                </div>
                <div class="col-md-4 p-3 bg-blade rounded-elite">
                    <p class="small fw-bold text-white-50 mb-3 text-uppercase">Modelo Aero-Glass</p>
                    <label class="switch-elite">
                        <input type="checkbox" checked>
                        <span class="switch-slider switch-slider--aero"></span>
                    </label>
                </div>
                <div class="col-md-4 p-3 bg-blade-light rounded-elite">
                    <p class="small fw-bold text-accent mb-3 text-uppercase">Modelo Blade-Tech</p>
                    <label class="switch-elite">
                        <input type="checkbox" checked>
                        <span class="switch-slider switch-slider--blade"></span>
                    </label>
                </div>
            </div>
        </div>

    </div>

    <!-- Scripts Moduladores -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/elite_showroom.js?v=9.2.1"></script>
</body>
</html>

