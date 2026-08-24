<?php
declare(strict_types=1);

require_once __DIR__ . '/../../php/db.php';

echo "Iniciando siembra de Ciudades Mundiales de Clase Global...\n";

$catalogo_mundial = [
    'UG' => [ // Uganda
        'Región Central (Kampala)' => ['Kampala', 'Entebbe', 'Mukono', 'Wakiso', 'Kayunga'],
        'Región Norte'            => ['Gulu', 'Lira', 'Kitgum', 'Arua', 'Pader'],
        'Región Este'             => ['Jinja', 'Mbale', 'Tororo', 'Iganga', 'Busia'],
        'Región Oeste'            => ['Mbarara', 'Kasese', 'Fort Portal', 'Kabale', 'Hoima'],
        'Región Central Sur'      => ['Masaka', 'Mityana', 'Mubende', 'Rakai']
    ],
    'MX' => [ // México
        'Ciudad de México' => ['Ciudad de México', 'Iztapalapa', 'Coyoacán', 'Cuauhtémoc', 'Álvaro Obregón'],
        'Jalisco'          => ['Guadalajara', 'Zapopan', 'Tlaquepaque', 'Puerto Vallarta'],
        'Nuevo León'       => ['Monterrey', 'San Pedro Garza García', 'Guadalupe', 'Apodaca'],
        'Puebla'           => ['Puebla', 'Tehuacán', 'Atlixco'],
        'Yucatán'          => ['Mérida', 'Progreso', 'Valladolid'],
        'Quintana Roo'     => ['Cancún', 'Playa del Carmen', 'Cozumel', 'Chetumal']
    ],
    'AR' => [ // Argentina
        'Buenos Aires' => ['Buenos Aires (CABA)', 'La Plata', 'Mar del Plata', 'Bahía Blanca', 'Tandil'],
        'Córdoba'      => ['Córdoba', 'Villa Carlos Paz', 'Río Cuarto', 'Villa María'],
        'Santa Fe'     => ['Rosario', 'Santa Fe', 'Rafaela'],
        'Mendoza'      => ['Mendoza', 'San Rafael', 'Godoy Cruz']
    ],
    'BR' => [ // Brasil
        'São Paulo'         => ['São Paulo', 'Guarulhos', 'Campinas', 'Santos', 'São Bernardo do Campo'],
        'Rio de Janeiro'    => ['Rio de Janeiro', 'Niterói', 'Petrópolis', 'Duque de Caxias'],
        'Distrito Federal'  => ['Brasilia', 'Taguatinga', 'Ceilândia'],
        'Bahia'             => ['Salvador', 'Feira de Santana', 'Vitória da Conquista'],
        'Minas Gerais'      => ['Belo Horizonte', 'Uberlândia', 'Juiz de Fora']
    ],
    'CL' => [ // Chile
        'Región Metropolitana' => ['Santiago', 'Puente Alto', 'Maipú', 'Las Condes', 'Providencia'],
        'Valparaíso'           => ['Valparaíso', 'Viña del Mar', 'Quilpué', 'Villa Alemana'],
        'Biobío'               => ['Concepción', 'Talcahuano', 'Los Ángeles']
    ],
    'PA' => [ // Panamá
        'Panamá'   => ['Ciudad de Panamá', 'San Miguelito', 'Tocumen', 'Las Cumbres'],
        'Chiriquí' => ['David', 'Boquete', 'Volcán'],
        'Colón'    => ['Colón', 'Sabanitas', 'Portobelo']
    ],
    'CR' => [ // Costa Rica
        'San José' => ['San José', 'Escazú', 'Desamparados', 'Santa Ana'],
        'Alajuela' => ['Alajuela', 'San Carlos', 'San Ramón'],
        'Heredia'  => ['Heredia', 'San Rafael', 'Barva']
    ],
    'CA' => [ // Canadá
        'Ontario'            => ['Toronto', 'Ottawa', 'Mississauga', 'Hamilton', 'London'],
        'Quebec'             => ['Montreal', 'Quebec City', 'Laval', 'Gatineau'],
        'Columbia Británica' => ['Vancouver', 'Victoria', 'Surrey', 'Burnaby'],
        'Alberta'            => ['Calgary', 'Edmonton', 'Red Deer']
    ],
    'GB' => [ // Reino Unido
        'Inglaterra'        => ['Londres', 'Manchester', 'Birmingham', 'Liverpool', 'Leeds', 'Bristol'],
        'Escocia'           => ['Edimburgo', 'Glasgow', 'Aberdeen', 'Dundee'],
        'Gales'             => ['Cardiff', 'Swansea', 'Newport'],
        'Irlanda del Norte' => ['Belfast', 'Derry', 'Lisburn']
    ],
    'FR' => [ // Francia
        'Île-de-France' => ['París', 'Versalles', 'Boulogne-Billancourt', 'Saint-Denis'],
        'Provenza'      => ['Marsella', 'Niza', 'Tolón', 'Aix-en-Provence'],
        'Ródano'        => ['Lyon', 'Grenoble', 'Saint-Étienne'],
        'Aquitania'     => ['Burdeos', 'Bayona', 'Pau']
    ],
    'DE' => [ // Alemania
        'Baviera'   => ['Múnich', 'Núremberg', 'Augsburgo', 'Ratisbona'],
        'Berlín'    => ['Berlín', 'Potsdam'],
        'Hesse'     => ['Fráncfort', 'Wiesbaden', 'Darmstadt'],
        'Renania'   => ['Colonia', 'Düsseldorf', 'Dortmund', 'Bonn']
    ],
    'IT' => [ // Italia
        'Lacio'     => ['Roma', 'Latina', 'Tívoli'],
        'Lombardía' => ['Milán', 'Brescia', 'Bérgamo', 'Monza'],
        'Véneto'    => ['Venecia', 'Verona', 'Padua'],
        'Toscana'   => ['Florencia', 'Pisa', 'Siena']
    ],
    'JP' => [ // Japón
        'Tokio'   => ['Tokio', 'Shinjuku', 'Shibuya', 'Hachioji'],
        'Kansai'  => ['Osaka', 'Kioto', 'Kobe', 'Nara'],
        'Kanto'   => ['Yokohama', 'Kawasaki', 'Saitama', 'Chiba'],
        'Chubu'   => ['Nagoya', 'Shizuoka', 'Kanazawa']
    ],
    'CN' => [ // China
        'Beijing'   => ['Beijing', 'Chaoyang', 'Haidian'],
        'Shanghái'  => ['Shanghái', 'Pudong', 'Minhang'],
        'Guangdong' => ['Guangzhou', 'Shenzhen', 'Dongguan', 'Foshan']
    ],
    'AU' => [ // Australia
        'Nueva Gales del Sur' => ['Sídney', 'Newcastle', 'Wollongong'],
        'Victoria'            => ['Melbourne', 'Geelong', 'Ballarat'],
        'Queensland'          => ['Brisbane', 'Gold Coast', 'Cairns']
    ],
    'ZA' => [ // Sudáfrica
        'Gauteng'        => ['Johannesburgo', 'Pretoria', 'Soweto'],
        'Cabo Occidental'=> ['Ciudad del Cabo', 'Stellenbosch', 'George'],
        'KwaZulu-Natal'  => ['Durban', 'Pietermaritzburg']
    ]
];

