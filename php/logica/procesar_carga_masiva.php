<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
proteccion_extrema();
// PHP/LOGICA/PROCESAR_CARGA_MASIVA.PHP - MOTOR DE INGESTIÓN ELITE v1.0
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
require_once '../db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Acceso no permitido.');
    }

    if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo o archivo no seleccionado.');
    }

    $fileTmpPath = $_FILES['archivo_csv']['tmp_name'];
    $handle = fopen($fileTmpPath, 'r');
    if (!$handle) {
        throw new Exception('No se pudo abrir el archivo CSV.');
    }

    // 1. CARGAR MAPA DE CURSOS Y MAPA DE ESTUDIANTES PARA BÚSQUEDA RÁPIDA (Evitar consultas N+1 en loop)
    $cursos_map = [];
    $stmt_stmt_c = $db->prepare("SELECT id, nombre_curso FROM cursos"); $stmt_stmt_c->execute(); $stmt_c = $stmt_stmt_c;
    while ($c = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
        $cursos_map[strtoupper(trim($c['nombre_curso']))] = $c['id'];
    }

    $estudiantes_existentes = [];
    $stmt_est = $db->prepare("SELECT identificacion FROM estudiantes");
    $stmt_est->execute();
    while ($id_existente = $stmt_est->fetchColumn()) {
        $estudiantes_existentes[trim($id_existente)] = true;
    }

    // 2. PREPARAR ESTADÍSTICAS
    $exitos = 0;
    $duplicados = 0;
    $errores_curso = 0;
    $total_filas = 0;

    // 3. PROCESAR FILAS (Separador Punto y Coma ;)
    // Saltamos la cabecera
    fgetcsv($handle, 2000, ';');

    // Iniciamos transacción para máxima velocidad y seguridad
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO estudiantes (identificacion, nombre, apellido, curso_id, curso, tipo_documento, rh, genero, email, celular, es_antiguo, promedio) 
                          VALUES (:id, :nom, :ape, :cid, :cur, :tdoc, :rh, :gen, :mail, :cel, :ant, :pro)");

    while (($data = fgetcsv($handle, 2000, ';')) !== FALSE) {
        if (count($data) < 3) continue; // Fila vacía o corrupta
        $total_filas++;

        $identificacion = trim($data[0]);
        $nombre = trim($data[1]);
        $apellido = trim($data[2]);
        $tdoc = trim($data[3] ?? 'TI');
        $rh = trim($data[4] ?? 'O+');
        $gen = strtoupper(trim($data[5] ?? 'M'));
        $email = trim($data[6] ?? '');
        $celular = trim($data[7] ?? '');
        $nom_curso = strtoupper(trim($data[8] ?? 'SIN ASIGNAR'));
        $es_antiguo = intval($data[9] ?? 0);
        $promedio = floatval($data[10] ?? 0);

        // a. Verificar Duplicados en memoria RAM usando la HashTable (Evita N+1 SELECTs)
        if (isset($estudiantes_existentes[$identificacion])) {
            $duplicados++;
            continue; // Saltamos si ya existe (Opción: Ignorar)
        }

        // Registrar en caliente en el mapa de duplicados para evitar repeticiones dentro del mismo archivo
        $estudiantes_existentes[$identificacion] = true;

        // b. Mapear Curso
        $curso_id = $cursos_map[$nom_curso] ?? null;
        if ($curso_id === null && $nom_curso !== 'SIN ASIGNAR') {
            $errores_curso++;
            // Podríamos decidir si fallar o ponerle "SIN ASIGNAR"
            // Por ahora, le asignamos null/0 si no existe
            $curso_id = null;
        }

        // c. Ejecutar Inserción
        $stmt->bindValue(':id', $identificacion, PDO::PARAM_STR);
        $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':ape', $apellido, PDO::PARAM_STR);
        $stmt->bindValue(':cid', $curso_id, $curso_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':cur', $nom_curso, PDO::PARAM_STR);
        $stmt->bindValue(':tdoc', $tdoc, PDO::PARAM_STR);
        $stmt->bindValue(':rh', $rh, PDO::PARAM_STR);
        $stmt->bindValue(':gen', $gen, PDO::PARAM_STR);
        $stmt->bindValue(':mail', $email, PDO::PARAM_STR);
        $stmt->bindValue(':cel', $celular, PDO::PARAM_STR);
        $stmt->bindValue(':ant', $es_antiguo, PDO::PARAM_INT);
        $stmt->bindValue(':pro', $promedio, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $exitos++;
        }
    }

    $db->commit();
    fclose($handle);

    echo json_encode([
        'status' => 'success',
        'message' => 'Proceso de carga finalizado.',
        'stats' => [
            'total' => $total_filas,
            'exitos' => $exitos,
            'duplicados' => $duplicados,
            'errores_curso' => $errores_curso
        ]
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Fallo en la Bóveda: ' . $e->getMessage()
    ]);
} finally {
    // Protección atómica finalizada
}

