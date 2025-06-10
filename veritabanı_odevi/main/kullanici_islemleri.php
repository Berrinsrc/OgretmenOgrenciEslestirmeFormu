<?php
// kullanici_islemleri.php - Kullanıcı Kayıt, Giriş ve Genel İşlemleri
// Bu dosya, kullanıcıların genel kayıt, giriş, şifre değiştirme gibi işlemlerini yönetir.

require_once 'ayarlar.php'; // PDO bağlantısı ve oturum için ayarlar dosyasını dahil et

/**
 * Kullanıcı girişi yapar.
 *
 * @param string $eposta Kullanıcının e-posta adresi.
 * @param string $sifre Kullanıcının düz metin şifresi.
 * @return array|false Başarılı ise kullanıcı bilgileri (id, rol, ad, soyad, [ogrenci_id/ogretmen_id], [brans]) veya false.
 */
function girisYap(string $eposta, string $sifre) {
    global $pdo; // PDO bağlantısını kullan

    try {
        // Kullanıcıyı e-posta ile ara (kullanicilar tablosunda)
        $stmt = $pdo->prepare("SELECT id, sifre_hash, rol FROM kullanicilar WHERE e_posta = :eposta");
        $stmt->bindParam(':eposta', $eposta, PDO::PARAM_STR);
        $stmt->execute();
        $kullanici = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($kullanici && password_verify($sifre, $kullanici['sifre_hash'])) {
            // Şifre doğru, kullanıcı rolüne göre detayları çek
            if ($kullanici['rol'] === 'ogrenci') {
                $stmt_detay = $pdo->prepare("SELECT ogrenci_id, adi, soyadi FROM ogrenciler WHERE kullanici_id = :kullanici_id");
                $stmt_detay->bindParam(':kullanici_id', $kullanici['id'], PDO::PARAM_INT);
                $stmt_detay->execute();
                $detay = $stmt_detay->fetch(PDO::FETCH_ASSOC);
                if ($detay) {
                    return [
                        'id' => $kullanici['id'], // kullanicilar.id
                        'rol' => 'ogrenci',
                        'ogrenci_id' => $detay['ogrenci_id'], // ogrenciler.ogrenci_id
                        'ad' => $detay['adi'],
                        'soyad' => $detay['soyadi']
                    ];
                }
            } elseif ($kullanici['rol'] === 'ogretmen') {
                $stmt_detay = $pdo->prepare("SELECT ogretmen_id, adi, soyadi, uzmanlik_alanlari FROM ogretmenler WHERE kullanici_id = :kullanici_id");
                $stmt_detay->bindParam(':kullanici_id', $kullanici['id'], PDO::PARAM_INT);
                $stmt_detay->execute();
                $detay = $stmt_detay->fetch(PDO::FETCH_ASSOC);
                if ($detay) {
                    return [
                        'id' => $kullanici['id'], // kullanicilar.id
                        'rol' => 'ogretmen',
                        'ogretmen_id' => $detay['ogretmen_id'], // ogretmenler.ogretmen_id
                        'ad' => $detay['adi'],
                        'soyad' => $detay['soyadi'],
                        'brans' => $detay['uzmanlik_alanlari'] // uzmanlik_alanlari olarak geri dön
                    ];
                }
            }
        }
        return false; // Kullanıcı bulunamadı, şifre yanlış veya detay bilgileri eksik
    } catch (PDOException $e) {
        error_log("Giriş yapma hatası (DB): " . $e->getMessage()); // Detaylı hata loglama
        return false;
    }
}

/**
 * Yeni bir kullanıcı kaydeder.
 *
 * @param string $ad Kullanıcının adı.
 * @param string $soyad Kullanıcının soyadı.
 * @param string $eposta Kullanıcının e-posta adresi.
 * @param string $sifre Kullanıcının düz metin şifresi.
 * @param string $rol Kullanıcının rolü ('ogrenci' veya 'ogretmen').
 * @param array $secilenDersler Öğretmen için seçilen derslerin ID'leri (öğrenciler için boş dizi).
 * @param string|null $uzmanlikAlani Öğretmen için uzmanlık alanı (isteğe bağlı).
 * @return string Başarılı ise 'basarili', zaten kayıtlı ise 'kayit_var', hata oluşursa 'hata'.
 */
