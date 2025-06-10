<?php
// ders_getir.php
// Bu dosya, veritabanından tüm dersleri çeker ve JSON formatında geri gönderir.

require_once 'ayarlar.php'; // Veritabanı bağlantısı için ayarlar dosyasını dahil ediyoruz.

header('Content-Type: application/json'); // Yanıtın JSON formatında olacağını belirtiyoruz.

try {
    // Dersler tablosundan ders_id ve ders_adi sütunlarını seçiyoruz.
    // Dersleri alfabetik sıraya göre sıralıyoruz.
    $stmt = $pdo->query("SELECT ders_id, ders_adi FROM dersler ORDER BY ders_adi ASC");
    $dersler = $stmt->fetchAll(PDO::FETCH_ASSOC); // Sonuçları ilişkisel dizi olarak alıyoruz.

    // Başarılı yanıtı ve ders verilerini JSON olarak gönderiyoruz.
    echo json_encode(['success' => true, 'dersler' => $dersler]);

} catch (PDOException $e) {
    // Veritabanı hatası durumunda, hata mesajını JSON olarak gönderiyoruz.
    error_log("Dersler çekilirken hata oluştu: " . $e->getMessage()); // Hata loglama
    echo json_encode(['success' => false, 'message' => 'Dersler alınırken hata oluştu.']);
}
?>
