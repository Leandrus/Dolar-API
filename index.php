<?php
/**
 * API Dólar y Euro Venezuela
 * Cotizaciones oficiales del BCV y mercado paralelo
 * Repositorio: https://github.com/Leandrus/Dolar-API
 * Licencia: MIT
 */

// Evitar que errores o notices de PHP rompan la salida JSON
error_reporting(0);
ini_set('display_errors', '0');

// Configurar zona horaria oficial de Venezuela (UTC-4)
date_default_timezone_set('America/Caracas');

// 1. Cabeceras de Seguridad y CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Responder peticiones pre-flight de navegadores (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Únicamente permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, OPTIONS');
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode([
        'error' => 405,
        'mensaje' => 'Método HTTP no permitido. Utilice GET.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// 2. Parámetros de Caché
define('CACHE_FILE', __DIR__ . '/cache.json');
define('CACHE_TTL', 900); // 15 minutos en segundos

// 3. Obtener cotizaciones con caché inteligente y tolerancia a caídas
$data = getRatesWithCache();

// Estructuras auxiliares para cotizaciones de la próxima fecha valor oficial (disponibles tras publicación de la tarde)
$dolarSiguiente = !empty($data['bcvSiguiente']['usd']) ? [
    'moneda' => 'USD',
    'fuente' => 'oficial',
    'nombre' => 'Dólar (Próxima Fecha Valor)',
    'compra' => null,
    'venta' => null,
    'promedio' => $data['bcvSiguiente']['usd'],
    'fechaActualizacion' => $data['bcvSiguiente']['fecha'],
    'fechaValor' => $data['bcvSiguiente']['fechaValor']
] : null;

$euroSiguiente = !empty($data['bcvSiguiente']['eur']) ? [
    'moneda' => 'EUR',
    'fuente' => 'oficial',
    'nombre' => 'Euro (Próxima Fecha Valor)',
    'compra' => null,
    'venta' => null,
    'promedio' => $data['bcvSiguiente']['eur'],
    'fechaActualizacion' => $data['bcvSiguiente']['fecha'],
    'fechaValor' => $data['bcvSiguiente']['fechaValor']
] : null;

// 4. Enrutamiento de peticiones
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim(strtolower($uri), '/');

switch ($path) {
    case 'v1/dolares':
    case 'dolares':
        echo json_encode([$data['dolarOficial'], $data['dolarParalelo']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/dolares/oficial':
    case 'dolares/oficial':
        echo json_encode($data['dolarOficial'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/dolares/oficial/siguiente':
    case 'dolares/oficial/siguiente':
    case 'v1/dolares/siguiente':
    case 'dolares/siguiente':
        if ($dolarSiguiente) {
            echo json_encode($dolarSiguiente, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode([
                'estado' => 'no_disponible',
                'mensaje' => 'Aún no se ha publicado una nueva fecha valor oficial. El BCV suele publicarla en las tardes de los días hábiles bancarios.',
                'fecha_vigente_actual' => $data['dolarOficial']['fechaActualizacion'] ?? null
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
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

    case 'v1/euros/oficial/siguiente':
    case 'euros/oficial/siguiente':
    case 'v1/euros/siguiente':
    case 'euros/siguiente':
        if ($euroSiguiente) {
            echo json_encode($euroSiguiente, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode([
                'estado' => 'no_disponible',
                'mensaje' => 'Aún no se ha publicado una nueva fecha valor oficial. El BCV suele publicarla en las tardes de los días hábiles bancarios.',
                'fecha_vigente_actual' => $data['euroOficial']['fechaActualizacion'] ?? null
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;

    case 'v1/euros/paralelo':
    case 'euros/paralelo':
        echo json_encode($data['euroParalelo'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/cotizaciones':
    case 'cotizaciones':
        echo json_encode([$data['dolarOficial'], $data['euroOficial']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'v1/cotizaciones/siguiente':
    case 'cotizaciones/siguiente':
        if ($dolarSiguiente && $euroSiguiente) {
            echo json_encode([$dolarSiguiente, $euroSiguiente], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode([
                'estado' => 'no_disponible',
                'mensaje' => 'Aún no se han publicado las cotizaciones oficiales para el siguiente día hábil.',
                'cotizaciones' => null
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;

    case '':
    case 'v1':
        echo json_encode([
            'servicio' => 'API Cotizaciones Venezuela (USD & EUR)',
            'estado' => 'En línea',
            'zona_horaria' => 'America/Caracas (UTC-4)',
            'fecha_vigente_bcv' => $data['dolarOficial']['fechaActualizacion'] ?? null,
            'tasa_siguiente_disponible' => !empty($data['bcvSiguiente']),
            'ultima_actualizacion_cache' => $data['_cached_at'] ?? null,
            'documentacion' => 'https://github.com/Leandrus/Dolar-API',
            'endpoints' => [
                '/v1/dolares' => 'Cotizaciones vigentes del dólar oficial (BCV) y paralelo',
                '/v1/dolares/oficial' => 'Cotización oficial BCV vigente para el día',
                '/v1/dolares/oficial/siguiente' => 'Cotización oficial BCV asignada para el siguiente día hábil (disponible tras publicación ~4pm)',
                '/v1/dolares/paralelo' => 'Cotización del dólar paralelo',
                '/v1/euros' => 'Cotizaciones vigentes del euro oficial (BCV) y paralelo',
                '/v1/euros/oficial' => 'Cotización oficial BCV vigente para el día',
                '/v1/euros/oficial/siguiente' => 'Cotización oficial BCV asignada para el siguiente día hábil (disponible tras publicación ~4pm)',
                '/v1/euros/paralelo' => 'Cotización del euro paralelo',
                '/v1/cotizaciones' => 'Cotizaciones oficiales BCV vigentes del día (Dólar y Euro)',
                '/v1/cotizaciones/siguiente' => 'Cotizaciones oficiales BCV para el siguiente día hábil'
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
                '/v1/dolares/oficial/siguiente',
                '/v1/dolares/paralelo',
                '/v1/euros',
                '/v1/euros/oficial',
                '/v1/euros/oficial/siguiente',
                '/v1/euros/paralelo',
                '/v1/cotizaciones',
                '/v1/cotizaciones/siguiente'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
}

// ==========================================
// FUNCIONES DE EXTRACCIÓN Y CACHÉ
// ==========================================

function getRatesWithCache() {
    $now = time();
    $todayVET = date('Y-m-d');
    $cached = null;

    if (file_exists(CACHE_FILE)) {
        $content = @file_get_contents(CACHE_FILE);
        $cached = json_decode($content, true);

        if ($cached) {
            // Si hay una cotización futura pendiente y ya llegó su fecha valor (a partir de medianoche VET), promoverla de inmediato
            if (!empty($cached['bcvSiguiente']['fechaValor']) && $todayVET >= $cached['bcvSiguiente']['fechaValor']) {
                $cached['dolarOficial']['promedio'] = $cached['bcvSiguiente']['usd'];
                $cached['dolarOficial']['fechaActualizacion'] = $cached['bcvSiguiente']['fecha'];
                if (!empty($cached['bcvSiguiente']['eur'])) {
                    $cached['euroOficial']['promedio'] = $cached['bcvSiguiente']['eur'];
                }
                $cached['euroOficial']['fechaActualizacion'] = $cached['bcvSiguiente']['fecha'];
                $cached['bcvSiguiente'] = null;
                $cached['_cached_at'] = date('c');
                @file_put_contents(CACHE_FILE, json_encode($cached, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

                if (isset($cached['_timestamp']) && ($now - $cached['_timestamp'] < CACHE_TTL)) {
                    return $cached;
                }
            }

            // Si la caché tiene menos de 15 minutos, responder inmediatamente
            if (isset($cached['_timestamp']) && ($now - $cached['_timestamp'] < CACHE_TTL)) {
                return $cached;
            }
        }
    }

    // Intentamos extraer datos en vivo
    $bcv = fetchBcv();
    if ($bcv['usd'] === null || $bcv['eur'] === null || $bcv['fechaValor'] === null) {
        $fbBcv = fetchBcvFallback();
        if ($fbBcv) {
            if ($bcv['usd'] === null && $fbBcv['usd'] !== null) {
                $bcv['usd'] = $fbBcv['usd'];
            }
            if ($bcv['eur'] === null && $fbBcv['eur'] !== null) {
                $bcv['eur'] = $fbBcv['eur'];
            }
            if ($bcv['fechaValor'] === null && $fbBcv['fechaValor'] !== null) {
                $bcv['fechaValor'] = $fbBcv['fechaValor'];
                $bcv['fecha'] = $fbBcv['fecha'];
            }
        }
    }

    $yadio = fetchYadio();

    // 1. Manejo del BCV respetando el día oficial
    $bcvSiguiente = $cached['bcvSiguiente'] ?? null;
    if (!empty($bcvSiguiente['fechaValor']) && $bcvSiguiente['fechaValor'] <= $todayVET) {
        $bcvSiguiente = null;
    }

    $dolarOficial = null;
    $euroOficial = null;
    $bcvFailed = false;

    if ($bcv['usd'] !== null && $bcv['fechaValor'] !== null) {
        // ¿La fecha asignada por el BCV es posterior al día de hoy en Venezuela? (publicación de las ~4pm para el día siguiente)
        if ($bcv['fechaValor'] > $todayVET) {
            // Guardamos la tasa futura para que entre en vigencia oficial a partir de medianoche
            $bcvSiguiente = [
                'usd' => $bcv['usd'],
                'eur' => $bcv['eur'],
                'fecha' => $bcv['fecha'],
                'fechaValor' => $bcv['fechaValor']
            ];

            $cachedBcvDate = isset($cached['dolarOficial']['fechaActualizacion'])
                ? substr((string)$cached['dolarOficial']['fechaActualizacion'], 0, 10)
                : '';
            $hasFreshCachedBcv = (
                $cached
                && isset($cached['dolarOficial']['promedio'])
                && $cached['dolarOficial']['promedio'] !== null
                && $cachedBcvDate >= $todayVET
            );

            if ($hasFreshCachedBcv) {
                // Para la entrega actual, mantenemos el valor vigente del día guardado en caché
                $dolarOficial = $cached['dolarOficial'];
                $euroOficial = $cached['euroOficial'];
            } else {
                // Si la caché previa está obsoleta o vacía, intentar recuperar la tasa oficial vigente de hoy vía respaldo
                $todayFb = fetchBcvTodayFallback($todayVET);
                if (
                    $todayFb !== null
                    && $todayFb['usd'] !== null
                    && ($cachedBcvDate === '' || $todayFb['fechaValor'] > $cachedBcvDate || ($cached['dolarOficial']['promedio'] ?? null) === null)
                ) {
                    $dolarOficial = [
                        'moneda' => 'USD',
                        'fuente' => 'oficial',
                        'nombre' => 'Dólar',
                        'compra' => null,
                        'venta' => null,
                        'promedio' => $todayFb['usd'],
                        'fechaActualizacion' => $todayFb['fecha']
                    ];
                    $eurVal = $todayFb['eur'] ?? ($cached['euroOficial']['promedio'] ?? $bcv['eur']);
                    $euroOficial = [
                        'moneda' => 'EUR',
                        'fuente' => 'oficial',
                        'nombre' => 'Euro',
                        'compra' => null,
                        'venta' => null,
                        'promedio' => $eurVal,
                        'fechaActualizacion' => $todayFb['fecha']
                    ];
                } elseif ($cached && isset($cached['dolarOficial']['promedio']) && $cached['dolarOficial']['promedio'] !== null) {
                    $dolarOficial = $cached['dolarOficial'];
                    $euroOficial = $cached['euroOficial'];
                } else {
                    // Último recurso si no hay caché ni histórico vigente
                    $dolarOficial = [
                        'moneda' => 'USD',
                        'fuente' => 'oficial',
                        'nombre' => 'Dólar',
                        'compra' => null,
                        'venta' => null,
                        'promedio' => $bcv['usd'],
                        'fechaActualizacion' => $bcv['fecha']
                    ];
                    $euroOficial = [
                        'moneda' => 'EUR',
                        'fuente' => 'oficial',
                        'nombre' => 'Euro',
                        'compra' => null,
                        'venta' => null,
                        'promedio' => $bcv['eur'],
                        'fechaActualizacion' => $bcv['fecha']
                    ];
                }
            }
        } else {
            // La fecha reportada es igual o anterior al día de hoy: es el valor oficial vigente para hoy
            $bcvSiguiente = null; // ya no hay tasa futura pendiente
            $dolarOficial = [
                'moneda' => 'USD',
                'fuente' => 'oficial',
                'nombre' => 'Dólar',
                'compra' => null,
                'venta' => null,
                'promedio' => $bcv['usd'],
                'fechaActualizacion' => $bcv['fecha']
            ];
            $eurVal = $bcv['eur'] ?? ($cached['euroOficial']['promedio'] ?? null);
            $euroOficial = [
                'moneda' => 'EUR',
                'fuente' => 'oficial',
                'nombre' => 'Euro',
                'compra' => null,
                'venta' => null,
                'promedio' => $eurVal,
                'fechaActualizacion' => $bcv['fecha']
            ];
        }
    } else {
        $bcvFailed = true;
        // Fallback si el portal BCV no responde o sufre caídas temporales
        if ($cached && isset($cached['dolarOficial']['promedio']) && $cached['dolarOficial']['promedio'] !== null) {
            $dolarOficial = $cached['dolarOficial'];
            $euroOficial = $cached['euroOficial'];
        } else {
            $isoDate = date('Y-m-d\TH:i:sP');
            $dolarOficial = [
                'moneda' => 'USD',
                'fuente' => 'oficial',
                'nombre' => 'Dólar',
                'compra' => null,
                'venta' => null,
                'promedio' => null,
                'fechaActualizacion' => $isoDate
            ];
            $euroOficial = [
                'moneda' => 'EUR',
                'fuente' => 'oficial',
                'nombre' => 'Euro',
                'compra' => null,
                'venta' => null,
                'promedio' => null,
                'fechaActualizacion' => $isoDate
            ];
        }
    }

    // 2. Manejo de Paralelo (Yadio) de forma independiente con tolerancia a fallos
    $isoParaleloDate = gmdate('Y-m-d\TH:i:s.v\Z');

    $usdParaleloVal = $yadio['usd'];
    if ($usdParaleloVal === null || $usdParaleloVal <= 0) {
        $usdParaleloVal = fetchParaleloFallback();
    }

    if ($usdParaleloVal !== null && $usdParaleloVal > 0) {
        $dolarParalelo = [
            'moneda' => 'USD',
            'fuente' => 'paralelo',
            'nombre' => 'Paralelo',
            'compra' => null,
            'venta' => null,
            'promedio' => $usdParaleloVal,
            'fechaActualizacion' => $isoParaleloDate
        ];
    } elseif ($cached && isset($cached['dolarParalelo'])) {
        $dolarParalelo = $cached['dolarParalelo'];
    } else {
        $dolarParalelo = [
            'moneda' => 'USD',
            'fuente' => 'paralelo',
            'nombre' => 'Paralelo',
            'compra' => null,
            'venta' => null,
            'promedio' => null,
            'fechaActualizacion' => $isoParaleloDate
        ];
    }

    if ($yadio['eur'] !== null && $yadio['eur'] > 0) {
        $euroParalelo = [
            'moneda' => 'EUR',
            'fuente' => 'paralelo',
            'nombre' => 'Paralelo',
            'compra' => null,
            'venta' => null,
            'promedio' => $yadio['eur'],
            'fechaActualizacion' => $isoParaleloDate
        ];
    } elseif ($cached && isset($cached['euroParalelo']['promedio']) && $cached['euroParalelo']['promedio'] !== null) {
        $euroParalelo = $cached['euroParalelo'];
    } else {
        $euroParalelo = [
            'moneda' => 'EUR',
            'fuente' => 'paralelo',
            'nombre' => 'Paralelo',
            'compra' => null,
            'venta' => null,
            'promedio' => null,
            'fechaActualizacion' => $isoParaleloDate
        ];
    }

    // Si BCV falló en vivo y en respaldo, reintentar en 2 minutos en vez de cachear 15 minutos
    $effectiveTimestamp = $bcvFailed ? ($now - CACHE_TTL + 120) : $now;

    $result = [
        '_timestamp' => $effectiveTimestamp,
        '_cached_at' => date('c'),
        'dolarOficial' => $dolarOficial,
        'dolarParalelo' => $dolarParalelo,
        'euroOficial' => $euroOficial,
        'euroParalelo' => $euroParalelo,
        'bcvSiguiente' => $bcvSiguiente
    ];

    // Guardar en archivo local con bloqueo exclusivo
    @file_put_contents(CACHE_FILE, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    return $result;
}

function fetchUrl($url, $timeout = 10) {
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => min(6, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT => $userAgent
        ]);
        $res = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 200 && is_string($res) && $res !== '') {
            return $res;
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'header' => "User-Agent: {$userAgent}\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    $streamRes = @file_get_contents($url, false, $ctx);
    return (is_string($streamRes) && $streamRes !== '') ? $streamRes : null;
}

function extractBcvDate($html) {
    if (!$html) {
        return ['iso' => null, 'ymd' => null];
    }

    // 1. Extraer específicamente la 'Fecha Valor' indicada por el BCV
    if (preg_match('/Fecha\s*Valor:[\s\S]{0,400}?content=["\'](\d{4}-\d{2}-\d{2}[^"\']*)["\']/i', $html, $m)) {
        $raw = trim($m[1]);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $mDate)) {
            return ['iso' => $raw, 'ymd' => $mDate[1]];
        }
    }

    // 2. Extraer mediante atributo content cuando class aparece antes de content
    if (preg_match('/<span[^>]*class=["\'][^"\']*date-display-single[^"\']*["\'][^>]*content=["\'](\d{4}-\d{2}-\d{2}[^"\']*)["\']/i', $html, $m)) {
        $raw = trim($m[1]);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $mDate)) {
            return ['iso' => $raw, 'ymd' => $mDate[1]];
        }
    }

    // 3. Extraer mediante atributo content cuando content aparece antes de class (estructura actual BCV)
    if (preg_match('/<span[^>]*content=["\'](\d{4}-\d{2}-\d{2}[^"\']*)["\'][^>]*class=["\'][^"\']*date-display-single[^"\']*["\']/i', $html, $m)) {
        $raw = trim($m[1]);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $mDate)) {
            return ['iso' => $raw, 'ymd' => $mDate[1]];
        }
    }

    // 4. Extraer mediante texto visible como respaldo (ej: "Jueves, 19 Noviembre 2026")
    if (preg_match('/class=["\'][^"\']*date-display-single[^"\']*["\'][^>]*>\s*([^<]+)\s*<\/span>/i', $html, $m)) {
        $text = trim($m[1]);
        $meses = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'setiembre' => '09', 'octubre' => '10',
            'noviembre' => '11', 'diciembre' => '12'
        ];
        if (preg_match('/(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/i', $text, $mPartes)) {
            $dia = str_pad($mPartes[1], 2, '0', STR_PAD_LEFT);
            $mes = $meses[strtolower($mPartes[2])] ?? null;
            $anio = $mPartes[3];
            if ($mes !== null) {
                $ymd = "$anio-$mes-$dia";
                return ['iso' => "{$ymd}T00:00:00-04:00", 'ymd' => $ymd];
            }
        }
    }

    return ['iso' => null, 'ymd' => null];
}

function fetchBcv() {
    $html = fetchUrl('https://www.bcv.org.ve/', 12);
    if (!$html) {
        return ['fecha' => null, 'fechaValor' => null, 'usd' => null, 'eur' => null];
    }

    // Fecha reportada por el BCV
    $dateInfo = extractBcvDate($html);

    // USD (resiliente a atributos como style="..." y class="strong-tb")
    $usd = null;
    if (preg_match('/id=["\']dolar["\'][\s\S]{0,1200}?<strong[^>]*>\s*([0-9.,]+)\s*<\/strong>/i', $html, $mUsd)) {
        $usd = cleanNumber($mUsd[1]);
    } elseif (preg_match('/<span[^>]*>\s*USD\s*<\/span>[\s\S]{0,600}?<strong[^>]*>\s*([0-9.,]+)\s*<\/strong>/i', $html, $mUsd)) {
        $usd = cleanNumber($mUsd[1]);
    }

    // EUR (resiliente a atributos como style="..." y class="strong-tb")
    $eur = null;
    if (preg_match('/id=["\']euro["\'][\s\S]{0,1200}?<strong[^>]*>\s*([0-9.,]+)\s*<\/strong>/i', $html, $mEur)) {
        $eur = cleanNumber($mEur[1]);
    } elseif (preg_match('/<span[^>]*>\s*EUR\s*<\/span>[\s\S]{0,600}?<strong[^>]*>\s*([0-9.,]+)\s*<\/strong>/i', $html, $mEur)) {
        $eur = cleanNumber($mEur[1]);
    }

    return [
        'fecha' => $dateInfo['iso'],
        'fechaValor' => $dateInfo['ymd'],
        'usd' => $usd,
        'eur' => $eur
    ];
}

function fetchBcvFallback() {
    $raw = fetchUrl('https://ve.dolarapi.com/v1/cotizaciones', 10);
    $items = $raw ? json_decode($raw, true) : null;
    if (!is_array($items)) {
        return null;
    }

    $usd = null;
    $eur = null;
    $fecha = null;
    $fechaValor = null;

    foreach ($items as $item) {
        if (!is_array($item) || ($item['fuente'] ?? '') !== 'oficial') {
            continue;
        }
        $val = $item['promedio'] ?? null;
        if (!is_numeric($val) || (float)$val <= 0) {
            continue;
        }
        $moneda = strtoupper((string)($item['moneda'] ?? ''));
        $rawFecha = $item['fechaActualizacion'] ?? null;
        if ($rawFecha && preg_match('/^(\d{4}-\d{2}-\d{2})/', (string)$rawFecha, $mDate)) {
            if ($fechaValor === null) {
                $fecha = (string)$rawFecha;
                $fechaValor = $mDate[1];
            }
        }
        if ($moneda === 'USD') {
            $usd = (float)$val;
        } elseif ($moneda === 'EUR') {
            $eur = (float)$val;
        }
    }

    if ($usd === null || $fechaValor === null) {
        return null;
    }

    return [
        'fecha' => $fecha,
        'fechaValor' => $fechaValor,
        'usd' => $usd,
        'eur' => $eur
    ];
}

function fetchBcvTodayFallback($todayVET) {
    $fb = fetchBcvFallback();
    if ($fb !== null && $fb['fechaValor'] !== null && $fb['fechaValor'] <= $todayVET) {
        return $fb;
    }

    $rawHist = fetchUrl('https://ve.dolarapi.com/v1/historicos/dolares/oficial', 12);
    $hist = $rawHist ? json_decode($rawHist, true) : null;
    if (!is_array($hist)) {
        return null;
    }

    $bestDate = null;
    $bestUsd = null;
    foreach ($hist as $item) {
        if (!is_array($item)) {
            continue;
        }
        $fecha = isset($item['fecha']) ? substr((string)$item['fecha'], 0, 10) : '';
        $val = $item['promedio'] ?? null;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha > $todayVET || !is_numeric($val) || (float)$val <= 0) {
            continue;
        }
        if ($bestDate === null || $fecha > $bestDate) {
            $bestDate = $fecha;
            $bestUsd = (float)$val;
        }
    }

    if ($bestDate === null || $bestUsd === null) {
        return null;
    }

    return [
        'fecha' => $bestDate . 'T00:00:00-04:00',
        'fechaValor' => $bestDate,
        'usd' => $bestUsd,
        'eur' => null
    ];
}

function fetchParaleloFallback() {
    $raw = fetchUrl('https://ve.dolarapi.com/v1/dolares/paralelo', 10);
    $json = $raw ? json_decode($raw, true) : null;
    if (is_array($json) && isset($json['promedio']) && is_numeric($json['promedio']) && (float)$json['promedio'] > 0) {
        return (float)$json['promedio'];
    }
    return null;
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
    $clean = trim(preg_replace('/[^\d.,]/', '', $str));
    if (strpos($clean, '.') !== false && strpos($clean, ',') !== false) {
        if (strrpos($clean, '.') < strrpos($clean, ',')) {
            // Formato estándar venezolano / europeo: 1.234,56
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            // Formato anglosajón: 1,234.56
            $clean = str_replace(',', '', $clean);
        }
    } elseif (strpos($clean, ',') !== false) {
        $clean = str_replace(',', '.', $clean);
    }
    return is_numeric($clean) ? (float)$clean : null;
}