function kayitOl(string $ad, string $soyad, string $eposta, string $sifre, string $rol, array $secilenDersler = [], ?string $uzmanlikAlani = null): string {
    global $pdo;

    try {
        $pdo->beginTransaction(); // İşlem başlat

        // E-posta kontrolü (kullanicilar tablosunda)
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM kullanicilar WHERE e_posta = :eposta");
        $stmt_check->bindParam(':eposta', $eposta, PDO::PARAM_STR);
        $stmt_check->execute();
        if ($stmt_check->fetchColumn() > 0) {
            $pdo->rollBack(); // İşlemi geri al
            return 'kayit_var';
        }

        // Şifreyi hashle
        $sifreHash = password_hash($sifre, PASSWORD_BCRYPT);

        // Kullanıcıyı 'kullanicilar' tablosuna ekle
        $stmt_kullanici = $pdo->prepare("INSERT INTO kullanicilar (e_posta, sifre_hash, rol) VALUES (:eposta, :sifre_hash, :rol)");
        $stmt_kullanici->bindParam(':eposta', $eposta, PDO::PARAM_STR);
        $stmt_kullanici->bindParam(':sifre_hash', $sifreHash, PDO::PARAM_STR);
        $stmt_kullanici->bindParam(':rol', $rol, PDO::PARAM_STR);
        $stmt_kullanici->execute();
        $kullanici_id = $pdo->lastInsertId(); // Eklenen kullanıcının ID'sini al

        // Rolüne göre ilgili tabloya ekle
        if ($rol === 'ogrenci') {
            $stmt_ogrenci = $pdo->prepare("INSERT INTO ogrenciler (kullanici_id, adi, soyadi) VALUES (:kullanici_id, :adi, :soyadi)");
            $stmt_ogrenci->bindParam(':kullanici_id', $kullanici_id, PDO::PARAM_INT);
            $stmt_ogrenci->bindParam(':adi', $ad, PDO::PARAM_STR);
            $stmt_ogrenci->bindParam(':soyadi', $soyad, PDO::PARAM_STR);
            $stmt_ogrenci->execute();
        } elseif ($rol === 'ogretmen') {
            $stmt_ogretmen = $pdo->prepare("INSERT INTO ogretmenler (kullanici_id, adi, soyadi, uzmanlik_alanlari) VALUES (:kullanici_id, :adi, :soyadi, :uzmanlik_alanlari)");
            $stmt_ogretmen->bindParam(':kullanici_id', $kullanici_id, PDO::PARAM_INT);
            $stmt_ogretmen->bindParam(':adi', $ad, PDO::PARAM_STR);
            $stmt_ogretmen->bindParam(':soyadi', $soyad, PDO::PARAM_STR);
            $stmt_ogretmen->bindParam(':uzmanlik_alanlari', $uzmanlikAlani, PDO::PARAM_STR); // uzmanlik_alanlari olarak bağla
            $stmt_ogretmen->execute();
            $ogretmen_id = $pdo->lastInsertId();

            // Öğretmenin verdiği dersleri kaydet
            if (!empty($secilenDersler)) {
                foreach ($secilenDersler as $ders_id) {
                    $stmt_ders = $pdo->prepare("INSERT INTO ogretmen_dersleri (ogretmen_id, ders_id) VALUES (:ogretmen_id, :ders_id)");
                    $stmt_ders->bindParam(':ogretmen_id', $ogretmen_id, PDO::PARAM_INT);
                    $stmt_ders->bindParam(':ders_id', $ders_id, PDO::PARAM_INT);
                    $stmt_ders->execute();
                }
            }
        }
        $pdo->commit(); // İşlemi onayla
        return 'basarili';
    } catch (PDOException $e) {
        $pdo->rollBack(); // Hata durumunda işlemi geri al
        error_log("Kayıt olma hatası: " . $e->getMessage()); // Hata loglama
        return 'hata';
    }
}

/**
 * Kullanıcı bilgilerini günceller (ad, soyad, e-posta, isteğe bağlı olarak şifre).
 *
 * @param int $kullaniciId Kullanıcının ID'si (kullanicilar tablosundaki ID).
 * @param string $rol Kullanıcının rolü.
 * @param string $ad Yeni adı.
 * @param string $soyad Yeni soyadı.
 * @param string $eposta Yeni e-posta adresi.
 * @param string|null $yeniSifre Yeni şifre (isteğe bağlı, boş değilse güncellenir).
 * @param string|null $uzmanlikAlani Öğretmen için yeni uzmanlık alanı (isteğe bağlı).
 * @return bool Başarılı ise true, değilse false.
 */
