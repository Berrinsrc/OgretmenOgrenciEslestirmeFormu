<?php
// get_dersler.php
// Veritabanından mevcut dersleri çeker.

require_once 'ayarlar.php'; // Veritabanı bağlantısını içerir

header('Content-Type: application/json'); // Yanıtın JSON formatında olacağını belirt

try {
    // Dersler tablosundan ders_id ve ders_adi sütunlarını seç
    $stmt = $pdo->query("SELECT ders_id, ders_adi FROM dersler ORDER BY ders_adi");
    $dersler = $stmt->fetchAll(PDO::FETCH_ASSOC); // Sonuçları ilişkisel dizi olarak al
    echo json_encode(['success' => true, 'dersler' => $dersler]); // Başarılı yanıtı JSON olarak döndür
} catch (PDOException $e) {
    // Hata durumunda hata mesajını JSON olarak döndür
    echo json_encode(['success' => false, 'message' => 'Dersler çekilirken hata oluştu: ' . $e->getMessage()]);
}
?>