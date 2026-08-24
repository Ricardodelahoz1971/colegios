<?php
declare(strict_types=1);

require_once __DIR__ . '/../../php/db.php';

echo "=== PASO 1: LIMPIANDO TABLAS GEOGRÁFICAS ===\n";
$db->exec("SET FOREIGN_KEY_CHECKS = 0");
$db->exec("TRUNCATE TABLE cat_municipios");
$db->exec("TRUNCATE TABLE cat_departamentos");
$db->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "Tablas limpiadas con éxito.\n\n";

// ============================================
// PASO 2: COLOMBIA (pais_id = 1)
// ============================================
echo "=== PASO 2: SEMBRANDO COLOMBIA COMPLETA ===\n";

$colombia = [
    ['Amazonas', ['Leticia', 'Puerto Nariño', 'El Encanto', 'La Chorrera', 'La Pedrera', 'Mirití-Paraná', 'Puerto Arica', 'Puerto Santander', 'Tarapacá']],
    ['Antioquia', ['Medellín', 'Bello', 'Itagüí', 'Envigado', 'Apartadó', 'Turbo', 'Rionegro', 'Caldas', 'Sabaneta', 'Copacabana', 'La Ceja', 'Marinilla', 'Sonsón', 'Yarumal', 'Carepa', 'Chigorodó', 'Caucasia', 'Santa Fe de Antioquia', 'Guarne', 'El Carmen de Viboral']],
    ['Arauca', ['Arauca', 'Arauquita', 'Cravo Norte', 'Fortul', 'Puerto Rondón', 'Saravena', 'Tame']],
    ['Atlántico', ['Barranquilla', 'Soledad', 'Malambo', 'Puerto Colombia', 'Sabanalarga', 'Sabanagrande', 'Galapa', 'Santo Tomás', 'Juan de Acosta', 'Baranoa', 'Palmar de Varela', 'Ponedera']],
    ['Bogotá D.C.', ['Bogotá D.C.', 'Usaquén', 'Chapinero', 'Santa Fe', 'San Cristóbal', 'Usme', 'Tunjuelito', 'Bosa', 'Kennedy', 'Fontibón', 'Engativá', 'Suba', 'Barrios Unidos', 'Teusaquillo', 'Los Mártires', 'Antonio Nariño', 'Puente Aranda', 'La Candelaria', 'Rafael Uribe Uribe', 'Ciudad Bolívar', 'Sumapaz']],
    ['Bolívar', ['Cartagena de Indias', 'Magangué', 'Turbaco', 'Arjona', 'El Carmen de Bolívar', 'Santa Cruz de Mompox', 'El Bagre', 'María La Baja', 'San Juan Nepomuceno', 'San Jacinto', 'Calamar', 'Santa Rosa del Sur']],
    ['Boyacá', ['Tunja', 'Duitama', 'Sogamoso', 'Chiquinquirá', 'Paipa', 'Moniquirá', 'Miraflores', 'Garagoa', 'Villa de Leyva', 'Puerto Boyacá', 'Nobsa', 'Samacá']],
    ['Caldas', ['Manizales', 'Villamaría', 'Chinchiná', 'La Dorada', 'Riosucio', 'Salamina', 'Aguadas', 'Pácora', 'Neira', 'Anserma', 'Supía', 'Pensilvania']],
    ['Caquetá', ['Florencia', 'San Vicente del Caguán', 'El Doncello', 'Puerto Rico', 'Belén de los Andaquíes', 'Cartagena del Chairá', 'Curillo', 'Solano', 'Valparaíso', 'Morelia']],
    ['Casanare', ['Yopal', 'Aguazul', 'Paz de Ariporo', 'Villanueva', 'Tauramena', 'Monterrey', 'Maní', 'Pore', 'Trinidad', 'Hato Corozal', 'Orocué']],
    ['Cauca', ['Popayán', 'Santander de Quilichao', 'Puerto Tejada', 'Miranda', 'Piendamó', 'Cajibío', 'Silvia', 'Timbío', 'Patía (El Bordo)', 'Guachené', 'Corinto', 'Caloto']],
    ['Cesar', ['Valledupar', 'Aguachica', 'Agustín Codazzi', 'La Paz', 'San Alberto', 'Pueblo Bello', 'Curumaní', 'Gamarra', 'Río de Oro', 'Bosconia', 'La Jagua de Ibirico', 'El Paso', 'Chiriguaná']],
    ['Chocó', ['Quibdó', 'Istmina', 'Tadó', 'Condoto', 'Juradó', 'Bahía Solano', 'Nuquí', 'Acandí', 'Riosucio', 'El Carmen de Atrato', 'Bajo Baudó']],
    ['Córdoba', ['Montería', 'Cereté', 'Tierralta', 'Sahagún', 'Lorica', 'Montelíbano', 'Planeta Rica', 'Ciénaga de Oro', 'San Carlos', 'San Andrés de Sotavento', 'San Antero', 'San Pelayo', 'Chinú', 'Pueblo Nuevo', 'Ayapel', 'Moñitos', 'Puerto Escondido']],
    ['Cundinamarca', ['Soacha', 'Zipaquirá', 'Facatativá', 'Chía', 'Girardot', 'Cajicá', 'Madrid', 'Mosquera', 'Funza', 'Fusagasugá', 'Sopó', 'Tocancipá', 'Cota', 'La Calera', 'Ubate', 'Gachancipá', 'Tabio', 'Tenjo', 'Silvania']],
    ['Guainía', ['Inírida', 'Barrancominas', 'Cacahual', 'La Guadalupe', 'Mapiripana', 'Morichal', 'Pana Pana', 'Puerto Colombia', 'San Felipe']],
    ['Guaviare', ['San José del Guaviare', 'Calamar', 'El Retorno', 'Miraflores']],
    ['Huila', ['Neiva', 'Pitalito', 'Garzón', 'La Plata', 'Campoalegre', 'Rivera', 'Gigante', 'Palermo', 'Aipe', 'San Agustín', 'Timaná', 'Algeciras']],
    ['La Guajira', ['Riohacha', 'Maicao', 'Uribia', 'San Juan del Cesar', 'Fonseca', 'Barrancas', 'Hatonuevo', 'Albania', 'Dibulla', 'Manaure', 'Villanueva']],
    ['Magdalena', ['Santa Marta', 'Ciénaga', 'Fundación', 'El Banco', 'Pivijay', 'Aracataca', 'Plato', 'Ariguaní', 'Cerro de San Antonio', 'Sitio Nuevo', 'Zona Bananera']],
    ['Meta', ['Villavicencio', 'Acacías', 'Granada', 'Puerto López', 'San Martín', 'Restrepo', 'Cumaral', 'Puerto Gaitán', 'Fuente de Oro', 'Castilla La Nueva']],
    ['Nariño', ['Pasto', 'Tumaco', 'Ipiales', 'Túquerres', 'La Unión', 'Barbacoas', 'Samaniego', 'Pupiales', 'Cumbal', 'Guaitarilla', 'Sandoná']],
    ['Norte de Santander', ['Cúcuta', 'Ocaña', 'Pamplona', 'Los Patios', 'Villa del Rosario', 'Chinácota', 'Ábrego', 'El Zulia', 'Sardinata', 'Tibú', 'Toledo']],
    ['Putumayo', ['Mocoa', 'Puerto Asís', 'Orito', 'Valle del Guamuez (La Hormiga)', 'San Miguel', 'Sibundoy', 'Puerto Caicedo', 'Villagarzón', 'Puerto Leguízamo']],
    ['Quindío', ['Armenia', 'Calarcá', 'La Tebaida', 'Montenegro', 'Quimbaya', 'Salento', 'Buenavista', 'Circasia', 'Córdoba', 'Filandia', 'Génova', 'Pijao']],
    ['Risaralda', ['Pereira', 'Dosquebradas', 'Santa Rosa de Cabal', 'La Virginia', 'Belén de Umbría', 'Apía', 'Guática', 'Mistrató', 'Quinchía', 'Marsella', 'Santuario']],
    ['San Andrés y Providencia', ['San Andrés', 'Providencia', 'Santa Catalina']],
    ['Santander', ['Bucaramanga', 'Floridablanca', 'Girón', 'Piedecuesta', 'Barrancabermeja', 'San Gil', 'Vélez', 'Málaga', 'Socorro', 'Barbosa', 'Lebrija', 'Zapatoca', 'Charalá', 'Cimitarra']],
    ['Sucre', ['Sincelejo', 'Corozal', 'Sampués', 'Santiago de Tolú', 'San Marcos', 'Coveñas', 'San Onofre', 'Sucre', 'Tolú Viejo', 'Majagual', 'San Pedro', 'Sincé']],
    ['Tolima', ['Ibagué', 'Espinal', 'Melgar', 'Honda', 'Líbano', 'Fresno', 'Mariquita', 'Rovira', 'Purificación', 'Chaparral', 'Guamo', 'Flandes', 'Armero Guayabal']],
    ['Valle del Cauca', ['Cali', 'Palmira', 'Buenaventura', 'Tuluá', 'Cartago', 'Yumbo', 'Jamundí', 'Buga (Guadalajara de Buga)', 'Candelaria', 'Pradera', 'Florida', 'Zarzal', 'Roldanillo', 'Sevilla', 'Caicedonia', 'Dagua', 'Guacarí', 'El Cerrito']],
    ['Vaupés', ['Mitú', 'Carurú', 'Taraira', 'Papunaua', 'Yavaraté', 'Pacoa']],
    ['Vichada', ['Puerto Carreño', 'Cumaribo', 'La Primavera', 'Santa Rosalía']]
];

