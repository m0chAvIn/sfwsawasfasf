<?php
/**
 * Script PHP Lanjutan untuk mencari file yang mengandung kata "news"
 * Dengan form pencarian dan fitur preview konten
 */

// Set waktu eksekusi tidak terbatas
set_time_limit(0);

// Konfigurasi default
$search_directory = isset($_POST['directory']) ? $_POST['directory'] : __DIR__;
$keyword = isset($_POST['keyword']) ? $_POST['keyword'] : 'news';
$file_types = isset($_POST['file_types']) ? $_POST['file_types'] : 'php,html,htm,txt,js,css,json,xml';
$case_sensitive = isset($_POST['case_sensitive']) ? true : false;
$search_in_filename = isset($_POST['search_in_filename']) ? true : false;
$search_in_content = isset($_POST['search_in_content']) ? true : false;
$preview_content = isset($_POST['preview_content']) ? true : false;
$max_file_size = isset($_POST['max_file_size']) ? intval($_POST['max_file_size']) * 1024 * 1024 : 5 * 1024 * 1024; // Default 5MB

// Parse ekstensi file
$allowed_extensions = array_filter(array_map('trim', explode(',', $file_types)));

// Hasil pencarian
$results = [
    'in_filename' => [],
    'in_content' => []
];

/**
 * Fungsi untuk mencari file secara rekursif
 */
function searchFiles($dir, $keyword, $allowed_extensions, $case_sensitive, $search_in_filename, $search_in_content, $max_file_size, &$results, $preview_content) {
    if (!is_dir($dir)) return;
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            // Rekursif ke subdirektori
            searchFiles($path, $keyword, $allowed_extensions, $case_sensitive, $search_in_filename, $search_in_content, $max_file_size, $results, $preview_content);
        } else {
            // Cek ekstensi file
            if (!empty($allowed_extensions)) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($extension, $allowed_extensions)) {
                    continue;
                }
            }
            
            $file_info = [
                'path' => $path,
                'size' => filesize($path),
                'modified' => date('Y-m-d H:i:s', filemtime($path)),
                'preview' => ''
            ];
            
            // Cek keyword dalam nama file
            if ($search_in_filename) {
                if ($case_sensitive) {
                    $found = strpos($file, $keyword) !== false;
                } else {
                    $found = stripos($file, $keyword) !== false;
                }
                
                if ($found) {
                    $results['in_filename'][] = $file_info;
                }
            }
            
            // Cek keyword dalam konten file
            if ($search_in_content) {
                if (filesize($path) < $max_file_size) {
                    try {
                        $content = file_get_contents($path);
                        
                        if ($case_sensitive) {
                            $found = strpos($content, $keyword) !== false;
                        } else {
                            $found = stripos($content, $keyword) !== false;
                        }
                        
                        if ($found) {
                            if ($preview_content) {
                                // Ambil preview konten di sekitar keyword
                                $file_info['preview'] = getContentPreview($content, $keyword, $case_sensitive);
                            }
                            $results['in_content'][] = $file_info;
                        }
                    } catch (Exception $e) {
                        // Abaikan file yang tidak bisa dibaca
                    }
                }
            }
        }
    }
}

/**
 * Fungsi untuk mendapatkan preview konten
 */
function getContentPreview($content, $keyword, $case_sensitive) {
    $preview_length = 200;
    $context_length = 50;
    
    if ($case_sensitive) {
        $pos = strpos($content, $keyword);
    } else {
        $pos = stripos($content, $keyword);
    }
    
    if ($pos !== false) {
        $start = max(0, $pos - $context_length);
        $length = min(strlen($content) - $start, $preview_length);
        $preview = substr($content, $start, $length);
        
        // Highlight keyword
        if ($case_sensitive) {
            $preview = str_replace($keyword, '<span style="background-color: yellow; font-weight: bold;">' . $keyword . '</span>', $preview);
        } else {
            $preview = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<span style="background-color: yellow; font-weight: bold;">$1</span>', $preview);
        }
        
        return $preview;
    }
    
    return '';
}

