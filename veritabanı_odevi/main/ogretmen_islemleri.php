<?php
// ogretmen_islemleri.php - Öğretmene Özel İşlemler
// Bu dosya, öğretmenlerin listelenmesi ve detay bilgilerinin çekilmesi gibi rollere özel işlevleri içerir.
// Kullanıcı silme ve güncelleme işlemleri artık kullanici_islemleri.php üzerinden yapılmaktadır.

require_once 'ayarlar.php'; // PDO bağlantısı için ayarlar dosyasını dahil et

/**
 * Tüm öğretmenleri getirir.
 * Öğretmen ve kullanıcı bilgilerini birleştirir.
 *
 * @return array Tüm öğretmenlerin listesi.
 */
function ogretmenleriGetir(): array {
    global $pdo;
    try {
        // Öğretmen adını, soyadını ogretmenler tablosundan, e-postayı kullanicilar tablosundan ve uzmanlık alanlarını ogretmenler tablosundan alıyoruz
        $stmt = $pdo->query("SELECT o.ogretmen_id, k.e_posta, o.adi, o.soyadi, o.uzmanlik_alanlari FROM ogretmenler o JOIN kullanicilar k ON o.kullanici_id = k.id ORDER BY o.adi, o.soyadi");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Öğretmen listesi çekilirken hata oluştu: " . $e->getMessage());
        return [];
    }
}

/**
 * Belirli bir öğretmenin detaylarını getirir.
 * Öğretmen ve kullanıcı bilgilerini birleştirir.
 *
 * @param int $ogretmenId Öğretmenin ID'si.
 * @return array|null Öğretmen detayları veya null.
 */
function ogretmenDetayGetir(int $ogretmenId): ?array {
    global $pdo;
    try {
        // Öğretmen ID'si ile hem ogretmenler hem de kullanicilar tablolarından gerekli bilgileri çekiyoruz.
        // E-posta kullanicilar tablosundan, ad, soyad ve uzmanlik_alanlari ogretmenler tablosundan.
        $stmt = $pdo->prepare("SELECT o.ogretmen_id, k.e_posta, o.adi, o.soyadi, o.uzmanlik_alanlari, k.id AS kullanici_id FROM ogretmenler o JOIN kullanicilar k ON o.kullanici_id = k.id WHERE o.ogretmen_id = :ogretmenId");
        $stmt->bindParam(':ogretmenId', $ogretmenId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Öğretmen detayları çekilirken hata oluştu: " . $e->getMessage());
        return null;
    }
}

// Öğretmenin verdiği dersleri getirir.
function ogretmenDersleriniGetir(int $ogretmenId): array {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT d.ders_id, d.ders_adi FROM ogretmen_dersleri od JOIN dersler d ON od.ders_id = d.ders_id WHERE od.ogretmen_id = :ogretmenId");
        $stmt->bindParam(':ogretmenId', $ogretmenId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Öğretmen dersleri çekilirken hata oluştu: " . $e->getMessage());
        return [];
    }
}

// Diğer özel öğretmen işlemleri buraya eklenebilir.
// Örneğin: Öğretmenin müsaitliklerini güncelleme, ders atama vs.
