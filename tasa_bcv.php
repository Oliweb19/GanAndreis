<?php
/**
 * Servicio de Consulta y Caché de la Tasa Oficial del BCV
 * Fuente: API DolarApi Venezuela (https://ve.dolarapi.com/v1/dolares/oficial)
 */

function obtenerTasaBCV() {
    $carpetaUploads = __DIR__ . '/uploads';
    if (!is_dir($carpetaUploads)) {
        @mkdir($carpetaUploads, 0777, true);
    }
    $archivoCache = $carpetaUploads . '/cache_tasa_bcv.json';
    $tiempoCache = 1800; // 30 minutos

    // 1. Verificar si existe caché vigente
    if (file_exists($archivoCache)) {
        $contenido = @file_get_contents($archivoCache);
        $datos = json_decode($contenido, true);
        if ($datos && isset($datos['promedio'], $datos['timestamp'])) {
            if ((time() - $datos['timestamp']) < $tiempoCache && (float)$datos['promedio'] > 0) {
                return (float)$datos['promedio'];
            }
        }
    }

    // 2. Consultar API oficial
    try {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3,
                'user_agent' => 'Mozilla/5.0 (GanAndreis System)'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $json = @file_get_contents('https://ve.dolarapi.com/v1/dolares/oficial', false, $ctx);
        if ($json) {
            $resp = json_decode($json, true);
            if (!empty($resp['promedio']) && is_numeric($resp['promedio'])) {
                $tasa = (float)$resp['promedio'];
                @file_put_contents($archivoCache, json_encode([
                    'promedio' => $tasa,
                    'fecha_bcv' => $resp['fechaActualizacion'] ?? null,
                    'timestamp' => time()
                ]));
                return $tasa;
            }
        }
    } catch (Exception $e) {
        // Fallback silencioso
    }

    // 3. Si la API falló pero tenemos una caché anterior, usarla
    if (file_exists($archivoCache)) {
        $datos = json_decode(@file_get_contents($archivoCache), true);
        if (!empty($datos['promedio']) && (float)$datos['promedio'] > 0) {
            return (float)$datos['promedio'];
        }
    }

    // 4. Fallback de seguridad
    return 875.65;
}