// Proses pencarian jika form disubmit
$search_performed = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search_performed = true;
    
    if (is_dir($search_directory)) {
        searchFiles($search_directory, $keyword, $allowed_extensions, $case_sensitive, 
                   $search_in_filename, $search_in_content, $max_file_size, $results, $preview_content);
    } else {
        $error = "Direktori tidak valid: " . htmlspecialchars($search_directory);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian File - News</title>
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
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .search-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"] {
            padding: 8px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="checkbox"] {
            margin-right: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .results {
            margin-top: 20px;
        }
        .result-section {
            margin-bottom: 30px;
        }
        .result-item {
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }
        .file-path {
            font-weight: bold;
            color: #0066cc;
        }
        .file-info {
            color: #666;
            font-size: 0.9em;
        }
        .preview {
            background-color: white;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 5px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .count {
            font-size: 1.1em;
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Pencarian File - Kata Kunci: News</h1>
        
        <div class="search-form">
            <form method="POST">
                <div class="form-group">
                    <label>Direktori:</label>
                    <input type="text" name="directory" value="<?php echo htmlspecialchars($search_directory); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Kata Kunci:</label>
                    <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Tipe File:</label>
                    <input type="text" name="file_types" value="<?php echo htmlspecialchars($file_types); ?>" 
                           placeholder="php,html,js,css" required>
                    <small>(pisahkan dengan koma)</small>
                </div>
                
                <div class="form-group">
                    <label>Max File Size:</label>
                    <input type="number" name="max_file_size" value="<?php echo $max_file_size / (1024*1024); ?>" min="1" max="100">
                    <small>MB (untuk preview konten)</small>
                </div>
                
                <div class="form-group">
                    <label>Opsi Pencarian:</label>
                    <input type="checkbox" name="search_in_filename" <?php echo $search_in_filename ? 'checked' : ''; ?>>
                    <span>Cari dalam Nama File</span><br>
                    
                    <input type="checkbox" name="search_in_content" <?php echo $search_in_content ? 'checked' : ''; ?>>
                    <span>Cari dalam Konten File</span><br>
                    
                    <input type="checkbox" name="case_sensitive" <?php echo $case_sensitive ? 'checked' : ''; ?>>
                    <span>Case Sensitive</span><br>
                    
                    <input type="checkbox" name="preview_content" <?php echo $preview_content ? 'checked' : ''; ?>>
                    <span>Tampilkan Preview Konten</span>
                </div>
                
                <button type="submit" name="search">🔍 Cari File</button>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($search_performed): ?>
            <div class="results">
                <h2>Hasil Pencarian untuk "<?php echo htmlspecialchars($keyword); ?>"</h2>
                
                <div class="result-section">
                    <h3>📁 File dengan kata dalam NAMA FILE 
                        <span class="count">(<?php echo count($results['in_filename']); ?> ditemukan)</span>
                    </h3>
                    <?php if (count($results['in_filename']) > 0): ?>
                        <?php foreach ($results['in_filename'] as $file): ?>
                            <div class="result-item">
                                <div class="file-path"><?php echo htmlspecialchars($file['path']); ?></div>
                                <div class="file-info">
                                    Ukuran: <?php echo formatBytes($file['size']); ?> | 
                                    Dimodifikasi: <?php echo $file['modified']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Tidak ada file ditemukan.</p>
                    <?php endif; ?>
                </div>
                
                <div class="result-section">
                    <h3>📄 File dengan kata dalam KONTEN FILE 
                        <span class="count">(<?php echo count($results['in_content']); ?> ditemukan)</span>
                    </h3>
                    <?php if (count($results['in_content']) > 0): ?>
                        <?php foreach ($results['in_content'] as $file): ?>
                            <div class="result-item">
                                <div class="file-path"><?php echo htmlspecialchars($file['path']); ?></div>
                                <div class="file-info">
                                    Ukuran: <?php echo formatBytes($file['size']); ?> | 
                                    Dimodifikasi: <?php echo $file['modified']; ?>
                                </div>
                                <?php if (!empty($file['preview'])): ?>
                                    <div class="preview"><?php echo $file['preview']; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Tidak ada file ditemukan.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
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
