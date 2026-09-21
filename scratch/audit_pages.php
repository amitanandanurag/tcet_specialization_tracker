<?php
$dirs = ['admin', 'login'];
$basePath = dirname(__DIR__);

$results = [];

foreach ($dirs as $dir) {
    $fullDir = $basePath . DIRECTORY_SEPARATOR . $dir;
    $files = scandir($fullDir);
    foreach ($files as $file) {
        if (substr($file, -4) !== '.php') continue;
        $filePath = $fullDir . DIRECTORY_SEPARATOR . $file;
        $content = file_get_contents($filePath);
        
        $issues = [];
        if (preg_match_all('/linear-gradient\([^)]+\)/i', $content, $m)) {
            $issues['gradients'] = array_unique($m[0]);
        }
        if (preg_match_all('/(#[0-9a-fA-F]{6}|#[0-9a-fA-F]{3})/i', $content, $m)) {
            $colors = array_unique($m[0]);
            $badColors = [];
            foreach ($colors as $c) {
                $uc = strtoupper($c);
                // Flag SaaS blues / neon / bright unbranded colors
                if (in_array($uc, ['#0EA5E9', '#2563EB', '#1D4ED8', '#0284C7', '#3B82F6', '#1E40AF', '#1E3A8A', '#0DF387', '#F97161', '#9C27B0', '#009688', '#FFEB3B', '#667EEA', '#764BA2', '#273C8E', '#1AA7CF', '#44C4E8', '#1596BA', '#38B6D8', '#00B09B', '#96C93D', '#F2994A', '#F2C94C', '#EB3349', '#F45C43'])) {
                    $badColors[] = $uc;
                }
            }
            if (!empty($badColors)) {
                $issues['bad_colors'] = $badColors;
            }
        }
        if (preg_match_all('/class=[\'"][^\'"]*(btn-primary|btn-info|btn-success|btn-warning|btn-danger|btn-submit|btn-reset|btn-link-like|btn-action)[^\'"]*[\'"]/i', $content, $m)) {
            $issues['legacy_buttons'] = array_unique($m[0]);
        }
        if (preg_match_all('/border-radius:\s*(20px|25px|30px|50px|9999px|16px|14px|12px|10px)/i', $content, $m)) {
            $issues['excessive_radius'] = array_unique($m[0]);
        }
        
        if (!empty($issues)) {
            $results[$dir . '/' . $file] = $issues;
        }
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
