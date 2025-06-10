<?php
// cikis.php
require_once 'ayarlar.php'; // Sadece session_start() için

// Tüm session değişkenlerini temizle
$_SESSION = array();

// Session cookie'sini sil (tarayıcı kapandığında session'ın bitmesi için)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı yok et
session_destroy();

// Giriş sayfasına yönlendir
header("Location: giris.php?cikis=basarili");
exit();
?>
