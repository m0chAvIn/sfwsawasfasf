<?php
/**
 * Script PHP untuk mencari file yang mengandung kata "news"
 * Mencari dalam konten file dan nama file
 */

// Set waktu eksekusi tidak terbatas untuk pencarian besar
set_time_limit(0);

// Direktori yang akan dicari (ubah sesuai kebutuhan)
$search_directory = __DIR__; // Default: direktori script ini

// Kata kunci yang dicari
$keyword = 'news';

// Ekstensi file yang akan dicari (opsional, kosongkan untuk semua file)
$allowed_extensions = ['php', 'html', 'htm', 'txt', 'js', 'css', 'json', 'xml'];

// Hasil pencarian
$results = [
    'in_filename' => [],
    'in_content' => []
];

/**
 * Fungsi untuk mencari file secara rekursif
 */
function searchFiles($dir, $keyword, $allowed_extensions, &$results) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            // Rekursif ke subdirektori
            searchFiles($path, $keyword, $allowed_extensions, $results);
        } else {
            // Cek ekstensi file jika ditentukan
            if (!empty($allowed_extensions)) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($extension, $allowed_extensions)) {
                    continue;
                }
            }
            
            // Cek apakah keyword ada dalam nama file
            if (stripos($file, $keyword) !== false) {
                $results['in_filename'][] = [
                    'path' => $path,
                    'size' => filesize($path),
                    'modified' => date('Y-m-d H:i:s', filemtime($path))
                ];
            }
            
            // Cek apakah keyword ada dalam konten file
            try {
                $content = file_get_contents($path);
                if (stripos($content, $keyword) !== false) {
                    $results['in_content'][] = [
                        'path' => $path,
                        'size' => filesize($path),
                        'modified' => date('Y-m-d H:i:s', filemtime($path))
                    ];
                }
            } catch (Exception $e) {
                // Abaikan file yang tidak bisa dibaca
            }
        }
    }
}

// Mulai pencarian
echo "<h1>Mencari file yang mengandung kata: <span style='color:blue;'>" . htmlspecialchars($keyword) . "</span></h1>";
echo "<p>Direktori pencarian: <strong>" . htmlspecialchars($search_directory) . "</strong></p>";
echo "<hr>";

searchFiles($search_directory, $keyword, $allowed_extensions, $results);

// Tampilkan hasil
echo "<h2>File dengan kata 'news' dalam NAMA FILE (" . count($results['in_filename']) . " ditemukan)</h2>";
if (count($results['in_filename']) > 0) {
    echo "<ul>";
    foreach ($results['in_filename'] as $file) {
        echo "<li><strong>" . htmlspecialchars($file['path']) . "</strong> ";
        echo "(" . formatBytes($file['size']) . ", dimodifikasi: " . $file['modified'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Tidak ada file ditemukan.</p>";
}

echo "<h2>File dengan kata 'news' dalam KONTEN FILE (" . count($results['in_content']) . " ditemukan)</h2>";
if (count($results['in_content']) > 0) {
    echo "<ul>";
    foreach ($results['in_content'] as $file) {
        echo "<li><strong>" . htmlspecialchars($file['path']) . "</strong> ";
        echo "(" . formatBytes($file['size']) . ", dimodifikasi: " . $file['modified'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Tidak ada file ditemukan.</p>";
}

echo "<hr><p>Pencarian selesai.</p>";

/**
 * Fungsi untuk format bytes ke format yang mudah dibaca
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
