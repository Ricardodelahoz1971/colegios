<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $db->beginTransaction();

    // Update Question 991 (5 + 5) to Mathematics Grado 1º DBA 1 (id: 56)
    $stmt1 = $db->prepare("UPDATE eval_preguntas SET aprendizaje_id = 56 WHERE id = 991");
    $stmt1->execute();
    echo "Pregunta 991 actualizada. Filas afectadas: " . $stmt1->rowCount() . "\n";

    // Update Question 992 (10 * 3) to Mathematics Grado 2º DBA 1 (id: 66)
    $stmt2 = $db->prepare("UPDATE eval_preguntas SET aprendizaje_id = 66 WHERE id = 992");
    $stmt2->execute();
    echo "Pregunta 992 actualizada. Filas afectadas: " . $stmt2->rowCount() . "\n";

    // Update Question 993 (Aritmética) to Mathematics Grado 1º DBA 1 (id: 56)
    $stmt3 = $db->prepare("UPDATE eval_preguntas SET aprendizaje_id = 56 WHERE id = 993");
    $stmt3->execute();
    echo "Pregunta 993 actualizada. Filas afectadas: " . $stmt3->rowCount() . "\n";

    $db->commit();
    echo "Actualización de base de datos completada con éxito.\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
