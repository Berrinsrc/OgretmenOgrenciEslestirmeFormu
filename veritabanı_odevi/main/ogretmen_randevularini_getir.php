<?php
// ogretmen_randevularini_getir.php
// Bu dosya, öğretmenlere gelen randevu taleplerini gösterir.

require_once 'ayarlar.php'; // Veritabanı bağlantısı için ayarlar dosyasını çağırırız.

header('Content-Type: application/json'); // Sana göndereceğimiz bilginin JSON formatında olduğunu söyleriz.

// Oturumdan öğretmenin ID'sini alırız.
$ogretmen_id = isset($_SESSION['ogretmen_id']) ? intval($_SESSION['ogretmen_id']) : 0;

// Eğer öğretmen olarak giriş yapmadıysan, hata mesajı göndeririz.
if ($ogretmen_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Öğretmen olarak giriş yapmadın. Lütfen giriş yap.']);
    exit();
}

try {
    // Randevuları, hangi öğrenciden geldiğini ve hangi ders için olduğunu birleştirerek çekeriz.
    // Öğretmenin yazdığı açıklamayı da alırız.
    $stmt = $pdo->prepare("SELECT r.randevu_id, r.randevu_tarihi, r.randevu_baslangic_saati, r.randevu_durumu, r.ogretmen_aciklama,
                                  o.adi AS ogrenci_adi, o.soyadi AS ogrenci_soyadi,
                                  d.ders_adi
                           FROM randevular r
                           JOIN ogrenciler o ON r.ogrenci_id = o.ogrenci_id
                           JOIN dersler d ON r.ders_id = d.ders_id
                           WHERE r.ogretmen_id = :ogretmen_id
                           ORDER BY r.randevu_tarihi ASC, r.randevu_baslangic_saati ASC"); // En eski randevuları en üstte gösteririz.
    $stmt->bindParam(':ogretmen_id', $ogretmen_id, PDO::PARAM_INT);
    $stmt->execute();
    $randevular = $stmt->fetchAll(PDO::FETCH_ASSOC); // Aldığımız bilgileri düzenli bir listeye dönüştürürüz.

    echo json_encode(['success' => true, 'randevular' => $randevular]); // Randevu taleplerini sana göndeririz.

} catch (PDOException $e) {
    // Bir hata olursa, hata mesajını sana göndeririz.
    echo json_encode(['success' => false, 'message' => 'Randevu talepleri çekilirken hata oluştu: ' . $e->getMessage()]);
}
?>
