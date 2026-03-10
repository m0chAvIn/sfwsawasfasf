<?php
/**
 * Script PHP untuk melihat file dengan atribut immutable (chattr +i)
 * dan menampilkan atribut file menggunakan lsattr
 */

// Set waktu eksekusi tidak terbatas
set_time_limit(0);

// Direktori yang akan diperiksa (ubah sesuai kebutuhan)
$scan_directory = __DIR__; // Default: direktori script ini

// Warna untuk output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'magenta' => "\033[35m",
    'cyan' => "\033[36m",
    'white' => "\033[37m"
];

// Cek apakah fungsi exec tersedia
function isExecAvailable() {
    $disabled = explode(',', ini_get('disable_functions'));
    return function_exists('exec') && !in_array('exec', $disabled);
}

// Cek apakah perintah lsattr tersedia
function isLsattrAvailable() {
    if (!isExecAvailable()) return false;
    
    exec('which lsattr 2>/dev/null', $output, $return_var);
    return $return_var === 0 && !empty($output);
}

// Format atribut untuk ditampilkan
function formatAttribute($attr) {
    global $colors;
    
    $result = '';
    $attrs = str_split($attr);
    
    foreach ($attrs as $char) {
        switch ($char) {
            case 'i':
                $result .= $colors['red'] . 'i' . $colors['reset']; // immutable
                break;
            case 'a':
                $result .= $colors['yellow'] . 'a' . $colors['reset']; // append only
                break;
            case 'd':
                $result .= $colors['green'] . 'd' . $colors['reset']; // no dump
                break;
            case 'e':
                $result .= $colors['cyan'] . 'e' . $colors['reset']; // extent format
                break;
            case 'j':
                $result .= $colors['blue'] . 'j' . $colors['reset']; // data journalling
                break;
            case 's':
                $result .= $colors['magenta'] . 's' . $colors['reset']; // secure deletion
                break;
            case 'u':
                $result .= $colors['green'] . 'u' . $colors['reset']; // undeletable
                break;
            case 'c':
                $result .= $colors['cyan'] . 'c' . $colors['reset']; // compressed
                break;
            case 'D':
                $result .= $colors['yellow'] . 'D' . $colors['reset']; // synchronous directory
                break;
            default:
                $result .= $char;
        }
    }
    
    return $result;
}

// Dapatkan atribut file menggunakan lsattr
function getFileAttributes($path) {
    if (!isExecAvailable() || !isLsattrAvailable()) {
        return ['error' => 'lsattr tidak tersedia atau exec dinonaktifkan'];
    }
    
    $output = [];
    $return_var = 0;
    
    // Escape path untuk keamanan
    $escaped_path = escapeshellarg($path);
    exec("lsattr $escaped_path 2>&1", $output, $return_var);
    
    if ($return_var !== 0) {
        return ['error' => 'Gagal menjalankan lsattr: ' . implode("\n", $output)];
    }
    
    return ['success' => $output];
}

// Scan direktori untuk file dengan atribut immutable
function scanForImmutable($dir, &$results) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            // Rekursif untuk subdirektori
            scanForImmutable($path, $results);
        } else {
            // Dapatkan atribut file
            $attr_result = getFileAttributes($path);
            
            if (isset($attr_result['success'])) {
                foreach ($attr_result['success'] as $line) {
                    // Format lsattr: [attributes] [filename]
                    if (preg_match('/^([a-zA-Z\-]+)\s+(.+)$/', $line, $matches)) {
                        $attributes = $matches[1];
                        $filepath = $matches[2];
                        
                        // Cek apakah ada atribut 'i' (immutable)
                        if (strpos($attributes, 'i') !== false) {
                            $results['immutable'][] = [
                                'path' => $filepath,
                                'attributes' => $attributes,
                                'size' => filesize($path),
                                'modified' => date('Y-m-d H:i:s', filemtime($path))
                            ];
                        }
                        
                        // Simpan semua atribut untuk referensi
                        $results['all_attributes'][] = [
                            'path' => $filepath,
                            'attributes' => $attributes,
                            'size' => filesize($path),
                            'modified' => date('Y-m-d H:i:s', filemtime($path))
                        ];
                    }
                }
            }
        }
    }
}