$stmtDept = $db->prepare("INSERT INTO cat_departamentos (pais_id, nombre, codigo_dane) VALUES (:pais_id, :nombre, :codigo)");
$stmtMuni = $db->prepare("INSERT INTO cat_municipios (departamento_id, nombre) VALUES (:dept_id, :nombre)");

$contadorCol = 0;
foreach ($colombia as $indice => $departamento) {
    $codigoDane = str_pad((string)($indice + 1), 2, '0', STR_PAD_LEFT);
    
    $stmtDept->execute([
        ':pais_id' => 1,
        ':nombre'  => $departamento[0],
        ':codigo'  => $codigoDane
    ]);
    $deptId = (int)$db->lastInsertId();
    
    foreach ($departamento[1] as $municipio) {
        $stmtMuni->execute([
            ':dept_id' => $deptId,
            ':nombre'  => $municipio
        ]);
        $contadorCol++;
    }
}
echo "Colombia sembrada: " . count($colombia) . " departamentos/distritos, {$contadorCol} municipios.\n\n";

// ============================================
// PASO 3: VENEZUELA (pais_id = 2)
// ============================================
echo "=== PASO 3: SEMBRANDO VENEZUELA COMPLETA (24 ENTIDADES) ===\n";

$venezuela = [
    ['Amazonas', ['Puerto Ayacucho (Atures)', 'San Fernando de Atabapo', 'Isla Ratón (Autana)', 'Maroa', 'San Juan de Manapiare', 'San Carlos de Río Negro', 'Guainía']],
    ['Anzoátegui', ['Barcelona (Simón Bolívar)', 'Anaco', 'El Tigre (Simón Rodríguez)', 'Lechería (Diego Bautista Urbaneja)', 'Puerto La Cruz (Juan Antonio Sotillo)', 'Guanta', 'Cantaura (Pedro María Freites)', 'Aragua de Barcelona', 'Píritu', 'San José de Guanipa', 'Clarines (Manuel Ezequiel Bruzual)', 'Pariaguán (Francisco de Miranda)']],
    ['Apure', ['San Fernando de Apure', 'Achaguas', 'Biruaca', 'Guasdualito (Páez)', 'Elorza (Rómulo Gallegos)', 'San Juan de Payara (Pedro Camejo)', 'Bruzual (Muñoz)']],
    ['Aragua', ['Maracay (Girardot)', 'Cagua (Sucre)', 'Turmero (Santiago Mariño)', 'La Victoria (José Félix Ribas)', 'Villa de Cura (Zamora)', 'El Limón (Mario Briceño Iragorry)', 'Palo Negro (Libertador)', 'Ocumare de la Costa de Oro', 'Santa Rita (Francisco Linares Alcántara)', 'San Mateo (Bolívar)', 'San Casimiro', 'San Sebastián', 'Colonia Tovar']],
    ['Barinas', ['Barinas', 'Barinitas (Bolívar)', 'Socopó (Antonio José de Sucre)', 'Santa Bárbara (Ezequiel Zamora)', 'Sabaneta (Alberto Arvelo Torrealba)', 'Barrancas (Cruz Paredes)', 'Ciudad Bolivia (Pedraza)', 'Obispos', 'Arismendi']],
    ['Bolívar', ['Ciudad Bolívar (Angostura del Orinoco)', 'Ciudad Guayana / Puerto Ordaz (Caroní)', 'Upata (Piar)', 'Caicara del Orinoco (Cedeño)', 'El Callao', 'Tumeremo (Domingo Sifontes)', 'Guasipati (Roscio)', 'Santa Elena de Uairén (Gran Sabana)', 'Maripa (Sucre)', 'El Palmar (Padre Pedro Chien)']],
    ['Carabobo', ['Valencia', 'Puerto Cabello', 'Guacara', 'Mariara (Diego Ibarra)', 'San Diego', 'Los Guayos', 'Naguanagua', 'Tocuyito (Libertador)', 'Montalbán', 'Bejuma', 'Morón (Juan José Mora)', 'San Joaquín', 'Miranda', 'Güigüe (Carlos Arvelo)']],
    ['Cojedes', ['San Carlos (Ezequiel Zamora)', 'Tinaquillo', 'El Baúl (Girardot)', 'Las Vegas (Rómulo Gallegos)', 'Libertad de Cojedes (Ricaurte)', 'Tinaco', 'El Pao (Pao de San Juan Bautista)', 'Macapo (Lima Blanco)', 'Cojedes (Anzoátegui)']],
    ['Delta Amacuro', ['Tucupita', 'Sierra Imataca (Casacoima)', 'Pedernales', 'Curiapo (Antonio Díaz)']],
    ['Distrito Capital', ['Caracas (Municipio Libertador)', 'Catia', 'El Valle', 'Antímano', 'Caricuao', 'La Vega', 'El Recreo', 'San Pedro', 'Sucre']],
    ['Falcón', ['Coro (Miranda)', 'Punto Fijo (Carirubana)', 'Churuguara (Federación)', 'Dabajuro', 'Pueblo Nuevo (Falcón)', 'La Vela de Coro (Colina)', 'Tucacas (José Laurencio Silva)', 'Chichiriviche (Monseñor Iturriza)', 'Puerto Cumarebo (Zamora)', 'Judibana (Los Taques)', 'Mene de Mauroa']],
    ['Guárico', ['San Juan de los Morros (Juan Germán Roscio)', 'Calabozo (Francisco de Miranda)', 'Valle de la Pascua (Leonardo Infante)', 'Zaraza (Pedro Zaraza)', 'Altagracia de Orituco (José Tadeo Monagas)', 'El Sombrero (Julián Mellado)', 'Chaguaramas', 'Camaguán', 'Tucupido (José Félix Ribas)', 'Santa María de Ipire']],
    ['La Guaira (Vargas)', ['La Guaira', 'Catia La Mar', 'Maiquetía', 'Macuto', 'Caraballeda', 'Carayaca', 'Naiguatá', 'Caruao (La Sabana)', 'Carlos Soublette', 'Urimare', 'El Junko']],
    ['Lara', ['Barquisimeto (Iribarren)', 'Cabudare (Palavecino)', 'Carora (Torres)', 'El Tocuyo (Morán)', 'Quíbor (Jiménez)', 'Sanare (Andrés Eloy Blanco)', 'Sarare (Simón Planas)', 'Duaca (Crespo)', 'Siquisique (Urdaneta)']],
    ['Mérida', ['Mérida (Libertador)', 'Ejido (Campo Elías)', 'El Vigía (Alberto Adriani)', 'Tovar', 'Bailadores (Rivas Dávila)', 'La Azulita (Andrés Bello)', 'Timotes (Miranda)', 'Mucuchíes (Rangel)', 'Santa Cruz de Mora (Antonio Pinto Salinas)', 'Tabay (Santos Marquina)', 'Lagunillas (Sucre)', 'Nueva Bolivia (Julio César Salas)']],
    ['Miranda', ['Los Teques (Guaicaipuro)', 'Guarenas (Plaza)', 'Guatire (Zamora)', 'Petare (Sucre)', 'Baruta', 'Chacao', 'El Hatillo', 'Ocumare del Tuy (Tomás Lander)', 'Charallave (Cristóbal Rojas)', 'Cúa (General Rafael Urdaneta)', 'Santa Teresa del Tuy (Independencia)', 'Higuerote (Brión)', 'Río Chico (Páez)', 'San Antonio de los Altos (Los Salias)']],
    ['Monagas', ['Maturín', 'Punta de Mata (Ezequiel Zamora)', 'Temblador (Libertador)', 'Caicara de Maturín (Cedeño)', 'Caripe', 'Caripito (Bolívar)', 'San Antonio de Capayacuar (Acosta)', 'Aragua de Maturín (Piar)', 'Uracoa', 'Barrancas del Orinoco (Sotillo)']],
    ['Nueva Esparta', ['La Asunción (Arismendi)', 'Porlamar (Mariño)', 'Pampatar (Maneiro)', 'Juan Griego (Marcano)', 'Valle del Espíritu Santo (García)', 'Punta de Piedras (Tubores)', 'Santa Ana (Gómez)', 'San Juan Bautista (Díaz)', 'Boca de Río (Península de Macanao)', 'San Pedro de Coche (Villalba)']],
    ['Portuguesa', ['Guanare', 'Acarigua (Páez)', 'Araure', 'Villa Bruzual (Turén)', 'Ospino', 'Píritu (Esteller)', 'San Rafael de Onoto', 'Biscucuy (Sucre)', 'Guanarito', 'Chabasquén (José Vicente de Unda)']],
    ['Sucre', ['Cumaná (Sucre)', 'Carúpano (Bermúdez)', 'Cumanacoa (Montes)', 'Irapa (Mariño)', 'Güiria (Valdez)', 'Río Caribe (Arismendi)', 'Marigüitar (Bolívar)', 'Cariaco (Ribero)', 'Araya (Cruz Salmerón Acosta)', 'San Antonio del Golfo (Mejía)', 'El Pilar (Benítez)']],
    ['Táchira', ['San Cristóbal', 'Táriba (Cárdenas)', 'Rubio (Junín)', 'La Grita (Jáuregui)', 'San Juan de Colón (Ayacucho)', 'San Antonio del Táchira (Bolívar)', 'Ureña (Pedro María Ureña)', 'Michelena', 'Palmira (Guásimos)', 'La Fría (García de Hevia)', 'Santa Ana del Táchira (Córdoba)', 'Capacho Nuevo (Independencia)', 'Capacho Viejo (Libertad)', 'El Piñal (Fernández Feo)', 'Abejales (Libertador)', 'La Tendida (Samuel Darío Maldonado)', 'Queniquea (Sucre)', 'Umuquena (San Judas Tadeo)']],
    ['Trujillo', ['Trujillo (Capital)', 'Valera', 'Boconó', 'Pampán', 'Betijoque (Rafael Rangel)', 'Sabana de Mendoza (Sucre)', 'Escuque', 'Carache', 'Chejendé (Candelaria)', 'Monay (Pampanito)', 'La Ceiba', 'Santa Ana (Pampán)']],
    ['Yaracuy', ['San Felipe', 'Yaritagua (Peña)', 'Chivacoa (Bruzual)', 'Cocorote', 'Nirgua', 'Aroa (Bolívar)', 'Guama (Sucre)', 'Urachiche', 'San Pablo (Arístides Bastidas)', 'Farriar (Veroes)', 'Marin (San Felipe)']],
    ['Zulia', ['Maracaibo', 'Cabimas', 'Ciudad Ojeda (Lagunillas)', 'San Francisco', 'Machiques de Perijá', 'Villa del Rosario (Rosario de Perijá)', 'Santa Rita', 'La Cañada de Urdaneta', 'San Carlos del Zulia (Colón)', 'Bachaquero (Valmore Rodríguez)', 'Mene Grande (Baralt)', 'Los Puertos de Altagracia (Miranda)', 'Encontrados (Catatumbo)', 'Santa Bárbara del Zulia', 'El Chivo (Francisco Javier Pulgar)', 'Sinamaica (Guajira / Páez)']]
];

