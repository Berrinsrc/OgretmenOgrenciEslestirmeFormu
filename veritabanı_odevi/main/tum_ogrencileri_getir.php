<?php
// tum_ogrencileri_getir.php
// Bu dosya, sistemdeki tüm öğrencilerin listesini öğretmenlere gönderir.

require_once 'ayarlar.php'; // Veritabanı bağlantısı için ayarlar dosyasını çağırırız.

header('Content-Type: application/json'); // Sana göndereceğimiz bilginin JSON formatında olduğunu söyleriz.

try {
    // Öğrenciler tablosundan öğrenci ID'lerini, adlarını, soyadlarını ve e-postalarını alırız.
    $stmt = $pdo->query("SELECT ogrenci_id, adi, soyadi, e_posta FROM ogrenciler ORDER BY adi, soyadi");
    $ogrenciler = $stmt->fetchAll(PDO::FETCH_ASSOC); // Aldığımız bilgileri düzenli bir listeye dönüştürürüz.
    echo json_encode(['success' => true, 'ogrenciler' => $ogrenciler]); // Başarılı olduysak öğrencileri sana göndeririz.
} catch (PDOException $e) {
    // Eğer bir hata olursa, hata mesajını sana göndeririz.
    echo json_encode(['success' => false, 'message' => 'Öğrenciler çekilirken hata oluştu: ' . $e->getMessage()]);
}
?>
