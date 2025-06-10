<?php
// musait_ogretmenleri_getir.php - Müsait Öğretmenleri Getir
// Bu dosya, seçilen ders, tarih ve saate göre hangi öğretmenlerin boş olduğunu bulur.
// Öğretmen müsaitliği, o saatte "onaylandi" veya "beklemede" randevusu olmayan öğretmenler anlamına gelir.

require_once 'ayarlar.php'; // Veritabanı bağlantısı için ayarlar dosyasını çağırırız.

header('Content-Type: application/json'); // Sana göndereceğimiz bilginin JSON formatında olduğunu söyleriz.

// Senin gönderdiğin ders ID'sini, tarihi ve saati alırız.
$ders_id = isset($_GET['ders_id']) ? intval($_GET['ders_id']) : 0;
$randevu_tarihi = isset($_GET['randevu_tarihi']) ? $_GET['randevu_tarihi'] : '';
$randevu_saati = isset($_GET['randevu_saati']) ? $_GET['randevu_saati'] : '';

// Eğer bu bilgilerden biri eksikse, hata mesajı göndeririz.
if ($ders_id === 0 || empty($randevu_tarihi) || empty($randevu_saati)) {
    echo json_encode(['success' => false, 'message' => 'Eksik bilgi var. Ders, tarih ve saat belirtmelisiniz.']);
    exit();
}

try {
    $available_teachers = [];

    // 1. Önce, seçtiğin dersi veren tüm öğretmenleri ve onların ad/soyad bilgilerini ogretmenler tablosundan al.
    $stmt_ogretmenler = $pdo->prepare("SELECT o.ogretmen_id, o.adi, o.soyadi
                                       FROM ogretmen_dersleri od
                                       JOIN ogretmenler o ON od.ogretmen_id = o.ogretmen_id
                                       WHERE od.ders_id = :ders_id
                                       ORDER BY o.adi, o.soyadi");
    $stmt_ogretmenler->bindParam(':ders_id', $ders_id, PDO::PARAM_INT);
    $stmt_ogretmenler->execute();
    $dersi_veren_ogretmenler = $stmt_ogretmenler->fetchAll(PDO::FETCH_ASSOC);

    // Her öğretmenin belirtilen tarihte ve saatte müsait olup olmadığını kontrol et
    foreach ($dersi_veren_ogretmenler as $ogretmen) {
        // Öğretmenin belirtilen tarih ve saatte 'beklemede' veya 'onaylandi' durumunda bir randevusu var mı kontrol et.
        // Bu, öğretmenin o anda başka bir öğrenciyle meşgul olup olmadığını gösterir.
        $stmt_randevu = $pdo->prepare("SELECT COUNT(*)
                                           FROM randevular
                                           WHERE ogretmen_id = :ogretmen_id
                                             AND randevu_tarihi = :randevu_tarihi
                                             AND randevu_baslangic_saati = :randevu_saati
                                             AND randevu_durumu IN ('beklemede', 'onaylandi')");
        $stmt_randevu->bindParam(':ogretmen_id', $ogretmen['ogretmen_id'], PDO::PARAM_INT);
        $stmt_randevu->bindParam(':randevu_tarihi', $randevu_tarihi);
        $stmt_randevu->bindParam(':randevu_saati', $randevu_saati);
        $stmt_randevu->execute();
        $has_appointment = $stmt_randevu->fetchColumn();

        if (!$has_appointment) {
            // Eğer o saatte başka randevusu yoksa, bu öğretmeni listeye ekleriz.
            $available_teachers[] = $ogretmen;
        }
    }

    echo json_encode(['success' => true, 'ogretmenler' => $available_teachers]); // Müsait öğretmenleri sana göndeririz.

} catch (PDOException $e) {
    // Bir hata olursa, hata mesajını sana göndeririz.
    error_log("Öğretmen müsaitlik çekilirken hata oluştu: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Müsait öğretmenler çekilirken bir hata oluştu.']);
}
