<?php

require_once 'ayarlar.php'; // Artık veritabanı bağlantısı için $pdo değişkenini sağlar

function ogrenciKaydet(string $eposta, string $sifreHash, string $ad, string $soyad): bool {
    global $pdo; // Bağlantı değişkeni $conn yerine $pdo olarak güncellendi

    $kayitSorgusu = $pdo->prepare("INSERT INTO ogrenciler (e_posta, sifre_hash, ad, soyad) VALUES (:eposta, :sifreHash, :ad, :soyad)");
    $kayitSorgusu->bindParam(':eposta', $eposta, PDO::PARAM_STR);
    $kayitSorgusu->bindParam(':sifreHash', $sifreHash, PDO::PARAM_STR);
    $kayitSorgusu->bindParam(':ad', $ad, PDO::PARAM_STR);
    $kayitSorgusu->bindParam(':soyad', $soyad, PDO::PARAM_STR);

    return $kayitSorgusu->execute();
}

function ogrenciSil(int $ogrenciId): bool {
    global $pdo; // Bağlantı değişkeni $conn yerine $pdo olarak güncellendi

    $silmeSorgusu = $pdo->prepare("DELETE FROM ogrenciler WHERE ogrenci_id = :ogrenciId");
    $silmeSorgusu->bindParam(':ogrenciId', $ogrenciId, PDO::PARAM_INT);

    return $silmeSorgusu->execute();
}

function ogrencileriGetir(): array {
    global $pdo; // Bağlantı değişkeni $conn yerine $pdo olarak güncellendi

    // Ad ve soyad artık ogrenciler tablosunda olduğu için doğrudan çekilir
    $sorgu = $pdo->query("SELECT ogrenci_id, e_posta, adi, soyadi FROM ogrenciler", PDO::FETCH_ASSOC);
    return $sorgu->fetchAll();
}

/**
 * Belirli bir öğrencinin detaylarını getirir.
 *
 * @param int $ogrenciId Öğrencinin ID'si.
 * @return array|null Öğrenci detayları veya null.
 */
function ogrenciDetayGetir(int $ogrenciId): ?array {
    global $pdo; // Bağlantı değişkeni $conn yerine $pdo olarak güncellendi

    // Öğrenci ID'si ile ogrenciler ve kullanicilar tablolarından gerekli bilgileri çekiyoruz.
    // E-posta kullanicilar tablosundan, ad, soyad ve ilgi_alanlari ogrenciler tablosundan.
    try {
        $stmt = $pdo->prepare("SELECT o.ogrenci_id, k.e_posta, o.adi, o.soyadi, o.ilgi_alanlari, k.id AS kullanici_id FROM ogrenciler o JOIN kullanicilar k ON o.kullanici_id = k.id WHERE o.ogrenci_id = :ogrenciId");
        $stmt->bindParam(':ogrenciId', $ogrenciId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Öğrenci detayları çekilirken hata oluştu: " . $e->getMessage());
        return null;
    }
}

/**
 * Öğrenci bilgilerini günceller (ad, soyad, e-posta, ilgi alanları).
 *
 * @param int $ogrenciId Öğrencinin ID'si.
 * @param string $ad Yeni adı.
 * @param string $soyad Yeni soyadı.
 * @param string $eposta Yeni e-posta adresi.
 * @param string|null $yeniSifre Yeni şifre (isteğe bağlı).
 * @param string|null $ilgiAlanlari Yeni ilgi alanları (isteğe bağlı).
 * @return bool Başarılı ise true, değilse false.
 */
function ogrenciGuncelle(int $ogrenciId, string $ad, string $soyad, string $eposta, ?string $yeniSifre = null, ?string $ilgiAlanlari = null): bool {
    global $pdo; // Bağlantı değişkeni $conn yerine $pdo olarak güncellendi

    try {
        $pdo->beginTransaction();

        // 1. Kullanıcılar tablosunu güncelle (e-posta ve şifre)
        $sorgu_kullanici = "UPDATE kullanicilar SET e_posta = :eposta";
        if ($yeniSifre !== null && !empty($yeniSifre)) {
            $sorgu_kullanici .= ", sifre_hash = :sifre_hash";
        }
        $sorgu_kullanici .= " WHERE id = (SELECT kullanici_id FROM ogrenciler WHERE ogrenci_id = :ogrenciId)";

        $stmt_kullanici = $pdo->prepare($sorgu_kullanici);
        $stmt_kullanici->bindParam(':eposta', $eposta, PDO::PARAM_STR);
        if ($yeniSifre !== null && !empty($yeniSifre)) {
            $sifreHash = password_hash($yeniSifre, PASSWORD_BCRYPT);
            $stmt_kullanici->bindParam(':sifre_hash', $sifreHash, PDO::PARAM_STR);
        }
        $stmt_kullanici->bindParam(':ogrenciId', $ogrenciId, PDO::PARAM_INT); // ogrenciId'yi kullanıyoruz
        $stmt_kullanici->execute();

        // 2. Ogrenciler tablosunu güncelle (ad, soyad, ilgi alanları)
        $sorgu_ogrenci = "UPDATE ogrenciler SET adi = :adi, soyadi = :soyadi";
        if ($ilgiAlanlari !== null) {
            $sorgu_ogrenci .= ", ilgi_alanlari = :ilgi_alanlari";
        }
        $sorgu_ogrenci .= " WHERE ogrenci_id = :ogrenciId";

        $stmt_ogrenci = $pdo->prepare($sorgu_ogrenci);
        $stmt_ogrenci->bindParam(':adi', $ad, PDO::PARAM_STR);
        $stmt_ogrenci->bindParam(':soyadi', $soyad, PDO::PARAM_STR);
        if ($ilgiAlanlari !== null) {
            $stmt_ogrenci->bindParam(':ilgi_alanlari', $ilgiAlanlari, PDO::PARAM_STR);
        }
        $stmt_ogrenci->bindParam(':ogrenciId', $ogrenciId, PDO::PARAM_INT);
        $stmt_ogrenci->execute();

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Öğrenci bilgisi güncelleme hatası: " . $e->getMessage());
        return false;
    }
}