// Tampilan HTML untuk mode web
function displayWebResults($results, $scan_directory) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>File Immutable Viewer</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 20px;
                background-color: #f5f5f5;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background-color: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
                border-bottom: 2px solid #dc3545;
                padding-bottom: 10px;
            }
            h2 {
                color: #495057;
                margin-top: 30px;
            }
            .info-box {
                background-color: #e7f3ff;
                border-left: 4px solid #2196F3;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .warning-box {
                background-color: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .error-box {
                background-color: #f8d7da;
                border-left: 4px solid #dc3545;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            th {
                background-color: #343a40;
                color: white;
                padding: 12px;
                text-align: left;
            }
            td {
                padding: 10px;
                border-bottom: 1px solid #dee2e6;
            }
            tr:hover {
                background-color: #f8f9fa;
            }
            .immutable {
                background-color: #fff5f5;
            }
            .immutable td:first-child {
                border-left: 3px solid #dc3545;
            }
            .attribute-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 4px;
                font-family: monospace;
                font-weight: bold;
            }
            .attr-i {
                background-color: #dc3545;
                color: white;
            }
            .attr-a {
                background-color: #ffc107;
                color: black;
            }
            .attr-e {
                background-color: #17a2b8;
                color: white;
            }
            .attr-other {
                background-color: #6c757d;
                color: white;
            }
            .legend {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 20px;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 4px;
            }
            .legend-item {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .count-badge {
                background-color: #007bff;
                color: white;
                padding: 5px 10px;
                border-radius: 20px;
                font-size: 0.9em;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔒 File Immutable Viewer (lsattr)</h1>
            
            <div class="info-box">
                <strong>📁 Direktori yang dipindai:</strong> <?php echo htmlspecialchars($scan_directory); ?><br>
                <strong>⏱ Waktu pemindaian:</strong> <?php echo date('Y-m-d H:i:s'); ?>
            </div>
            
            <?php if (!isExecAvailable() || !isLsattrAvailable()): ?>
                <div class="error-box">
                    <strong>❌ Error:</strong> 
                    <?php if (!isExecAvailable()): ?>
                        Fungsi exec() tidak tersedia atau dinonaktifkan.
                    <?php elseif (!isLsattrAvailable()): ?>
                        Perintah lsattr tidak ditemukan. Pastikan e2fsprogs terinstal.
                    <?php endif; ?>
                    <br>
                    <small>Untuk menginstal: sudo apt-get install e2fsprogs (Ubuntu/Debian) atau sudo yum install e2fsprogs (CentOS/RHEL)</small>
                </div>
            <?php endif; ?>
            
            <div class="legend">
                <div class="legend-item"><span class="attribute-badge attr-i">i</span> Immutable (tidak bisa diubah/dihapus)</div>
                <div class="legend-item"><span class="attribute-badge attr-a">a</span> Append only (hanya bisa ditambah)</div>
                <div class="legend-item"><span class="attribute-badge attr-e">e</span> Extent format</div>
                <div class="legend-item"><span class="attribute-badge attr-other">d</span> No dump</div>
                <div class="legend-item"><span class="attribute-badge attr-other">j</span> Data journalling</div>
                <div class="legend-item"><span class="attribute-badge attr-other">s</span> Secure deletion</div>
                <div class="legend-item"><span class="attribute-badge attr-other">u</span> Undeletable</div>
                <div class="legend-item"><span class="attribute-badge attr-other">c</span> Compressed</div>
                <div class="legend-item"><span class="attribute-badge attr-other">D</span> Synchronous directory</div>
                <div class="legend-item"><span class="attribute-badge" style="background-color: #28a745;">-</span> Tidak ada atribut khusus</div>
            </div>
            
            <?php if (isset($results['immutable']) && count($results['immutable']) > 0): ?>
                <h2>🔴 File Immutable (Atribut i) 
                    <span class="count-badge"><?php echo count($results['immutable']); ?> ditemukan</span>
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Path File</th>
                            <th>Atribut</th>
                            <th>Ukuran</th>
                            <th>Terakhir Dimodifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['immutable'] as $file): ?>
                            <tr class="immutable">
                                <td><strong><?php echo htmlspecialchars($file['path']); ?></strong></td>
                                <td>
                                    <?php 
                                    $attrs = str_split($file['attributes']);
                                    foreach ($attrs as $attr) {
                                        $class = 'attr-other';
                                        if ($attr == 'i') $class = 'attr-i';
                                        elseif ($attr == 'a') $class = 'attr-a';
                                        elseif ($attr == 'e') $class = 'attr-e';
                                        elseif ($attr == '-') $class = 'attr-other';
                                        echo '<span class="attribute-badge ' . $class . '">' . $attr . '</span> ';
                                    }
                                    ?>
                                </td>
                                <td><?php echo formatBytes($file['size']); ?></td>
                                <td><?php echo $file['modified']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="warning-box">
                    <strong>📢 Informasi:</strong> Tidak ditemukan file dengan atribut immutable (i) di direktori ini.
                </div>
            <?php endif; ?>
            
            <?php if (isset($results['all_attributes']) && count($results['all_attributes']) > 0): ?>
                <h2>📋 Semua File dengan Atribut Khusus 
                    <span class="count-badge"><?php echo count($results['all_attributes']); ?> ditemukan</span>
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Path File</th>
                            <th>Atribut</th>
                            <th>Ukuran</th>
                            <th>Terakhir Dimodifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['all_attributes'] as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($file['path']); ?></td>
                                <td>
                                    <?php 
                                    $attrs = str_split($file['attributes']);
                                    foreach ($attrs as $attr) {
                                        $class = 'attr-other';
                                        if ($attr == 'i') $class = 'attr-i';
                                        elseif ($attr == 'a') $class = 'attr-a';
                                        elseif ($attr == 'e') $class = 'attr-e';
                                        elseif ($attr == '-') continue;
                                        echo '<span class="attribute-badge ' . $class . '">' . $attr . '</span> ';
                                    }
                                    ?>
                                </td>
                                <td><?php echo formatBytes($file['size']); ?></td>
                                <td><?php echo $file['modified']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="info-box">
                <strong>💡 Informasi:</strong>
                <ul>
                    <li>File dengan atribut <strong>immutable (i)</strong> tidak dapat dimodifikasi, dihapus, atau diganti namanya bahkan oleh root.</li>
                    <li>Untuk menghapus atribut immutable: <code>sudo chattr -i [nama_file]</code></li>
                    <li>Untuk menambah atribut immutable: <code>sudo chattr +i [nama_file]</code></li>
                    <li>Atribut ini hanya bekerja pada filesystem Linux (ext2/ext3/ext4).</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// Tampilan CLI
function displayCLIResults($results, $scan_directory, $colors) {
    echo $colors['cyan'] . "========================================\n" . $colors['reset'];
    echo $colors['white'] . "🔒 FILE IMMUTABLE VIEWER (lsattr)\n" . $colors['reset'];
    echo $colors['cyan'] . "========================================\n" . $colors['reset'];
    echo "Direktori: " . $colors['green'] . $scan_directory . $colors['reset'] . "\n";
    echo "Waktu: " . date('Y-m-d H:i:s') . "\n\n";
    
    if (!isExecAvailable() || !isLsattrAvailable()) {
        echo $colors['red'] . "❌ Error: " . $colors['reset'];
        if (!isExecAvailable()) {
            echo "Fungsi exec() tidak tersedia atau dinonaktifkan.\n";
        } elseif (!isLsattrAvailable()) {
            echo "Perintah lsattr tidak ditemukan. Pastikan e2fsprogs terinstal.\n";
        }
        return;
    }
    
    if (isset($results['immutable']) && count($results['immutable']) > 0) {
        echo $colors['red'] . "🔴 FILE IMMUTABLE (Atribut i): " . count($results['immutable']) . " ditemukan\n" . $colors['reset'];
        echo str_repeat("-", 80) . "\n";
        
        foreach ($results['immutable'] as $index => $file) {
            printf("%-3d %-50s %-10s %s\n", 
                $index + 1, 
                substr($file['path'], -50), 
                formatBytes($file['size']),
                $file['modified']
            );
            echo "   Atribut: " . formatAttribute($file['attributes']) . "\n";
        }
        echo "\n";
    } else {
        echo $colors['yellow'] . "📢 Informasi: Tidak ditemukan file dengan atribut immutable.\n" . $colors['reset'];
    }
    
    if (isset($results['all_attributes']) && count($results['all_attributes']) > 0) {
        echo $colors['cyan'] . "📋 SEMUA FILE DENGAN ATRIBUT KHUSUS: " . count($results['all_attributes']) . " ditemukan\n" . $colors['reset'];
        echo str_repeat("-", 80) . "\n";
        
        foreach ($results['all_attributes'] as $index => $file) {
            if (strpos($file['attributes'], 'i') === false) { // Jangan tampilkan ulang file immutable
                printf("%-3d %-50s Atribut: %s\n", 
                    $index + 1, 
                    substr($file['path'], -50),
                    formatAttribute($file['attributes'])
                );
            }
        }
    }
    
    echo $colors['cyan'] . "\n========================================\n" . $colors['reset'];
    echo "Informasi:\n";
    echo "- File dengan atribut i (immutable) tidak bisa dimodifikasi/dihapus\n";
    echo "- Untuk menghapus atribut: sudo chattr -i [nama_file]\n";
    echo "- Untuk menambah atribut: sudo chattr +i [nama_file]\n";
}

// Mulai eksekusi
$results = [
    'immutable' => [],
    'all_attributes' => []
];

// Scan direktori
if (is_dir($scan_directory) && isExecAvailable() && isLsattrAvailable()) {
    scanForImmutable($scan_directory, $results);
}

// Deteksi environment (CLI atau Web)
if (php_sapi_name() === 'cli') {
    // Mode CLI
    displayCLIResults($results, $scan_directory, $colors);
} else {
    // Mode Web
    displayWebResults($results, $scan_directory);
}

/**
 * Fungsi untuk format bytes
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
