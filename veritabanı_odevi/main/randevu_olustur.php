<?php
// randevu_olustur.php - Randevu Oluşturma İşlemi
// Bu dosya, öğrencinin randevu isteğini alır ve veritabanına kaydeder.

require_once 'ayarlar.php'; // Veritabanı bağlantısı için ayarlar dosyasını çağırırız.

header('Content-Type: application/json'); // Sana göndereceğimiz bilginin JSON formatında olduğunu söyleriz.

// POST isteği mi?
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit();
}

// Oturumdan senin öğrenci numaranı alırız.
$ogrenci_id = isset($_SESSION['ogrenci_id']) ? intval($_SESSION['ogrenci_id']) : 0;

// Eğer öğrenci olarak giriş yapmadıysan, hata mesajı göndeririz.
if ($ogrenci_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Oturum sona ermiş veya öğrenci olarak giriş yapmadınız. Lütfen giriş yapın.']);
    exit();
}

// Senin gönderdiğin öğretmen ID'sini, ders ID'sini, tarihi ve saati alırız.
$ogretmen_id = isset($_POST['ogretmen_id']) ? intval($_POST['ogretmen_id']) : 0;
$ders_id = isset($_POST['ders_id']) ? intval($_POST['ders_id']) : 0;
$randevu_tarihi_str = isset($_POST['randevu_tarihi']) ? $_POST['randevu_tarihi'] : '';
$randevu_saati_str = isset($_POST['randevu_saati']) ? $_POST['randevu_saati'] : ''; // Yazım hatası düzeltildi: $randemu_saati_str -> $randevu_saati_str

// Eğer bilgilerden biri eksikse, hata mesajı göndeririz.
if ($ogretmen_id === 0 || $ders_id === 0 || empty($randevu_tarihi_str) || empty($randevu_saati_str)) {
    echo json_encode(['success' => false, 'message' => 'Randevu bilgileri eksik. Lütfen tüm alanları doldurun.']);
    exit();
}

// Tarih ve saat doğrulama
// DateTime nesnelerini string'lerden doğru şekilde oluşturduğumuzdan emin olalım.
try {
    $randevu_tarihi = new DateTime($randevu_tarihi_str);
    $randevu_saati = new DateTime($randevu_saati_str); // Yazım hatası düzeltildi
} catch (Exception $e) {
    error_log("DateTime nesnesi oluşturulurken hata: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Geçersiz tarih veya saat formatı.']);
    exit();
}

$simdi = new DateTime();

// Geçmiş bir tarihe randevu oluşturmayı engelle
if ($randevu_tarihi < $simdi->setTime(0, 0, 0)) { // Sadece tarihi karşılaştır
    echo json_encode(['success' => false, 'message' => 'Geçmiş bir tarihe randevu oluşturamazsınız.']);
    exit();
}

// Eğer tarih bugünse, saati de kontrol et
if ($randevu_tarihi->format('Y-m-d') === $simdi->format('Y-m-d')) {
    // Saatleri HH:MM formatında karşılaştırmak daha güvenli olabilir
    if ($randevu_saati->format('H:i') <= $simdi->format('H:i')) {
        echo json_encode(['success' => false, 'message' => 'Geçmiş bir saate randevu oluşturamazsınız.']);
        exit();
    }
}

try {
    $pdo->beginTransaction();

    // Bu öğrencinin aynı öğretmenle aynı ders için aynı saatte beklemede veya onaylanmış bir randevusu var mı kontrol et
    $stmt_exist = $pdo->prepare("SELECT COUNT(*) FROM randevular 
                                WHERE ogrenci_id = :ogrenci_id 
                                AND ogretmen_id = :ogretmen_id 
                                AND ders_id = :ders_id 
                                AND randevu_tarihi = :randevu_tarihi 
                                AND randevu_baslangic_saati = :randevu_saati 
                                AND randevu_durumu IN ('beklemede', 'onaylandi')");
    $stmt_exist->bindParam(':ogrenci_id', $ogrenci_id, PDO::PARAM_INT);
    $stmt_exist->bindParam(':ogretmen_id', $ogretmen_id, PDO::PARAM_INT);
    $stmt_exist->bindParam(':ders_id', $ders_id, PDO::PARAM_INT);
    $stmt_exist->bindParam(':randevu_tarihi', $randevu_tarihi_str);
    $stmt_exist->bindParam(':randevu_saati', $randevu_saati_str);
    $stmt_exist->execute();
    if ($stmt_exist->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Bu tarihte ve saatte aynı ders için zaten bir randevu talebiniz bulunuyor.']);
        exit();
    }

    // Öğretmenin bu saatte başka bir randevusu olup olmadığını kontrol et (onaylı veya beklemede)
    $stmt_teacher_availability = $pdo->prepare("SELECT COUNT(*) FROM randevular
                                            WHERE ogretmen_id = :ogretmen_id
                                              AND randevu_tarihi = :randevu_tarihi
                                              AND randevu_baslangic_saati = :randevu_saati
                                              AND randevu_durumu IN ('beklemede', 'onaylandi')");
    $stmt_teacher_availability->bindParam(':ogretmen_id', $ogretmen_id, PDO::PARAM_INT);
    $stmt_teacher_availability->bindParam(':randevu_tarihi', $randevu_tarihi_str);
    $stmt_teacher_availability->bindParam(':randevu_saati', $randevu_saati_str);
    $stmt_teacher_availability->execute();
    if ($stmt_teacher_availability->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Seçtiğiniz öğretmen bu saatte başka bir randevuyla meşgul. Lütfen farklı bir saat veya öğretmen seçin.']);
        exit();
    }

    // Randevu bitiş saatini hesapla (basitçe başlangıç + 1 saat)
    // Gerçek bir uygulamada bu, öğretmenlerin tanımlı ders sürelerine veya aralıklara göre daha dinamik olabilir.
    $randevu_bitis_saati_obj = clone $randevu_saati;
    $randevu_bitis_saati_obj->modify('+1 hour');
    $randevu_bitis_saati = $randevu_bitis_saati_obj->format('H:i:s');

    // Yeni randevu isteğini veritabanına kaydederiz. Durumu "beklemede" olarak ayarlarız.
    $stmt = $pdo->prepare("INSERT INTO randevular (ogrenci_id, ogretmen_id, ders_id, randevu_tarihi, randevu_baslangic_saati, randevu_bitis_saati, randevu_durumu)
                           VALUES (:ogrenci_id, :ogretmen_id, :ders_id, :randevu_tarihi, :randevu_baslangic_saati, :randevu_bitis_saati, 'beklemede')");
    $stmt->bindParam(':ogrenci_id', $ogrenci_id, PDO::PARAM_INT);
    $stmt->bindParam(':ogretmen_id', $ogretmen_id, PDO::PARAM_INT);
    $stmt->bindParam(':ders_id', $ders_id, PDO::PARAM_INT);
    $stmt->bindParam(':randevu_tarihi', $randevu_tarihi_str);
    $stmt->bindParam(':randevu_baslangic_saati', $randevu_saati_str);
    $stmt->bindParam(':randevu_bitis_saati', $randevu_bitis_saati);
    $stmt->execute();

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Randevu talebiniz başarıyla oluşturuldu!', 'redirect' => 'ogrenci_ana_sayfa.php?durum=randevu_basarili']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Randevu oluşturma hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Randevu oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.']);
}
?>
