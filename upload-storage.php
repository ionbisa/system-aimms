<?php
// Simple bulk file uploader for storage/app/public/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    $uploadDir = __DIR__ . '/storage/app/public/';
    
    // Create subdirectories if needed
    $subdirs = ['stocks', 'assets', 'employee-boots', 'employee-uniforms', 'purchase-orders', 'users'];
    foreach ($subdirs as $dir) {
        @mkdir($uploadDir . $dir, 0755, true);
    }
    
    $uploaded = 0;
    $errors = [];
    
    foreach ($_FILES['files']['tmp_name'] as $key => $tmp) {
        $name = $_FILES['files']['name'][$key];
        $dest = $uploadDir . $name;
        
        if (move_uploaded_file($tmp, $dest)) {
            $uploaded++;
            chmod($dest, 0644);
        } else {
            $errors[] = "Failed: $name";
        }
    }
    
    echo "<pre>Uploaded: $uploaded files\n";
    if ($errors) echo implode("\n", $errors);
    echo "\n\nRefresh hosting halaman sekarang.\n</pre>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Storage Files</title>
    <style>
        body { font-family: Arial; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 600px; }
        input[type="file"] { display: block; margin: 20px 0; padding: 10px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Storage Files</h2>
        <form method="POST" enctype="multipart/form-data">
            <p>Select multiple image files to upload to storage/app/public/</p>
            <input type="file" name="files[]" multiple accept="image/*" required>
            <button type="submit">Upload Files</button>
        </form>
    </div>
</body>
</html>
