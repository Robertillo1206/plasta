<?php
header('Content-Type: application/json');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$dataFile = 'data.json';
$uploadDir = 'uploads/';

// Intentar crear la carpeta si no existe
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}
// Crear el archivo de datos si no existe
if (!file_exists($dataFile)) {
    @file_put_contents($dataFile, json_encode([]));
}

$photos = json_decode(@file_get_contents($dataFile), true);
if (!is_array($photos)) $photos = [];

// 1. Obtener las fotos
if ($action === 'get') {
    usort($photos, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    echo json_encode($photos);
    exit;
}

// 2. Subir fotos
if ($action === 'upload') {
    // Si el servidor corta la subida por peso excesivo, $_FILES llega vacío
    if (empty($_FILES) && empty($_POST)) {
        echo json_encode(['error' => 'La fotografía pesa demasiado. El servidor la ha bloqueado por su límite de tamaño.']);
        exit;
    }

    if (!empty($_FILES['photos'])) {
        $uploaded_any = false;
        $errores = [];

        foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
            // Verificar si hubo error al subir (ej. 1 = UPLOAD_ERR_INI_SIZE)
            if ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_OK) {
                $errores[] = "Error de servidor código: " . $_FILES['photos']['error'][$key];
                continue;
            }

            $ext = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            
            // Intentar guardar el archivo físicamente
            if (move_uploaded_file($tmpName, $targetPath)) {
                $photos[] = [
                    'id' => uniqid(),
                    'filename' => $fileName,
                    'timestamp' => time()
                ];
                $uploaded_any = true;
            } else {
                $errores[] = "El servidor no tiene permisos para guardar en la carpeta /uploads.";
            }
        }

        if ($uploaded_any) {
            file_put_contents($dataFile, json_encode($photos, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => implode(" | ", $errores)]);
        }
    } else {
        echo json_encode(['error' => 'No se ha recibido ningún archivo.']);
    }
    exit;
}

// 3. Eliminar fotos
if ($action === 'delete') {
    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
    foreach ($photos as $key => $photo) {
        if ($photo['id'] === $id) {
            $path = $uploadDir . $photo['filename'];
            if (file_exists($path)) unlink($path);
            
            array_splice($photos, $key, 1);
            file_put_contents($dataFile, json_encode($photos, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
            exit;
        }
    }
    echo json_encode(['error' => 'Foto no encontrada']);
    exit;
}
?>