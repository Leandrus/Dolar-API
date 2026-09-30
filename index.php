<?php
/**
 * API Dólar y Euro Venezuela
 * Optimizado para Hosting Compartido (Hostinger / Apache / LiteSpeed)
 * Subdominio: api-dolar.leandrus.net
 */

// 1. Configuración de cabeceras CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder peticiones pre-flight de navegadores (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// 2. Parámetros de Caché
define('CACHE_FILE', __DIR__ . '/cache.json');
define('CACHE_TTL', 900); // 15 minutos (en segundos)

// 3. Obtener cotizaciones con caché inteligente y tolerancia a fallos
$data = getRatesWithCache();

// 4. Procesamiento de ruta (Router simple)
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim(strtolower($uri), '/');

// Enrutador de endpoints
switch ($path) {
    case 'v1/dolares':
    case 'dolares':
        echo json_encode([$data['dolarOficial'], $data['dolarParalelo']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/dolares/oficial':
    case 'dolares/oficial':
        echo json_encode($data['dolarOficial'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/dolares/paralelo':
    case 'dolares/paralelo':
        echo json_encode($data['dolarParalelo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/euros':
    case 'euros':
        echo json_encode([$data['euroOficial'], $data['euroParalelo']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/euros/oficial':
    case 'euros/oficial':
        echo json_encode($data['euroOficial'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/euros/paralelo':
    case 'euros/paralelo':
        echo json_encode($data['euroParalelo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/cotizaciones':
    case 'cotizaciones':
        echo json_encode([$data['dolarOficial'], $data['euroOficial']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case '':
    case 'v1':
        echo json_encode([
            'servicio' => 'API Cotizaciones Venezuela (USD & EUR)',
            'estado' => 'En línea',
            'ultima_actualizacion_cache' => $data['_cached_at'] ?? null,
            'endpoints' => [
                '/v1/dolares' => 'Cotizaciones del dólar oficial (BCV) y paralelo',
                '/v1/dolares/oficial' => 'Cotización del dólar oficial BCV',
                '/v1/dolares/paralelo' => 'Cotización del dólar paralelo',
                '/v1/euros' => 'Cotizaciones del euro oficial (BCV) y paralelo',
                '/v1/euros/oficial' => 'Cotización del euro oficial BCV',
                '/v1/euros/paralelo' => 'Cotización del euro paralelo',
                '/v1/cotizaciones' => 'Cotizaciones oficiales BCV (Dólar y Euro)'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'error' => 404,
            'mensaje' => "Endpoint no encontrado: /$path",
            'endpoints_disponibles' => [
                '/v1/dolares',
                '/v1/dolares/oficial',
                '/v1/dolares/paralelo',
                '/v1/euros',
                '/v1/euros/oficial',
                '/v1/euros/paralelo',
                '/v1/cotizaciones'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
}

// ==========================================
// FUNCIONES DE EXTRACCIÓN Y CACHÉ
// ==========================================

function getRatesWithCache() {
    $now = time();
    $cached = null;

    if (file_exists(CACHE_FILE)) {
        $content = @file_get_contents(CACHE_FILE);
        $cached = json_decode($content, true);

        // Si la caché tiene menos de 15 minutos, responder de inmediato sin llamadas externas
        if ($cached && isset($cached['_timestamp']) && ($now - $cached['_timestamp'] < CACHE_TTL)) {
            return $cached;
        }
    }

    // Intentamos extraer datos en vivo
    $bcv = fetchBcv();
    $yadio = fetchYadio();

    // Manejo de Oficial (BCV) con tolerancia a caídas
    if (!$bcv['usd'] && $cached) {
        $dolarOficial = $cached['dolarOficial'];
        $euroOficial = $cached['euroOficial'];
    } else {
        $dolarOficial = [
            'moneda' => 'USD',
            'fuente' => 'oficial',
            'nombre' => 'Dólar',
            'compra' => null,
            'venta' => null,
            'promedio' => $bcv['usd'],
            'fechaActualizacion' => $bcv['fecha'] ?? gmdate('Y-m-d\TH:i:s\Z')
        ];

        $euroOficial = [
            'moneda' => 'EUR',
            'fuente' => 'oficial',
            'nombre' => 'Euro',
            'compra' => null,
            'venta' => null,
            'promedio' => $bcv['eur'],
            'fechaActualizacion' => $bcv['fecha'] ?? gmdate('Y-m-d\TH:i:s\Z')
        ];
    }

    // Manejo de Paralelo (Yadio) con tolerancia a caídas
    if (!$yadio['usd'] && $cached) {
        $dolarParalelo = $cached['dolarParalelo'];
        $euroParalelo = $cached['euroParalelo'];
    } else {
        $isoDate = gmdate('Y-m-d\TH:i:s.v\Z');
        $dolarParalelo = [
            'moneda' => 'USD',
            'fuente' => 'paralelo',
            'nombre' => 'Paralelo',
            'compra' => null,
            'venta' => null,
            'promedio' => $yadio['usd'],
            'fechaActualizacion' => $isoDate
        ];

        $euroParalelo = [
            'moneda' => 'EUR',
            'fuente' => 'paralelo',
            'nombre' => 'Paralelo',
            'compra' => null,
            'venta' => null,
            'promedio' => $yadio['eur'],
            'fechaActualizacion' => $isoDate
        ];
    }

    $result = [
        '_timestamp' => $now,
        '_cached_at' => date('c'),
        'dolarOficial' => $dolarOficial,
        'dolarParalelo' => $dolarParalelo,
        'euroOficial' => $euroOficial,
        'euroParalelo' => $euroParalelo
    ];

    // Guardar en archivo local
    @file_put_contents(CACHE_FILE, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $result;
}

function fetchUrl($url, $timeout = 10) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);
    $res = curl_exec($ch);
    return $res ?: null;
}

function fetchBcv() {
    $html = fetchUrl('https://www.bcv.org.ve/', 12);
    if (!$html) {
        return ['fecha' => null, 'usd' => null, 'eur' => null];
    }

    // Fecha reportada por el BCV
    preg_match('/class="date-display-single"[^>]*content="([^"]+)"/', $html, $mFecha);
    $fecha = $mFecha[1] ?? null;

    // USD
    $usd = null;
    if (preg_match('/<span>\s*USD\s*<\/span>.*?<strong[^>]*>\s*([0-9.,]+)\s*<\/strong>/s', $html, $mUsd)) {
        $usd = cleanNumber($mUsd[1]);
    } elseif (preg_match('/id=["\']dolar["\'][\s\S]*?<strong>\s*([0-9.,]+)\s*<\/strong>/i', $html, $mUsd)) {
        $usd = cleanNumber($mUsd[1]);
    }

    // EUR
    $eur = null;
    if (preg_match('/<span>\s*EUR\s*<\/span>.*?<strong[^>]*>\s*([0-9.,]+)\s*<\/strong>/s', $html, $mEur)) {
        $eur = cleanNumber($mEur[1]);
    } elseif (preg_match('/id=["\']euro["\'][\s\S]*?<strong>\s*([0-9.,]+)\s*<\/strong>/i', $html, $mEur)) {
        $eur = cleanNumber($mEur[1]);
    }

    return [
        'fecha' => $fecha,
        'usd' => $usd,
        'eur' => $eur
    ];
}

function fetchYadio() {
    $rawUsd = fetchUrl('https://api.yadio.io/exrates/usd', 8);
    $usdJson = $rawUsd ? json_decode($rawUsd, true) : null;

    $rawEur = fetchUrl('https://api.yadio.io/exrates/eur', 8);
    $eurJson = $rawEur ? json_decode($rawEur, true) : null;

    return [
        'usd' => isset($usdJson['USD']['VES']) ? (float)$usdJson['USD']['VES'] : null,
        'eur' => isset($eurJson['EUR']['VES']) ? (float)$eurJson['EUR']['VES'] : null
    ];
}

function cleanNumber($str) {
    if (!$str) return null;
    $clean = str_replace(' ', '', $str);
    $clean = str_replace(',', '.', $clean);
    return (float)$clean;
}