$stmt_find_pais = $db->prepare("SELECT id FROM cat_paises WHERE codigo_iso = ?");
$stmt_ins_depto = $db->prepare("INSERT INTO cat_departamentos (pais_id, codigo_dane, nombre) VALUES (?, ?, ?)");
$stmt_ins_mun   = $db->prepare("INSERT INTO cat_municipios (departamento_id, codigo_dane, nombre) VALUES (?, ?, ?)");

$total_ciudades = 0;
$total_deptos = 0;

foreach ($catalogo_mundial as $iso => $deptos) {
    $stmt_find_pais->execute([$iso]);
    $pais_id = $stmt_find_pais->fetchColumn();
    
    if (!$pais_id) continue;
    $pais_id = (int)$pais_id;

    // Limpiar para refrescar
    $db->prepare("DELETE FROM cat_departamentos WHERE pais_id = ?")->execute([$pais_id]);

    foreach ($deptos as $depto_nom => $ciudades) {
        $stmt_ins_depto->execute([$pais_id, null, $depto_nom]);
        $depto_id = (int)$db->lastInsertId();
        $total_deptos++;

        foreach ($ciudades as $ciudad_nom) {
            $stmt_ins_mun->execute([$depto_id, null, $ciudad_nom]);
            $total_ciudades++;
        }
    }
}

echo "✅ Se sembraron $total_deptos departamentos/regiones y $total_ciudades ciudades mundiales exitosamente.\n";
