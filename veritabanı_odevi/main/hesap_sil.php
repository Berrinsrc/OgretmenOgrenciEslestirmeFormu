<?php
// hesap_sil.php - Kullanıcı Hesap Silme İşlemi
// Bu dosya, oturum açmış kullanıcının hesabını ve ilişkili tüm verilerini siler.

require_once 'ayarlar.php'; // Oturum ve veritabanı bağlantısı için
require_once 'kullanici_islemleri.php'; // kullaniciSil fonksiyonu için

header('Content-Type: application/json'); // JSON yanıtı döndüreceğimizi belirtiriz

// Kullanıcının giriş yapıp yapmadığını kontrol et
if (!isset($_SESSION['giris_yapti']) || $_SESSION['giris_yapti'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Bu işlemi yapmak için giriş yapmalısınız.']);
    exit();
}

// Oturumdan kullanici ID'sini al
$kullanici_id = isset($_SESSION['kullanici_id']) ? intval($_SESSION['kullanici_id']) : 0;

if ($kullanici_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı ID\'si bulunamadı.']);
    exit();
}

// Güvenlik: CSRF kontrolü (basit bir örnek, daha gelişmiş bir yapı kurulabilir)
// Genellikle GET isteklerinde hassas işlemler yapılmaz, ancak burada silme işlemi GET ile tetikleniyor.
// Eğer POST ile silme işlemi yapılacaksa bir CSRF token kontrolü eklenmelidir.
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz CSRF token.']);
        exit();
    }
}
*/

// Kullanıcı silme işlemini gerçekleştir
if (kullaniciSil($kullanici_id)) {
    // Hesap başarıyla silindiyse oturumu sonlandır
    session_unset();
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Hesabınız ve tüm ilişkili verileriniz başarıyla silindi.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Hesabınız silinirken bir hata oluştu.']);
}
?>
