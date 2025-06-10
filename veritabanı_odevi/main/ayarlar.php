<?php
// ayarlar.php
// Bu dosya, veritabanı bağlantısını ve oturum yönetimini sağlar.
// Tüm projedeki veritabanı bağlantısı ve oturum başlatma işlemleri buradan yönetilir.

session_start(); // Oturumu başlat

// Hata raporlamayı etkinleştir (Geliştirme için açık tutun, canlıya alırken kapatın!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Veritabanı bağlantı bilgileri
define('DB_SERVER', 'localhost'); // Veritabanı sunucusu adresi
define('DB_USERNAME', 'root'); // Veritabanı kullanıcı adınız
define('DB_PASSWORD', '');     // Veritabanı şifreniz
define('DB_NAME', 'veritabanı_odevi'); // Kullanacağınız veritabanı adı

// PDO ile veritabanı bağlantısı oluştur
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    // Hata modunu istisna olarak ayarla, böylece PDO hataları istisna olarak fırlatır
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Varsayılan fetch modunu ayarla: İlişkisel dizi (kolon adları ile erişim)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Karakter setini UTF-8 olarak ayarla (DSN'de belirtildi ama ek olarak da ayarlanabilir)
    // $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    // Veritabanı bağlantısı başarısız olursa hata mesajını göster ve çık
    error_log("Veritabanı bağlantı hatası: " . $e->getMessage()); // Hata loglama
    die("Sisteme bağlanılamıyor. Lütfen daha sonra tekrar deneyin. (DB Bağlantı Hatası)");
}

// Güvenlik: CSRF Token Oluşturma ve Doğrulama
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Güvenlik: XSS Koruma için sanitize fonksiyonu (output için)
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Güvenlik: POST verilerini otomatik sanitize et (input için)
// Bu sadece basit bir korumadır, her zaman specific validasyon ve temizlik yapılmalıdır.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (is_string($value)) {
            $_POST[$key] = trim($value); // Baştaki ve sondaki boşlukları kaldır
        }
    }
}
