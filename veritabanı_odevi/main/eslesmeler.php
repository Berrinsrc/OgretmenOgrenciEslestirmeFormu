<?php
// eslesmeler.php - Öğretmen Müsaitlik ve Eşleşme İşlemleri
// Bu dosya, öğretmen müsaitlikleri ve öğrenci-öğretmen eşleşmeleriyle ilgili veritabanı fonksiyonlarını içerir.
// Not: Dinamik randevu müsaitliği 'randevular' tablosu üzerinden yönetildiğinden, bu dosyadaki bazı fonksiyonlar
// doğrudan mevcut akışta kullanılmayabilir, ancak veritabanı yapısıyla uyumluluğu sürdürülmüştür.

require_once 'ayarlar.php'; // PDO bağlantısı için ayarlar dosyasını dahil et

/**
 * Öğretmen müsaitliğini günceller.
 * (Bu fonksiyon, doğrudan randevu sistemiyle değil, potansiyel eşleşmelerle ilgili bir 'dolu' bayrağını güncelliyor olabilir.)
 *
 * @param int $ogretmenId Öğretmenin ID'si.
 * @param string $gun Gün (örn: 'Pazartesi').
 * @param string $baslangicSaati Başlangıç saati (örn: '09:00:00').
 * @param string $bitisSaati Bitiş saati (örn: '10:00:00').
 * @return bool Başarılı ise true, değilse false.
 */
