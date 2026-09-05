<?php
/**
 * File Upload Handler
 */
class Uploader {
    
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;
    private $errors = [];
    
    public function __construct($uploadDir = null) {
        $this->uploadDir = $uploadDir ?? Config::get('upload_path') . 'temp/';
        $this->allowedTypes = Config::get('allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
        $this->maxSize = Config::get('max_upload_size', 10485760);
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function upload($file, $subDir = '') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadError($file['error']);
            return false;
        }
        
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $this->allowedTypes)) {
            $this->errors[] = "File type not allowed: $fileName";
            return false;
        }
        
        if ($fileSize > $this->maxSize) {
            $this->errors[] = "File too large: $fileName (Max: " . ($this->maxSize / 1048576) . "MB)";
            return false;
        }
        
        $targetDir = $this->uploadDir;
        if ($subDir) {
            $targetDir .= rtrim($subDir, '/') . '/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
        }
        
        $newFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $fileName);
        $destination = $targetDir . $newFileName;
        
        if (move_uploaded_file($fileTmp, $destination)) {
            return [
                'original_name' => $fileName,
                'file_name' => $newFileName,
                'file_path' => $destination,
                'file_size' => $fileSize,
                'file_ext' => $fileExt,
                'mime_type' => $file['type']
            ];
        }
        
        $this->errors[] = "Failed to move uploaded file: $fileName";
        return false;
    }
    
    public function uploadMultiple($files, $subDir = '') {
        $results = [];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = $this->upload($file, $subDir);
            if ($result) {
                $results[] = $result;
            }
        }
        
        return $results;
    }
    
    public function delete($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    private function getUploadError($error) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return "File exceeds upload_max_filesize directive in php.ini";
            case UPLOAD_ERR_FORM_SIZE:
                return "File exceeds MAX_FILE_SIZE directive in HTML form";
            case UPLOAD_ERR_PARTIAL:
                return "File was only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "File upload stopped by extension";
            default:
                return "Unknown upload error";
        }
    }
}