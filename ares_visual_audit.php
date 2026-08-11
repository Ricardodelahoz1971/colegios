<?php
include_once 'php/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ARES | Auditoría Visual de Catálogo</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
        :root {
            --el-panel-bg: rgba(255, 255, 255, 0.05);
            --el-glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            background: #0f172a;
            color: #f8fafc;
            font-family: var(--el-font-institutional), sans-serif;

            padding: 40px;
            margin: 0;
        }

        .audit-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-elite {
            margin-bottom: 40px;
            border-bottom: 1px solid var(--el-glass-border);
            padding-bottom: 20px;
        }

        .grid-catalog {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }

        .card-dba {
            background: var(--el-panel-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--el-glass-border);
            border-radius: 24px; /* Radio de Prestigio */
            padding: 24px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-dba:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--el-primary, #ff4500);
        }

        .badge-grado {
            display: inline-block;
            height: 24px;
            padding: 0 12px;
            border-radius: 12px;
            background: var(--el-primary, #ff4500);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .dba-num {
            color: var(--el-primary, #ff4500);
            font-weight: 800;
            margin-right: 8px;
        }

        .enunciado {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .evidencias-list {
            border-top: 1px solid var(--el-glass-border);
            padding-top: 16px;
            font-size: 13px;
            color: #94a3b8;
        }

        .evidencia-item {
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .evidencia-item::before {
            content: "→";
            color: var(--el-primary, #ff4500);
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-pill {
            background: rgba(0, 0, 0, 0.3);
            height: 44px; /* Métrica 44px */
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-radius: 12px;
            border: 1px solid var(--el-glass-border);
        }
        .area-label {
            font-size: 12px;
            color: var(--el-text-secondary, #94a3b8);
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="audit-container">
        <header class="header-elite">
            <h1>🏛️ Auditoría Visual ARES</h1>
            <p>Visualización de Catálogo MEN | Vitrina 06 Compliant</p>
            
            <div class="stats-bar">
                <?php
                $sql_totales = "SELECT 
                    (SELECT COUNT(*) FROM ares_catalogo_aprendizajes) as a,
                    (SELECT COUNT(*) FROM ares_catalogo_evidencias) as e";
                $stmt_t = $db->prepare($sql_totales);
                $stmt_t->execute();
                $totales = $stmt_t->fetch();
                ?>
                <div class="stat-pill"><strong>Aprendizajes:</strong> &nbsp; <?php echo $totales['a']; ?></div>
                <div class="stat-pill"><strong>Evidencias:</strong> &nbsp; <?php echo $totales['e']; ?></div>
            </div>
        </header>

        <div class="grid-catalog">
            <?php
            // Mostrar una muestra de cada área para auditoría visual
            $sql_sample = "SELECT ap.*, ar.nombre_area 
                           FROM ares_catalogo_aprendizajes ap
                           JOIN areas ar ON ap.area_id = ar.id
                           GROUP BY ap.area_id, ap.grado
                           ORDER BY ap.area_id, CAST(ap.grado AS INTEGER)
                           LIMIT 20";
            $stmt_s = $db->prepare($sql_sample);
            $stmt_s->execute();
            while($row = $stmt_s->fetch(PDO::FETCH_ASSOC)) {
                echo "<div class='card-dba'>";
                echo "<span class='badge-grado'>{$row['grado']}</span>";
                echo "<div class='area-label'>{$row['nombre_area']}</div>";
                echo "<div class='enunciado'><span class='dba-num'>DBA #{$row['num_dba']}</span>{$row['enunciado']}</div>";
                
                // Evidencias (Limit 3 para auditoría)
                $evs = $db->prepare("SELECT texto FROM ares_catalogo_evidencias WHERE aprendizaje_id = ? LIMIT 3");
                $evs->execute([$row['id']]);
                echo "<div class='evidencias-list'>";
                while($ev = $evs->fetchColumn()) {
                    echo "<div class='evidencia-item'>$ev</div>";
                }
                echo "</div>";
                echo "</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>
