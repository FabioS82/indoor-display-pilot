<?php
// Aumenta limites para ficheiros grandes
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 0); // Sem limite de tempo
ini_set('output_buffering', 'off');

// Pasta onde os backups serão guardados (mesma do script)
$backupDir = __DIR__;
$siteRoot = $_SERVER['DOCUMENT_ROOT']; // Diretório principal do site

// Função para criar o backup em ZIP
function criarBackup($source, $destination) {
    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
        return false;
    }

    $source = realpath($source);

    if (is_dir($source)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $file = realpath($file);

            if (is_dir($file)) {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            } else if (is_file($file)) {
                $zip->addFile($file, str_replace($source . '/', '', $file));
            }
        }
    } elseif (is_file($source)) {
        $zip->addFile($source, basename($source));
    }

    return $zip->close();
}

// Criar backup
if (isset($_POST['dir'])) {
    $dir = trim($_POST['dir']);
    $nomeBackup = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.zip';
    if (criarBackup($dir, $nomeBackup)) {
        echo "<p>✅ Backup criado com sucesso: " . basename($nomeBackup) . "</p>";
    } else {
        echo "<p>❌ Erro ao criar backup. Verifique o diretório.</p>";
    }
}

// Upload de arquivo
if (isset($_FILES['upload_file'])) {
    $fileTmp = $_FILES['upload_file']['tmp_name'];
    $fileName = basename($_FILES['upload_file']['name']);
    $destPath = $backupDir . '/' . $fileName;

    if (move_uploaded_file($fileTmp, $destPath)) {
        echo "<p>✅ Arquivo enviado com sucesso: " . htmlspecialchars($fileName) . "</p>";
    } else {
        echo "<p>❌ Erro ao enviar arquivo.</p>";
    }
}

// Apagar ficheiro de backup
if (isset($_GET['delete']) && file_exists($backupDir . '/' . $_GET['delete'])) {
    unlink($backupDir . '/' . $_GET['delete']);
    echo "<p>🗑️ Backup apagado: " . htmlspecialchars($_GET['delete']) . "</p>";
}

// Apagar error_log
if (isset($_GET['delete_error_log'])) {
    $errorLogPath = $backupDir . '/error_log';
    if (file_exists($errorLogPath)) {
        unlink($errorLogPath);
        echo "<p>🗑️ Arquivo error_log apagado com sucesso.</p>";
    } else {
        echo "<p>⚠️ Nenhum error_log encontrado neste diretório.</p>";
    }
}

// Apagar o próprio script
if (isset($_GET['delete_self'])) {
    unlink(__FILE__);
    exit("📄 Script apagado com sucesso.");
}

// Download de backup
if (isset($_GET['download']) && file_exists($backupDir . '/' . $_GET['download'])) {
    $file = $backupDir . '/' . $_GET['download'];
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Backup Manager</title>
</head>
<body>
<h1>Gerenciador de Backups</h1>

<p><strong>📂 Diretório principal do site:</strong> <?php echo htmlspecialchars($siteRoot); ?></p>

<!-- Formulário de backup -->
<form method="post">
    <label>Diretório para backup:</label>
    <input type="text" name="dir" value="<?php echo htmlspecialchars($siteRoot); ?>" required>
    <button type="submit">Criar Backup</button>
</form>

<!-- Formulário de upload -->
<h2>📤 Enviar arquivo para este diretório</h2>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="upload_file" required>
    <button type="submit">Enviar</button>
</form>

<h2>Backups Existentes:</h2>
<ul>
<?php
$files = glob($backupDir . '/backup_*.zip');
if ($files) {
    foreach ($files as $file) {
        $base = basename($file);
        echo "<li>$base 
            [<a href='?download=$base'>Download</a>] 
            [<a href='?delete=$base' onclick='return confirm(\"Apagar este backup?\")'>Apagar</a>]
        </li>";
    }
} else {
    echo "<li>Nenhum backup encontrado.</li>";
}
?>
</ul>

<hr>
<a href="?delete_error_log=1" onclick="return confirm('Apagar o arquivo error_log deste diretório?')">🗑️ Apagar error_log</a><br>
<a href="?delete_self=1" onclick="return confirm('Tem certeza que deseja apagar este script?')">🗑️ Apagar este script</a>

</body>
</html>
