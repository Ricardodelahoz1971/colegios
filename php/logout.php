<?php
declare(strict_types=1);
// 1. DESPERTAR LA MEMORIA
// Aunque queremos destruirla, siempre tienes que "despertar" la sesión a la que te vas a referir, o PHP no sabrá qué destruir.
session_start();
// 2. VACIAR LOS BOLSILLOS
// Borramos variables específicas como el nombre o el ID.
session_unset();
// 3. QUEMAR LA MOCHILA
// Destruimos la sesión por completo del disco duro del servidor. ¡Ya nadie podrá recuperarla!
session_destroy();
// 4. ECHAR AL USUARIO HACIA AFUERA
// Lo redireccionamos amablemente a la puerta principal.
header("Location: ../index.php");
// Matamos el proceso para asegurar que el redireccionamiento fluya.
exit();
?>