function musaitligiGuncelle(int $ogretmenId, string $gun, string $baslangicSaati, string $bitisSaati): bool {
    global $pdo;
    try {
        $guncellemeSorgusu = $pdo->prepare("UPDATE ogretmen_musaitlik SET dolu = 1 WHERE ogretmen_id = :ogretmenId AND gun = :gun AND baslangic_saati = :baslangicSaati AND bitis_saati = :bitisSaati");
        $guncellemeSorgusu->bindParam(':ogretmenId', $ogretmenId, PDO::PARAM_INT);
        $guncellemeSorgusu->bindParam(':gun', $gun, PDO::PARAM_STR);
        $guncellemeSorgusu->bindParam(':baslangicSaati', $baslangicSaati, PDO::PARAM_STR);
        $guncellemeSorgusu->bindParam(':bitisSaati', $bitisSaati, PDO::PARAM_STR);
        return $guncellemeSorgusu->execute();
    } catch (PDOException $e) {
        error_log("Müsaitlik güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Yeni bir öğrenci-öğretmen-ders eşleşmesi oluşturur.
 * (Bu fonksiyon, doğrudan randevu oluşturma akışından farklı bir "eşleşme" kavramını ifade ediyor olabilir.)
 *
 * @param int $ogrenciId Öğrencinin ID'si.
 * @param int $ogretmenId Öğretmenin ID'si.
 * @param int $dersId Dersin ID'si.
 * @param string $tarih Eşleşme tarihi (YYYY-MM-DD).
 * @param string $gun Gün (örn: 'Pazartesi').
 * @param string $baslangicSaati Başlangıç saati.
 * @param string $bitisSaati Bitiş saati.
 * @return bool Başarılı ise true, değilse false.
 */
function eslesmeOlustur(int $ogrenciId, int $ogretmenId, int $dersId, string $tarih, string $gun, string $baslangicSaati, string $bitisSaati): bool {
    global $pdo;
    try {
        $kayitSorgusu = $pdo->prepare("INSERT INTO eslesmeler (ogrenci_id, ogretmen_id, ders_id, tarih, gun, baslangic_saati, bitis_saati) VALUES (:ogrenciId, :ogretmenId, :dersId, :tarih, :gun, :baslangicSaati, :bitisSaati)");
        $kayitSorgusu->bindParam(':ogrenciId', $ogrenciId, PDO::PARAM_INT);
        $kayitSorgusu->bindParam(':ogretmenId', $ogretmenId, PDO::PARAM_INT);
        $kayitSorgusu->bindParam(':dersId', $dersId, PDO::PARAM_INT);
        $kayitSorgusu->bindParam(':tarih', $tarih, PDO::PARAM_STR);
        $kayitSorgusu->bindParam(':gun', $gun, PDO::PARAM_STR);
        $kayitSorgusu->bindParam(':baslangicSaati', $baslangicSaati, PDO::PARAM_STR);
        $kayitSorgusu->bindParam(':bitisSaati', $bitisSaati, PDO::PARAM_STR);
        return $kayitSorgusu->execute();
    } catch (PDOException $e) {
        error_log("Eşleşme oluşturma hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Bir eşleşmeyi siler.
 *
 * @param int $eslesmeId Silinecek eşleşmenin ID'si.
 * @return bool Başarılı ise true, değilse false.
 */
function eslesmeSil(int $eslesmeId): bool {
    global $pdo;
    try {
        $silmeSorgusu = $pdo->prepare("DELETE FROM eslesmeler WHERE eslesme_id = :eslesmeId");
        $silmeSorgusu->bindParam(':eslesmeId', $eslesmeId, PDO::PARAM_INT);
        return $silmeSorgusu->execute();
    } catch (PDOException $e) {
        error_log("Eşleşme silme hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Tüm eşleşmeleri getirir.
 *
 * @return array Tüm eşleşmelerin listesi.
 */
function eslesmeleriGetir(): array {
    global $pdo;
    try {
        $sorgu = $pdo->query("SELECT eslesme_id, ogrenci_id, ogretmen_id, ders_id, tarih, gun, baslangic_saati, bitis_saati FROM eslesmeler", PDO::FETCH_ASSOC);
        return $sorgu->fetchAll();
    } catch (PDOException $e) {
        error_log("Eşleşmeleri getirirken hata oluştu: " . $e->getMessage());
        return [];
    }
}

/**
 * Öğrenciye göre eşleşmeleri getirir.
 *
 * @param int $ogrenciId Öğrencinin ID'si.
 * @return array Öğrencinin eşleşmeleri.
 */
function eslesmeleriOgrenciyeGoreGetir(int $ogrenciId): array {
    global $pdo;
    try {
        $sorgu = $pdo->prepare("SELECT eslesme_id, ogrenci_id, ogretmen_id, ders_id, tarih, gun, baslangic_saati, bitis_saati FROM eslesmeler WHERE ogrenci_id = :ogrenciId");
        $sorgu->bindParam(':ogrenciId', $ogrenciId, PDO::PARAM_INT);
        $sorgu->execute();
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Öğrencinin eşleşmelerini getirirken hata oluştu: " . $e->getMessage());
        return [];
    }
}

/**
 * Öğretmene göre eşleşmeleri getirir.
 *
 * @param int $ogretmenId Öğretmenin ID'si.
 * @return array Öğretmenin eşleşmeleri.
 */
function eslesmeleriOgretmeneGoreGetir(int $ogretmenId): array {
    global $pdo;
    try {
        $sorgu = $pdo->prepare("SELECT eslesme_id, ogrenci_id, ders_id, tarih, gun, baslangic_saati, bitis_saati FROM eslesmeler WHERE ogretmen_id = :ogretmenId");
        $sorgu->bindParam(':ogretmenId', $ogretmenId, PDO::PARAM_INT);
        $sorgu->execute();
        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Öğretmenin eşleşmelerini getirirken hata oluştu: " . $e->getMessage());
        return [];
    }
}

/**
 * Bir eşleşmeyi günceller.
 *
 * @param int $eslesmeId Güncellenecek eşleşmenin ID'si.
 * @param int $ogrenciId Öğrencinin ID'si.
 * @param int $ogretmenId Öğretmenin ID'si.
 * @param int $dersId Dersin ID'si.
 * @param string $tarih Eşleşme tarihi (YYYY-MM-DD).
 * @param string $gun Gün (örn: 'Pazartesi').
 * @param string $baslangicSaati Başlangıç saati.
 * @param string $bitisSaati Bitiş saati.
 * @return bool Başarılı ise true, değilse false.
 */
function eslesmeGuncelle(int $eslesmeId, int $ogrenciId, int $ogretmenId, int $dersId, string $tarih, string $gun, string $baslangicSaati, string $bitisSaati): bool {
    global $pdo;
    try {
        $guncellemeSorgusu = $pdo->prepare("UPDATE eslesmeler SET ogrenci_id = :ogrenciId, ogretmen_id = :ogretmenId, ders_id = :dersId, tarih = :tarih, gun = :gun, baslangic_saati = :baslangicSaati, bitis_saati = :bitisSaati WHERE eslesme_id = :eslesmeId");
        $guncellemeSorgusu->bindParam(':eslesmeId', $eslesmeId, PDO::PARAM_INT);
        $guncellemeSorgusu->bindParam(':ogrenciId', $ogrenciId, PDO::PARAM_INT);
        $guncellemeSorgusu->bindParam(':ogretmenId', $ogretmenId, PDO::PARAM_INT);
        $guncellemeSorgusu->bindParam(':dersId', $dersId, PDO::PARAM_INT);
        $guncellemeSorgusu->bindParam(':tarih', $tarih, PDO::PARAM_STR);
        $guncellemeSorgusu->bindParam(':gun', $gun, PDO::PARAM_STR);
        $guncellemeSorgusu->bindParam(':baslangicSaati', $baslangicSaati, PDO::PARAM_STR);
        $guncellemeSorgusu->bindParam(':bitisSaati', $bitisSaati, PDO::PARAM_STR);
        return $guncellemeSorgusu->execute();
    } catch (PDOException $e) {
        error_log("Eşleşme güncelleme hatası: " . $e->getMessage());
        return false;
    }
}
