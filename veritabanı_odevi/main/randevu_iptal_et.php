<?php
// randevu_iptal_et.php - Öğrencinin Randevu İptal İşlemi
// Bu dosya, öğrencilerin kendi oluşturdukları randevuları iptal etmelerini sağlar.

require_once 'ayarlar.php'; // Veritabanı bağlantısı ve oturum için

header('Content-Type: application/json'); // JSON yanıtı döndüreceğimizi belirtiriz

// POST isteği mi?
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit();
}

// Oturum kontrolü: Öğrenci giriş yapmış mı?
if (!isset($_SESSION['giris_yapti']) || $_SESSION['rol'] !== 'ogrenci') {
    echo json_encode(['success' => false, 'message' => 'Bu işlemi yapmak için öğrenci olarak giriş yapmalısınız.']);
    exit();
}

$ogrenci_id = $_SESSION['ogrenci_id'] ?? 0;
$randevu_id = isset($_POST['randevu_id']) ? intval($_POST['randevu_id']) : 0;

if ($ogrenci_id === 0 || $randevu_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Eksik randevu veya öğrenci bilgisi.']);
    exit();
}

try {
    // Randevunun gerçekten bu öğrenciye ait olup olmadığını kontrol et
    // ve mevcut durumunu al
    $stmt_check = $pdo->prepare("SELECT randevu_durumu FROM randevular WHERE randevu_id = :randevu_id AND ogrenci_id = :ogrenci_id");
    $stmt_check->bindParam(':randevu_id', $randevu_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':ogrenci_id', $ogrenci_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $randevu_info = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$randevu_info) {
        echo json_encode(['success' => false, 'message' => 'Randevu bulunamadı veya bu randevuyu iptal etme yetkiniz yok.']);
        exit();
    }

    // Yalnızca 'beklemede' veya 'onaylandi' durumundaki randevuları iptal et
    if ($randevu_info['randevu_durumu'] === 'reddedildi' || $randevu_info['randevu_durumu'] === 'iptal_edildi') {
        echo json_encode(['success' => false, 'message' => 'Bu randevu zaten reddedilmiş veya iptal edilmiş.']);
        exit();
    }

    // Randevuyu iptal et (durumu 'iptal_edildi' olarak güncelle)
    $stmt_update = $pdo->prepare("UPDATE randevular SET randevu_durumu = 'iptal_edildi', ogretmen_aciklama = CONCAT(ogretmen_aciklama, '\n(Öğrenci tarafından iptal edildi - ', NOW(), ')') WHERE randevu_id = :randevu_id AND ogrenci_id = :ogrenci_id");
    $stmt_update->bindParam(':randevu_id', $randevu_id, PDO::PARAM_INT);
    $stmt_update->bindParam(':ogrenci_id', $ogrenci_id, PDO::PARAM_INT);
    $stmt_update->execute();

    if ($stmt_update->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Randevunuz başarıyla iptal edildi.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Randevu iptal edilirken bir hata oluştu.']);
    }

} catch (PDOException $e) {
    error_log("Randevu iptal hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Sunucu hatası: Randevu iptal edilemedi.']);
}
?>
