<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$user = getCurrentUser();

// Check if files were uploaded
if (!isset($_FILES['evidence']) || empty($_FILES['evidence']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit();
}

try {
    $uploaded_files = [];
    $files = $_FILES['evidence'];
    
    // Handle multiple files
    if (is_array($files['tmp_name'])) {
        $file_count = count($files['tmp_name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === 0) {
                $file = [
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size' => $files['size'][$i],
                    'error' => $files['error'][$i],
                    'type' => $files['type'][$i]
                ];
                
                $result = processFileUpload($file);
                if ($result['success']) {
                    $uploaded_files[] = $result;
                } else {
                    echo json_encode(['success' => false, 'message' => $result['message']]);
                    exit();
                }
            }
        }
    } else {
        // Single file
        $file = [
            'name' => $files['name'],
            'tmp_name' => $files['tmp_name'],
            'size' => $files['size'],
            'error' => $files['error'],
            'type' => $files['type']
        ];
        
        $result = processFileUpload($file);
        if ($result['success']) {
            $uploaded_files[] = $result;
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            exit();
        }
    }
    
    // Log the upload activity
    logActivity($user['id'], 'evidence_uploaded', 'Uploaded ' . count($uploaded_files) . ' evidence files');
    
    echo json_encode([
        'success' => true,
        'message' => 'Files uploaded successfully',
        'files' => $uploaded_files
    ]);
    
} catch (Exception $e) {
    error_log("Evidence upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while uploading files']);
}

function processFileUpload($file) {
    // Validate file
    if ($file['error'] !== 0) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Check file type
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Generate unique filename
    $new_filename = generateUniqueId('evidence_') . '.' . $file_ext;
    $upload_path = UPLOAD_DIR . $new_filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'original_name' => $file['name'],
            'path' => $upload_path,
            'size' => $file['size'],
            'type' => $file['type']
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}

function validateImageFile($file_path) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file_path);
    finfo_close($file_info);
    
    return in_array($mime_type, $allowed_types);
}

function generateThumbnail($source_path, $thumb_path, $max_width = 200, $max_height = 200) {
    $image_info = getimagesize($source_path);
    if (!$image_info) {
        return false;
    }
    
    $src_width = $image_info[0];
    $src_height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Calculate thumbnail dimensions
    $ratio = min($max_width / $src_width, $max_height / $src_height);
    $thumb_width = intval($src_width * $ratio);
    $thumb_height = intval($src_height * $ratio);
    
    // Create source image
    switch ($mime_type) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }
    
    // Create thumbnail
    $thumbnail = imagecreatetruecolor($thumb_width, $thumb_height);
    
    // Preserve transparency for PNG and GIF
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefill($thumbnail, 0, 0, $transparent);
    }
    
    // Resize
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumb_width, $thumb_height, $src_width, $src_height);
    
    // Save thumbnail
    $result = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $result = imagejpeg($thumbnail, $thumb_path, 85);
            break;
        case 'image/png':
            $result = imagepng($thumbnail, $thumb_path);
            break;
        case 'image/gif':
            $result = imagegif($thumbnail, $thumb_path);
            break;
    }
    
    // Clean up
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $result;
}
?>
