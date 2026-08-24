<?php
declare(strict_types=1);

require_once __DIR__ . '/../../php/db.php';

echo "Iniciando instalación del Módulo Geográfico Global...\n";

// 1. Crear Tablas
$db->exec("
CREATE TABLE IF NOT EXISTS cat_paises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_iso VARCHAR(3) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    gentilicio VARCHAR(100) NOT NULL,
    indicativo VARCHAR(10) DEFAULT NULL,
    INDEX idx_pais_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cat_departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pais_id INT NOT NULL,
    codigo_dane VARCHAR(10) DEFAULT NULL,
    nombre VARCHAR(100) NOT NULL,
    FOREIGN KEY (pais_id) REFERENCES cat_paises(id) ON DELETE CASCADE,
    INDEX idx_departamento_pais (pais_id),
    INDEX idx_departamento_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cat_municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento_id INT NOT NULL,
    codigo_dane VARCHAR(10) DEFAULT NULL,
    nombre VARCHAR(100) NOT NULL,
    FOREIGN KEY (departamento_id) REFERENCES cat_departamentos(id) ON DELETE CASCADE,
    INDEX idx_municipio_departamento (departamento_id),
    INDEX idx_municipio_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo "Tablas creadas exitosamente.\n";

// 2. Sembrar Países
$paises = [
    [1, 'CO', 'Colombia', 'Colombiano/a', '+57'],
    [2, 'VE', 'Venezuela', 'Venezolano/a', '+58'],
    [3, 'EC', 'Ecuador', 'Ecuatoriano/a', '+593'],
    [4, 'PE', 'Perú', 'Peruano/a', '+51'],
    [5, 'XK', 'Kosovo', 'Kosovar', '+383'],
    [6, 'US', 'Estados Unidos', 'Estadounidense', '+1'],
    [7, 'ES', 'España', 'Español/a', '+34'],
    [8, 'MX', 'México', 'Mexicano/a', '+52'],
    [9, 'AR', 'Argentina', 'Argentino/a', '+54'],
    [10, 'CL', 'Chile', 'Chileno/a', '+56'],
    [11, 'BR', 'Brasil', 'Brasileño/a', '+55'],
    [12, 'PA', 'Panamá', 'Panameño/a', '+507'],
    [13, 'CR', 'Costa Rica', 'Costarricense', '+506'],
    [14, 'DO', 'República Dominicana', 'Dominicano/a', '+1809'],
    [15, 'BO', 'Bolivia', 'Boliviano/a', '+591'],
    [16, 'UY', 'Uruguay', 'Uruguayo/a', '+598'],
    [17, 'PY', 'Paraguay', 'Paraguayo/a', '+595'],
    [18, 'CU', 'Cuba', 'Cubano/a', '+53'],
    [19, 'GT', 'Guatemala', 'Guatemalteco/a', '+502'],
    [20, 'HN', 'Honduras', 'Hondureño/a', '+504'],
    [21, 'SV', 'El Salvador', 'Salvadoreño/a', '+503'],
    [22, 'NI', 'Nicaragua', 'Nicaragüense', '+505'],
    [23, 'CA', 'Canadá', 'Canadiense', '+1'],
    [24, 'FR', 'Francia', 'Francés/a', '+33'],
    [25, 'IT', 'Italia', 'Italiano/a', '+39'],
    [26, 'DE', 'Alemania', 'Alemán/a', '+49'],
    [27, 'GB', 'Reino Unido', 'Británico/a', '+44'],
    [28, 'PT', 'Portugal', 'Portugués/a', '+351'],
    [29, 'JP', 'Japón', 'Japonés/a', '+81'],
    [30, 'CN', 'China', 'Chino/a', '+86'],
    [31, 'KR', 'Corea del Sur', 'Surcoreano/a', '+82'],
    [32, 'AU', 'Australia', 'Australiano/a', '+61'],
    [33, 'NZ', 'Nueva Zelanda', 'Neozelandés/a', '+64'],
    [34, 'RU', 'Rusia', 'Ruso/a', '+7'],
    [35, 'UA', 'Ucrania', 'Ucraniano/a', '+380'],
    [36, 'CH', 'Suiza', 'Suizo/a', '+41'],
    [37, 'SE', 'Suecia', 'Sueco/a', '+46'],
    [38, 'NO', 'Noruega', 'Noruego/a', '+47'],
    [39, 'NL', 'Países Bajos', 'Neerlandés/a', '+31'],
    [40, 'BE', 'Bélgica', 'Belga', '+32'],
    [41, 'AL', 'Albania', 'Albanés/a', '+355'],
    [42, 'AD', 'Andorra', 'Andorrano/a', '+376'],
    [43, 'AE', 'Emiratos Árabes Unidos', 'Emiratí', '+971'],
    [44, 'AF', 'Afganistán', 'Afgano/a', '+93'],
    [45, 'AG', 'Antigua y Barbuda', 'Antiguano/a', '+1268'],
    [46, 'AM', 'Armenia', 'Armenio/a', '+374'],
    [47, 'AO', 'Angola', 'Angoleño/a', '+244'],
    [48, 'AT', 'Austria', 'Austríaco/a', '+43'],
    [49, 'AZ', 'Azerbaiyán', 'Azerbaiyano/a', '+994'],
    [50, 'BA', 'Bosnia y Herzegovina', 'Bosnio/a', '+387'],
    [51, 'BB', 'Barbados', 'Barbadense', '+1246'],
    [52, 'BD', 'Bangladés', 'Bangladesí', '+880'],
    [53, 'BF', 'Burkina Faso', 'Burkinés/a', '+226'],
    [54, 'BG', 'Bulgaria', 'Búlgaro/a', '+359'],
    [55, 'BH', 'Baréin', 'Bareiní', '+973'],
    [56, 'BI', 'Burundi', 'Burundés/a', '+257'],
    [57, 'BJ', 'Benín', 'Beninés/a', '+229'],
    [58, 'BN', 'Brunéi', 'Bruneano/a', '+673'],
    [59, 'BS', 'Bahamas', 'Bahameño/a', '+1242'],
    [60, 'BT', 'Bután', 'Butanés/a', '+975'],
    [61, 'BW', 'Botsuana', 'Botsuano/a', '+267'],
    [62, 'BY', 'Bielorrusia', 'Bielorruso/a', '+375'],
    [63, 'BZ', 'Belice', 'Beliceño/a', '+501'],
    [64, 'CD', 'Rep. Democrática del Congo', 'Congoleño/a', '+243'],
    [65, 'CF', 'Rep. Centroafricana', 'Centroafricano/a', '+236'],
    [66, 'CG', 'República del Congo', 'Congoleño/a', '+242'],
    [67, 'CI', 'Costa de Marfil', 'Marfileño/a', '+225'],
    [68, 'CM', 'Camerún', 'Camerunés/a', '+237'],
    [69, 'CY', 'Chipre', 'Chipriota', '+357'],
    [70, 'CZ', 'República Checa', 'Checo/a', '+420'],
    [71, 'DK', 'Dinamarca', 'Danés/a', '+45'],
    [72, 'DM', 'Dominica', 'Dominiqués/a', '+1767'],
    [73, 'DZ', 'Argelia', 'Argelino/a', '+213'],
    [74, 'EE', 'Estonia', 'Estonio/a', '+372'],
    [75, 'EG', 'Egipto', 'Egipcio/a', '+20'],
    [76, 'FI', 'Finlandia', 'Finlandés/a', '+358'],
    [77, 'FJ', 'Fiyi', 'Fiyiano/a', '+679'],
    [78, 'GA', 'Gabón', 'Gabonés/a', '+241'],
    [79, 'GD', 'Granada', 'Granadino/a', '+1473'],
    [80, 'GE', 'Georgia', 'Georgiano/a', '+995'],
    [81, 'GH', 'Ghana', 'Ghanés/a', '+233'],
    [82, 'GM', 'Gambia', 'Gambiano/a', '+220'],
    [83, 'GN', 'Guinea', 'Guineano/a', '+224'],
    [84, 'GQ', 'Guinea Ecuatorial', 'Ecuatoguineano/a', '+240'],
    [85, 'GR', 'Grecia', 'Griego/a', '+30'],
    [86, 'GY', 'Guyana', 'Guyanés/a', '+592'],
    [87, 'HR', 'Croacia', 'Croata', '+385'],
    [88, 'HT', 'Haití', 'Haitiano/a', '+509'],
    [89, 'HU', 'Hungría', 'Húngaro/a', '+36'],
    [90, 'ID', 'Indonesia', 'Indonesio/a', '+62'],
    [91, 'IE', 'Irlanda', 'Irlandés/a', '+353'],
    [92, 'IL', 'Israel', 'Israelí', '+972'],
    [93, 'IN', 'India', 'Indio/a', '+91'],
    [94, 'IQ', 'Irak', 'Iraquí', '+964'],
    [95, 'IR', 'Irán', 'Iraní', '+98'],
    [96, 'IS', 'Islandia', 'Islandés/a', '+354'],
    [97, 'JM', 'Jamaica', 'Jamaiquino/a', '+1876'],
    [98, 'JO', 'Jordania', 'Jordano/a', '+962'],
    [99, 'KE', 'Kenia', 'Keniano/a', '+254'],
    [100, 'KG', 'Kirguistán', 'Kirguís', '+996'],
    [101, 'KH', 'Camboya', 'Camboyano/a', '+855'],
    [102, 'KP', 'Corea del Norte', 'Norcoreano/a', '+850'],
    [103, 'KW', 'Kuwait', 'Kuwaití', '+965'],
    [104, 'KZ', 'Kazajistán', 'Kazajo/a', '+7'],
    [105, 'LA', 'Laos', 'Laosiano/a', '+856'],
    [106, 'LB', 'Líbano', 'Libanés/a', '+961'],
    [107, 'LC', 'Santa Lucía', 'Santalucense', '+1758'],
    [108, 'LI', 'Liechtenstein', 'Liechtensteiniano/a', '+423'],
    [109, 'LK', 'Sri Lanka', 'Ceilanés/a', '+94'],
    [110, 'LR', 'Liberia', 'Liberiano/a', '+231'],
    [111, 'LS', 'Lesoto', 'Lesotense', '+266'],
    [112, 'LT', 'Lituania', 'Lituano/a', '+370'],
    [113, 'LU', 'Luxemburgo', 'Luxemburgués/a', '+352'],
    [114, 'LV', 'Letonia', 'Letón/a', '+371'],
    [115, 'LY', 'Libia', 'Libio/a', '+218'],
    [116, 'MA', 'Marruecos', 'Marroquí', '+212'],
    [117, 'MC', 'Mónaco', 'Monegasco/a', '+377'],
    [118, 'MD', 'Moldavia', 'Moldavo/a', '+373'],
    [119, 'ME', 'Montenegro', 'Montenegrino/a', '+382'],
    [120, 'MG', 'Madagascar', 'Malgache', '+261'],
    [121, 'MK', 'Macedonia del Norte', 'Macedonio/a', '+389'],
    [122, 'ML', 'Malí', 'Maliense', '+223'],
    [123, 'MM', 'Myanmar', 'Birmano/a', '+95'],
    [124, 'MN', 'Mongolia', 'Mongol/a', '+976'],
    [125, 'MR', 'Mauritania', 'Mauritano/a', '+222'],
    [126, 'MT', 'Malta', 'Maltés/a', '+356'],
    [127, 'MU', 'Mauricio', 'Mauriciano/a', '+230'],
    [128, 'MV', 'Maldivas', 'Maldivo/a', '+960'],
    [129, 'MW', 'Malaui', 'Malauí', '+265'],
    [130, 'MY', 'Malasia', 'Malasio/a', '+60'],
    [131, 'MZ', 'Mozambique', 'Mozambiqueño/a', '+258'],
    [132, 'NA', 'Namibia', 'Namibio/a', '+264'],
    [133, 'NE', 'Níger', 'Nigerino/a', '+227'],
    [134, 'NG', 'Nigeria', 'Nigeriano/a', '+234'],
    [135, 'NP', 'Nepal', 'Nepalí', '+977'],
    [136, 'OM', 'Omán', 'Omaní', '+968'],
    [137, 'PH', 'Filipinas', 'Filipino/a', '+63'],
    [138, 'PK', 'Pakistán', 'Pakistaní', '+92'],
    [139, 'PL', 'Polonia', 'Polaco/a', '+48'],
    [140, 'QA', 'Catar', 'Catarí', '+974'],
    [141, 'RO', 'Rumania', 'Rumano/a', '+40'],
    [142, 'RS', 'Serbia', 'Serbio/a', '+381'],
    [143, 'RW', 'Ruanda', 'Ruandés/a', '+250'],
    [144, 'SA', 'Arabia Saudita', 'Saudí', '+966'],
    [145, 'SD', 'Sudán', 'Sudanés/a', '+249'],
    [146, 'SG', 'Singapur', 'Singapurense', '+65'],
    [147, 'SI', 'Eslovenia', 'Esloveno/a', '+386'],
    [148, 'SK', 'Eslovaquia', 'Eslovaco/a', '+421'],
    [149, 'SL', 'Sierra Leona', 'Sierraleonés/a', '+232'],
    [150, 'SN', 'Senegal', 'Senegalés/a', '+221'],
    [151, 'SO', 'Somalia', 'Somalí', '+252'],
    [152, 'SR', 'Surinam', 'Surinamés/a', '+597'],
    [153, 'SS', 'Sudán del Sur', 'Sursudanés/a', '+211'],
    [154, 'SY', 'Siria', 'Sirio/a', '+963'],
    [155, 'TH', 'Tailandia', 'Tailandés/a', '+66'],
    [156, 'TJ', 'Tayikistán', 'Tayiko/a', '+992'],
    [157, 'TM', 'Turkmenistán', 'Turcomano/a', '+993'],
    [158, 'TN', 'Túnez', 'Tunecino/a', '+216'],
    [159, 'TR', 'Turquía', 'Turco/a', '+90'],
    [160, 'TT', 'Trinidad y Tobago', 'Trinitense', '+1868'],
    [161, 'TZ', 'Tanzania', 'Tanzano/a', '+255'],
    [162, 'UG', 'Uganda', 'Ugandés/a', '+256'],
    [163, 'UZ', 'Uzbekistán', 'Uzbeko/a', '+998'],
    [164, 'VA', 'Ciudad del Vaticano', 'Vaticano/a', '+379'],
    [165, 'VC', 'San Vicente y las Granadinas', 'Sanvicentino/a', '+1784'],
    [166, 'VN', 'Vietnam', 'Vietnamita', '+84'],
    [167, 'YE', 'Yemen', 'Yemení', '+967'],
    [168, 'ZA', 'Sudáfrica', 'Sudafricano/a', '+27'],
    [169, 'ZM', 'Zambia', 'Zambiano/a', '+260'],
    [170, 'ZW', 'Zimbabue', 'Zimbabuense', '+263']
];

$stmt_p = $db->prepare("INSERT IGNORE INTO cat_paises (id, codigo_iso, nombre, gentilicio, indicativo) VALUES (?, ?, ?, ?, ?)");
foreach ($paises as $p) {
    $stmt_p->execute($p);
}
echo "Países insertados (" . count($paises) . ").\n";

// 3. Sembrar Departamentos de Colombia (32 + Bogotá D.C.)
$deptos_colombia = [
    ['05', 'Antioquia'], ['08', 'Atlántico'], ['11', 'Bogotá D.C.'], ['13', 'Bolívar'],
    ['15', 'Boyacá'], ['17', 'Caldas'], ['18', 'Caquetá'], ['19', 'Cauca'],
    ['20', 'Cesar'], ['23', 'Córdoba'], ['25', 'Cundinamarca'], ['27', 'Chocó'],
    ['41', 'Huila'], ['44', 'La Guajira'], ['47', 'Magdalena'], ['50', 'Meta'],
    ['52', 'Nariño'], ['54', 'Norte de Santander'], ['63', 'Quindío'], ['66', 'Risaralda'],
    ['68', 'Santander'], ['70', 'Sucre'], ['73', 'Tolima'], ['76', 'Valle del Cauca'],
    ['81', 'Arauca'], ['85', 'Casanare'], ['86', 'Putumayo'], ['88', 'San Andrés y Providencia'],
    ['91', 'Amazonas'], ['94', 'Guainía'], ['95', 'Guaviare'], ['97', 'Vaupés'], ['99', 'Vichada']
];

$stmt_d = $db->prepare("INSERT INTO cat_departamentos (pais_id, codigo_dane, nombre) VALUES (1, ?, ?)");
$depto_map = [];

// Limpiar departamentos colombianos para reinsertar ordenadamente si ya existen
$db->exec("DELETE FROM cat_departamentos WHERE pais_id = 1");

foreach ($deptos_colombia as $d) {
    $stmt_d->execute([$d[0], $d[1]]);
    $depto_map[$d[0]] = (int)$db->lastInsertId();
}
echo "Departamentos de Colombia insertados (" . count($deptos_colombia) . ").\n";

// 4. Sembrar Municipios Clave de Colombia
$muns = [
    // Antioquia (05)
    ['05', '05001', 'Medellín'], ['05', '05088', 'Bello'], ['05', '05360', 'Itagüí'],
    ['05', '05266', 'Envigado'], ['05', '05615', 'Rionegro'], ['05', '05045', 'Apartadó'],
    ['05', '05631', 'Sabaneta'], ['05', '05147', 'Carepa'], ['05', '05129', 'Caldas'],
    ['05', '05212', 'Copacabana'], ['05', '05400', 'La Ceja'], ['05', '05440', 'Marinilla'],
    ['05', '05756', 'Sonsón'], ['05', '05837', 'Turbo'], ['05', '05893', 'Yarumal'],
    
    // Atlántico (08)
    ['08', '08001', 'Barranquilla'], ['08', '08758', 'Soledad'], ['08', '08433', 'Malambo'],
    ['08', '08573', 'Puerto Colombia'], ['08', '08634', 'Sabanagrande'], ['08', '08078', 'Baranoa'],

    // Bogotá D.C. (11)
    ['11', '11001', 'Bogotá D.C.'],

    // Bolívar (13)
    ['13', '13001', 'Cartagena de Indias'], ['13', '13430', 'Magangué'], ['13', '13810', 'Turbaná'],
    ['13', '13052', 'Arjona'], ['13', '13244', 'El Carmen de Bolívar'], ['13', '13836', 'Turbaco'],

    // Boyacá (15)
    ['15', '15001', 'Tunja'], ['15', '15759', 'Sogamoso'], ['15', '15238', 'Duitama'],
    ['15', '15176', 'Chiquinquirá'], ['15', '15572', 'Puerto Boyacá'], ['15', '15861', 'Villa de Leyva'],

    // Caldas (17)
    ['17', '17001', 'Manizales'], ['17', '17380', 'La Dorada'], ['17', '17873', 'Villamaría'],
    ['17', '17174', 'Chinchiná'], ['17', '17042', 'Anserma'], ['17', '17614', 'Riosucio'],

    // Caquetá (18)
    ['18', '18001', 'Florencia'], ['18', '18753', 'San Vicente del Caguán'],

    // Cauca (19)
    ['19', '19001', 'Popayán'], ['19', '19698', 'Santander de Quilichao'], ['19', '19548', 'Piendamó'],

    // Cesar (20)
    ['20', '20001', 'Valledupar'], ['20', '20011', 'Aguachica'], ['20', '20013', 'Agustín Codazzi'],
    ['20', '20060', 'Bosconia'], ['20', '20400', 'La Jagua de Ibirico'],

    // Córdoba (23)
    ['23', '23001', 'Montería'], ['23', '23162', 'Cereté'], ['23', '23417', 'Lorica'],
    ['23', '23670', 'San Andrés de Sotavento'], ['23', '23466', 'Montelíbano'], ['23', '23807', 'Tierralta'],
    ['23', '23555', 'Planeta Rica'], ['23', '23672', 'San Antero'], ['23', '23686', 'San Pelayo'],

    // Cundinamarca (25)
    ['25', '25754', 'Soacha'], ['25', '25175', 'Chía'], ['25', '25899', 'Zipaquirá'],
    ['25', '25269', 'Facatativá'], ['25', '25290', 'Fusagasugá'], ['25', '25430', 'Madrid'],
    ['25', '25473', 'Mosquera'], ['25', '25307', 'Girardot'], ['25', '25286', 'Funza'],
    ['25', '25126', 'Cajicá'], ['25', '25758', 'Sopó'], ['25', '25817', 'Tocancipá'],

    // Chocó (27)
    ['27', '27001', 'Quibdó'], ['27', '27372', 'Istmina'],

    // Huila (41)
    ['41', '41001', 'Neiva'], ['41', '41551', 'Pitalito'], ['41', '41298', 'Garzón'], ['41', '41396', 'La Plata'],

    // La Guajira (44)
    ['44', '44001', 'Riohacha'], ['44', '44430', 'Maicao'], ['44', '44847', 'Uribia'], ['44', '44650', 'San Juan del Cesar'],

    // Magdalena (47)
    ['47', '47001', 'Santa Marta'], ['47', '47189', 'Ciénaga'], ['47', '47288', 'Fundación'], ['47', '47053', 'Aracataca'],

    // Meta (50)
    ['50', '50001', 'Villavicencio'], ['50', '50006', 'Acacías'], ['50', '50287', 'Granada'], ['50', '50568', 'Puerto Gaitán'],

    // Nariño (52)
    ['52', '52001', 'Pasto'], ['52', '52835', 'Tumaco'], ['52', '52356', 'Ipiales'], ['52', '52838', 'Túquerres'],

    // Norte de Santander (54)
    ['54', '54001', 'Cúcuta'], ['54', '54810', 'Villa del Rosario'], ['54', '54405', 'Los Patios'],
    ['54', '54498', 'Ocaña'], ['54', '54518', 'Pamplona'], ['54', '54800', 'Tibú'],

    // Quindío (63)
    ['63', '63001', 'Armenia'], ['63', '63130', 'Calarcá'], ['63', '63470', 'Montenegro'], ['63', '63594', 'Quimbaya'],

    // Risaralda (66)
    ['66', '66001', 'Pereira'], ['66', '66170', 'Dosquebradas'], ['66', '66682', 'Santa Rosa de Cabal'],

    // Santander (68)
    ['68', '68001', 'Bucaramanga'], ['68', '68276', 'Floridablanca'], ['68', '68307', 'Girón'],
    ['68', '68547', 'Piedecuesta'], ['68', '68081', 'Barrancabermeja'], ['68', '68679', 'San Gil'],
    ['68', '68755', 'Socorro'], ['68', '68077', 'Barbosa'],

    // Sucre (70)
    ['70', '70001', 'Sincelejo'], ['70', '70215', 'Corozal'], ['70', '70708', 'San Marcos'],
    ['70', '70717', 'San Onofre'], ['70', '70820', 'Santiago de Tolú'], ['70', '70771', 'Sucre'],

    // Tolima (73)
    ['73', '73001', 'Ibagué'], ['73', '73268', 'Espinal'], ['73', '73449', 'Melgar'],
    ['73', '73168', 'Chaparral'], ['73', '73349', 'Honda'], ['73', '73408', 'Líbano'],

    // Valle del Cauca (76)
    ['76', '76001', 'Cali'], ['76', '76111', 'Buenaventura'], ['76', '76520', 'Palmira'],
    ['76', '76834', 'Tuluá'], ['76', '76147', 'Cartago'], ['76', '76364', 'Jamundí'],
    ['76', '76130', 'Candelaria'], ['76', '76126', 'Calima (El Darién)'], ['76', '76100', 'Bolívar'],
    ['76', '76892', 'Yumbo'], ['76', '76248', 'El Cerrito'], ['76', '76318', 'Guacarí'],
    ['76', '76122', 'Buga (Guadalajara de Buga)'], ['76', '76670', 'San Pedro'], ['76', '76828', 'Trujillo'],

    // Arauca (81)
    ['81', '81001', 'Arauca'], ['81', '81736', 'Saravena'], ['81', '81794', 'Tame'],

    // Casanare (85)
    ['85', '85001', 'Yopal'], ['85', '85010', 'Aguazul'], ['85', '85440', 'Villanueva'],

    // Putumayo (86)
    ['86', '86001', 'Mocoa'], ['86', '86568', 'Puerto Asís'], ['86', '86571', 'Puerto Guzmán'],

    // San Andrés (88)
    ['88', '88001', 'San Andrés'], ['88', '88564', 'Providencia'],

    // Amazonas (91)
    ['91', '91001', 'Leticia'], ['91', '91540', 'Puerto Nariño'],

    // Guainía (94)
    ['94', '94001', 'Inírida'],

    // Guaviare (95)
    ['95', '95001', 'San José del Guaviare'],

    // Vaupés (97)
    ['97', '97001', 'Mitú'],

    // Vichada (99)
    ['99', '99001', 'Puerto Carreño']
];

$stmt_m = $db->prepare("INSERT INTO cat_municipios (departamento_id, codigo_dane, nombre) VALUES (?, ?, ?)");
foreach ($muns as $m) {
    $depto_id = $depto_map[$m[0]] ?? null;
    if ($depto_id) {
        $stmt_m->execute([$depto_id, $m[1], $m[2]]);
    }
}
echo "Municipios de Colombia insertados (" . count($muns) . ").\n";

// 5. Sembrar Kosovo (País id=5)
$db->exec("DELETE FROM cat_departamentos WHERE pais_id = 5");
$deptos_kosovo = [
    ['PRI', 'Distrito de Pristina', ['Pristina', 'Podujevo', 'Glogovac', 'Kosovo Polje', 'Lipljan', 'Obilić', 'Gračanica']],
    ['PRZ', 'Distrito de Prizren', ['Prizren', 'Dragaš', 'Suva Reka', 'Mamuša']],
    ['PEJ', 'Distrito de Peja', ['Peć (Peja)', 'Istok', 'Klina']],
    ['GJK', 'Distrito de Gjakova', ['Gjakova', 'Dečani', 'Orahovac', 'Junik']],
    ['MIT', 'Distrito de Mitrovica', ['Mitrovica', 'Leposavić', 'Srbica', 'Vučitrn', 'Zubin Potok', 'Zvečan']],
    ['FER', 'Distrito de Ferizaj', ['Ferizaj', 'Kačanik', 'Štimlje', 'Elez Han', 'Štrpce']],
    ['GJL', 'Distrito de Gjilan', ['Gjilan', 'Kamenica', 'Vitina', 'Ranilug', 'Parteš', 'Klokot']]
];

foreach ($deptos_kosovo as $dk) {
    $stmt_d = $db->prepare("INSERT INTO cat_departamentos (pais_id, codigo_dane, nombre) VALUES (5, ?, ?)");
    $stmt_d->execute([$dk[0], $dk[1]]);
    $d_id = (int)$db->lastInsertId();
    foreach ($dk[2] as $m_nom) {
        $stmt_m->execute([$d_id, null, $m_nom]);
    }
}
echo "Kosovo y sus 7 distritos con 38 municipios insertados con éxito.\n";

// 6. Sembrar Venezuela (País id=2)
$db->exec("DELETE FROM cat_departamentos WHERE pais_id = 2");
$deptos_vzla = [
    ['DC', 'Distrito Capital', ['Caracas', 'El Recreo', 'Sucre']],
    ['ZUL', 'Zulia', ['Maracaibo', 'Cabimas', 'San Francisco', 'Lagunillas', 'Ciudad Ojeda']],
    ['TAC', 'Táchira', ['San Cristóbal', 'San Antonio del Táchira', 'Ureña', 'Táriba', 'Rubio']],
    ['MIR', 'Miranda', ['Los Teques', 'Guarenas', 'Guatire', 'Baruta', 'Chacao', 'Petare']],
    ['CAR', 'Carabobo', ['Valencia', 'Puerto Cabello', 'Guacara', 'Naguanagua', 'San Diego']],
    ['LAR', 'Lara', ['Barquisimeto', 'Carora', 'Cabudare', 'El Tocuyo']],
    ['BOL', 'Bolívar', ['Ciudad Guayana', 'Ciudad Bolívar', 'Puerto Ordaz', 'Upata']],
    ['ANZ', 'Anzoátegui', ['Barcelona', 'Puerto La Cruz', 'El Tigre', 'Lechería', 'Anaco']],
    ['ARA', 'Aragua', ['Maracay', 'Turmero', 'La Victoria', 'Cagua', 'Villa de Cura']],
    ['MER', 'Mérida', ['Mérida', 'El Vigía', 'Tovar', 'Ejido']],
    ['BAR', 'Barinas', ['Barinas', 'Socopó', 'Santa Bárbara']]
];
foreach ($deptos_vzla as $dv) {
    $stmt_d = $db->prepare("INSERT INTO cat_departamentos (pais_id, codigo_dane, nombre) VALUES (2, ?, ?)");
    $stmt_d->execute([$dv[0], $dv[1]]);
    $d_id = (int)$db->lastInsertId();
    foreach ($dv[2] as $m_nom) {
        $stmt_m->execute([$d_id, null, $m_nom]);
    }
}
echo "Venezuela y sus estados principales insertados.\n";

// 7. Sembrar Ecuador (País id=3)
$db->exec("DELETE FROM cat_departamentos WHERE pais_id = 3");
$deptos_ec = [
    ['PICH', 'Pichincha', ['Quito', 'Cayambe', 'Rumiñahui', 'Mejía']],
    ['GUAY', 'Guayas', ['Guayaquil', 'Durán', 'Samborondón', 'Milagro', 'Daule']],
    ['AZU', 'Azuay', ['Cuenca', 'Gualaceo', 'Paute']],
    ['MAN', 'Manabí', ['Portoviejo', 'Manta', 'Chone', 'Montecristi']],
    ['TUNG', 'Tungurahua', ['Ambato', 'Baños', 'Pelileo']],
    ['ELOR', 'El Oro', ['Machala', 'Pasaje', 'Santa Rosa', 'Huaquillas']],
    ['LOJA', 'Loja', ['Loja', 'Catamayo', 'Cariamanga']],
    ['CARCH', 'Carchi', ['Tulcán', 'San Gabriel', 'Montúfar']]
];
foreach ($deptos_ec as $de) {
    $stmt_d = $db->prepare("INSERT INTO cat_departamentos (pais_id, codigo_dane, nombre) VALUES (3, ?, ?)");
    $stmt_d->execute([$de[0], $de[1]]);
    $d_id = (int)$db->lastInsertId();
    foreach ($de[2] as $m_nom) {
        $stmt_m->execute([$d_id, null, $m_nom]);
    }
}
echo "Ecuador y sus provincias principales insertadas.\n";

// 8. Sembrar Perú (País id=4)
$db->exec("DELETE FROM cat_departamentos WHERE pais_id = 4");
$deptos_pe = [
    ['LIM', 'Lima', ['Lima', 'Callao', 'Miraflores', 'San Isidro', 'Los Olivos', 'Surco']],
    ['ARE', 'Arequipa', ['Arequipa', 'Cayma', 'Cerro Colorado']],
    ['CUS', 'Cusco', ['Cusco', 'Urubamba', 'Sicuani']],
    ['LAL', 'La Libertad', ['Trujillo', 'Chepén', 'Huamachuco']],
    ['PIU', 'Piura', ['Piura', 'Sullana', 'Talara', 'Paita']],
    ['LAM', 'Lambayeque', ['Chiclayo', 'Lambayeque', 'Ferreñafe']],
    ['PUN', 'Puno', ['Puno', 'Juliaca', 'Ilave']],
    ['TAC', 'Tacna', ['Tacna', 'Alto de la Alianza']]
];
foreach ($deptos_pe as $dp) {
    $stmt_d = $db->prepare("INSERT INTO cat_departamentos (pais_id, codigo_dane, nombre) VALUES (4, ?, ?)");
    $stmt_d->execute([$dp[0], $dp[1]]);
    $d_id = (int)$db->lastInsertId();
    foreach ($dp[2] as $m_nom) {
        $stmt_m->execute([$d_id, null, $m_nom]);
    }
}
echo "Perú y sus departamentos principales insertados.\n";

// 9. Sembrar Estados Unidos (País id=6)
$db->exec("DELETE FROM cat_departamentos WHERE pais_id = 6");
$deptos_us = [
    ['FL', 'Florida', ['Miami', 'Orlando', 'Tampa', 'Fort Lauderdale', 'Jacksonville']],
    ['NY', 'Nueva York', ['Nueva York', 'Buffalo', 'Rochester', 'Yonkers', 'Albany']],
    ['CA', 'California', ['Los Ángeles', 'San Francisco', 'San Diego', 'San José', 'Sacramento']],
    ['TX', 'Texas', ['Houston', 'San Antonio', 'Dallas', 'Austin', 'Fort Worth', 'El Paso']],
    ['IL', 'Illinois', ['Chicago', 'Aurora', 'Naperville', 'Joliet']],
    ['NJ', 'Nueva Jersey', ['Newark', 'Jersey City', 'Paterson', 'Elizabeth']]
];
foreach ($deptos_us as $du) {
    $stmt_d = $db->prepare("INSERT INTO cat_departamentos (pais_id, codigo_dane, nombre) VALUES (6, ?, ?)");
    $stmt_d->execute([$du[0], $du[1]]);
    $d_id = (int)$db->lastInsertId();
    foreach ($du[2] as $m_nom) {
        $stmt_m->execute([$d_id, null, $m_nom]);
    }
}
echo "Estados Unidos y sus estados principales insertados.\n";

// 10. Sembrar España (País id=7)
$db->exec("DELETE FROM cat_departamentos WHERE pais_id = 7");
$deptos_es = [
    ['MAD', 'Comunidad de Madrid', ['Madrid', 'Móstoles', 'Alcalá de Henares', 'Getafe', 'Leganés']],
    ['CAT', 'Cataluña', ['Barcelona', 'L\'Hospitalet de Llobregat', 'Badalona', 'Terrassa', 'Sabadell']],
    ['AND', 'Andalucía', ['Sevilla', 'Málaga', 'Córdoba', 'Granada', 'Jerez de la Frontera', 'Almería']],
    ['VAL', 'Comunidad Valenciana', ['Valencia', 'Alicante', 'Elche', 'Castellón de la Plana']],
    ['GAL', 'Galicia', ['Vigo', 'A Coruña', 'Ourense', 'Lugo', 'Santiago de Compostela']],
    ['PVA', 'País Vasco', ['Bilbao', 'Vitoria-Gasteiz', 'San Sebastián', 'Barakaldo']]
];
foreach ($deptos_es as $de_sp) {
    $stmt_d = $db->prepare("INSERT INTO cat_departamentos (pais_id, codigo_dane, nombre) VALUES (7, ?, ?)");
    $stmt_d->execute([$de_sp[0], $de_sp[1]]);
    $d_id = (int)$db->lastInsertId();
    foreach ($de_sp[2] as $m_nom) {
        $stmt_m->execute([$d_id, null, $m_nom]);
    }
}
echo "España y sus comunidades principales insertadas.\n";

echo "🚀 Módulo Geográfico Global instalado y sembrado con éxito!\n";