function kullaniciBilgileriniGuncelle(int $kullaniciId, string $rol, string $ad, string $soyad, string $eposta, ?string $yeniSifre = null, ?string $uzmanlikAlani = null): bool {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // 1. Kullanıcılar tablosunu güncelle (e-posta ve şifre)
        $sorgu_kullanici = "UPDATE kullanicilar SET e_posta = :eposta";
        if ($yeniSifre !== null && !empty($yeniSifre)) {
            $sorgu_kullanici .= ", sifre_hash = :sifre_hash";
        }
        $sorgu_kullanici .= " WHERE id = :kullanici_id";

        $stmt_kullanici = $pdo->prepare($sorgu_kullanici);
        $stmt_kullanici->bindParam(':eposta', $eposta, PDO::PARAM_STR);
        if ($yeniSifre !== null && !empty($yeniSifre)) {
            $sifreHash = password_hash($yeniSifre, PASSWORD_BCRYPT);
            $stmt_kullanici->bindParam(':sifre_hash', $sifreHash, PDO::PARAM_STR);
        }
        $stmt_kullanici->bindParam(':kullanici_id', $kullaniciId, PDO::PARAM_INT);
        $stmt_kullanici->execute();

        // 2. Rolüne göre ilgili tabloyu güncelle (ad, soyad, uzmanlık alanı)
        if ($rol === 'ogrenci') {
            $stmt_rol = $pdo->prepare("UPDATE ogrenciler SET adi = :adi, soyadi = :soyadi WHERE kullanici_id = :kullanici_id");
            $stmt_rol->bindParam(':adi', $ad, PDO::PARAM_STR);
            $stmt_rol->bindParam(':soyadi', $soyad, PDO::PARAM_STR);
            $stmt_rol->bindParam(':kullanici_id', $kullaniciId, PDO::PARAM_INT);
            $stmt_rol->execute();
        } elseif ($rol === 'ogretmen') {
            $sorgu_rol = "UPDATE ogretmenler SET adi = :adi, soyadi = :soyadi";
            if ($uzmanlikAlani !== null) { // Uzmanlık alanı boş gelirse güncelleme
                $sorgu_rol .= ", uzmanlik_alanlari = :uzmanlik_alanlari"; // Kolon adı güncellendi
            }
            $sorgu_rol .= " WHERE kullanici_id = :kullanici_id";

            $stmt_rol = $pdo->prepare($sorgu_rol);
            $stmt_rol->bindParam(':adi', $ad, PDO::PARAM_STR);
            $stmt_rol->bindParam(':soyadi', $soyad, PDO::PARAM_STR);
            if ($uzmanlikAlani !== null) {
                $stmt_rol->bindParam(':uzmanlik_alanlari', $uzmanlikAlani, PDO::PARAM_STR); // uzmanlik_alanlari olarak bağla
            }
            $stmt_rol->bindParam(':kullanici_id', $kullaniciId, PDO::PARAM_INT);
            $stmt_rol->execute();
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Kullanıcı bilgisi güncelleme hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Kullanıcıyı ve ilişkili tüm verilerini siler.
 * Bu fonksiyon, 'kullanicilar' tablosundaki foreign key kısıtlamalarının ON DELETE CASCADE olarak ayarlandığını varsayar.
 *
 * @param int $kullaniciId Silinecek kullanıcının ID'si.
 * @return bool Başarılı ise true, değilse false.
 */
function kullaniciSil(int $kullaniciId): bool {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Foreign key kısıtlamaları sayesinde ilgili tablolardaki (ogrenciler, ogretmenler, randevular, vb.)
        // kayıtlar otomatik olarak silinmelidir (CASCADE).
        $stmt = $pdo->prepare("DELETE FROM kullanicilar WHERE id = :kullanici_id");
        $stmt->bindParam(':kullanici_id', $kullaniciId, PDO::PARAM_INT);
        $success = $stmt->execute();

        if ($success && $stmt->rowCount() > 0) {
            $pdo->commit();
            return true;
        } else {
            $pdo->rollBack();
            return false;
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Kullanıcı silme hatası: " . $e->getMessage());
        return false;
    }
}