$contadorVen = 0;
$indiceVen = 1;
foreach ($venezuela as $entidad) {
    $codigoEntidad = str_pad((string)$indiceVen, 2, '0', STR_PAD_LEFT);
    
    $stmtDept->execute([
        ':pais_id' => 2,
        ':nombre'  => $entidad[0],
        ':codigo'  => $codigoEntidad
    ]);
    $deptId = (int)$db->lastInsertId();
    
    foreach ($entidad[1] as $municipio) {
        $stmtMuni->execute([
            ':dept_id' => $deptId,
            ':nombre'  => $municipio
        ]);
        $contadorVen++;
    }
    $indiceVen++;
}
echo "Venezuela sembrada: 24 entidades federales, {$contadorVen} municipios.\n\n";

$s1 = $db->prepare("SELECT COUNT(*) FROM cat_departamentos"); $s1->execute();
$totalDept = (int)$s1->fetchColumn();

$s2 = $db->prepare("SELECT COUNT(*) FROM cat_municipios"); $s2->execute();
$totalMuni = (int)$s2->fetchColumn();

echo "=== RESUMEN FINAL DE ARQUITECTURA ===\n";
echo "Total departamentos/estados en BD: {$totalDept}\n";
echo "Total municipios en BD: {$totalMuni}\n";
echo "Colombia (id=1): 33 entidades con {$contadorCol} municipios.\n";
echo "Venezuela (id=2): 24 entidades con {$contadorVen} municipios.\n";
echo "Resto del Mundo (id > 2): 0 registros en departamentos (activa automáticamente modo texto libre Estado + Ciudad).\n";
echo "🚀 Siembra binacional completada con pureza y éxito!\n";
