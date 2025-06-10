<?php
// ogrenci_randevularini_getir.php
// Bu dosya, senin daha önce aldığın veya istediğin tüm randevuları sana gösterir.

require_once 'ayarlar.php'; // Veritabanı bağlantısı için ayarlar dosyasını çağırırız.

header('Content-Type: application/json'); // Sana göndereceğimiz bilginin JSON formatında olduğunu söyleriz.

// Oturumdan senin öğrenci numaranı alırız.
$ogrenci_id = isset($_SESSION['ogrenci_id']) ? intval($_SESSION['ogrenci_id']) : 0;

// Eğer öğrenci olarak giriş yapmadıysan, hata mesajı göndeririz.
if ($ogrenci_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Öğrenci olarak giriş yapmadın. Lütfen giriş yap.']);
    exit();
}

try {
    // Randevularını, hangi öğretmenle olduğunu ve hangi ders için olduğunu birleştirerek çekeriz.
    // Öğretmenin yazdığı açıklamayı da alırız.
    $stmt = $pdo->prepare("SELECT r.randevu_id, r.randevu_tarihi, r.randevu_baslangic_saati, r.randevu_durumu, r.ogretmen_aciklama,
                                  o.adi AS ogretmen_adi, o.soyadi AS ogretmen_soyadi,
                                  d.ders_adi
                           FROM randevular r
                           JOIN ogretmenler o ON r.ogretmen_id = o.ogretmen_id
                           JOIN dersler d ON r.ders_id = d.ders_id
                           WHERE r.ogrenci_id = :ogrenci_id
                           ORDER BY r.randevu_tarihi DESC, r.randevu_baslangic_saati DESC"); // En yeni randevuları en üstte gösteririz.
    $stmt->bindParam(':ogrenci_id', $ogrenci_id, PDO::PARAM_INT);
    $stmt->execute();
    $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC); // Aldığımız bilgileri düzenli bir listeye dönüştürürüz.

    echo json_encode(['success' => true, 'randevular' => $randevular]); // Randevularını sana göndeririz.

} catch (PDOException $e) {
    // Bir hata olursa, hata mesajını sana göndeririz.
    echo json_encode(['success' => false, 'message' => 'Randevular çekilirken hata oluştu: ' . $e->getMessage()]);
}
?>
