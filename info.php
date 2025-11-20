<?php
/**
 * Basit PHP Test Dosyası
 */

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>PHP Çalışıyor!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f0;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class='box'>
        <p class='success'>✅ PHP ÇALIŞIYOR!</p>
        <p>PHP Version: " . PHP_VERSION . "</p>
        <p>HTTPS: " . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Evet' : 'Hayır') . "</p>
        <hr>
        <a href='/test.php'>Tam Sistem Testi</a> |
        <a href='/install.php'>Kurulum</a> |
        <a href='/'>Ana Sayfa</a>
    </div>
</body>
</html>";
?>
