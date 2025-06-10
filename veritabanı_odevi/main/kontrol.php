<?php
// kontrol.php - Kayıt ve Giriş İşlemleri Yöneticisi
// Bu dosya, kullanıcıların kayıt olmasını ve sisteme giriş yapmasını yönetir.

require_once 'ayarlar.php'; // Veritabanı bağlantısı ve oturum başlatma için
require_once 'kullanici_islemleri.php'; // Kullanıcı işlemleri fonksiyonları için

// İşlem türünü al (URL'den gelen 'islem' parametresi ile)
$islem = isset($_GET['islem']) ? $_GET['islem'] : '';

// Sadece POST isteklerini işleriz, güvenlik için önemlidir.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Kayıt işlemi
    if ($islem == 'kayit') {
        // CSRF Token kontrolü
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            header("Location: kayit_ol.php?hata=csrf_gecersiz");
            exit();
        }

        // Formdan gelen verileri al ve boşlukları temizle (ayarlar.php'deki otomatik trim zaten var)
        $ad = $_POST['ad'] ?? '';
        $soyad = $_POST['soyad'] ?? '';
        $eposta = $_POST['eposta'] ?? '';
        $sifre = $_POST['sifre'] ?? '';
        $rol = $_POST['rol'] ?? '';
        $secilen_dersler = isset($_POST['dersler']) ? (array)$_POST['dersler'] : []; // Öğretmen için seçilen dersler
        // Öğretmen ise uzmanlık alanı bilgisini al
        $uzmanlik_alani = ($rol === 'ogretmen') ? ($_POST['uzmanlik_alanlari'] ?? null) : null; 

        // Basit doğrulama: Alanlar boş mu?
        if (empty($ad) || empty($soyad) || empty($eposta) || empty($sifre) || empty($rol)) {
            header("Location: kayit_ol.php?hata=bos_alanlar");
            exit();
        }

        // E-posta format kontrolü
        if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
            header("Location: kayit_ol.php?hata=gecersiz_eposta");
            exit();
        }

        // Şifre uzunluk kontrolü
        if (strlen($sifre) < 6) {
            header("Location: kayit_ol.php?hata=sifre_kisa");
            exit();
        }

        // Kayıt işlemini başlat
        $kayit_sonuc = kayitOl($ad, $soyad, $eposta, $sifre, $rol, $secilen_dersler, $uzmanlik_alani); // uzmanlik_alani'nı pass et

        if ($kayit_sonuc === 'basarili') {
            header("Location: giris.php?kayit_basarili=true");
            exit();
        } elseif ($kayit_sonuc === 'kayit_var') {
            header("Location: kayit_ol.php?hata=kayit_var");
            exit();
        } else {
            header("Location: kayit_ol.php?hata=kayit_sirasi_hata");
            exit();
        }
    }
    // Giriş işlemi
    elseif ($islem == 'giris') {
        // CSRF Token kontrolü (Giriş formunda token yoksa bu yorum satırı kalabilir)
        /*
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            header("Location: giris.php?hata=csrf_gecersiz");
            exit();
        }
        */

        $eposta = $_POST['eposta'] ?? '';
        $sifre = $_POST['sifre'] ?? '';

        if (empty($eposta) || empty($sifre)) {
            header("Location: giris.php?hata=bos_alanlar");
            exit();
        }

        $kullanici_bilgileri = girisYap($eposta, $sifre);

        if ($kullanici_bilgileri) {
            // Oturum değişkenlerini ayarla
            $_SESSION['giris_yapti'] = true;
            $_SESSION['kullanici_id'] = $kullanici_bilgileri['id'];
            $_SESSION['rol'] = $kullanici_bilgileri['rol'];
            $_SESSION['ad'] = $kullanici_bilgileri['ad'];
            $_SESSION['soyad'] = $kullanici_bilgileri['soyad'];

            // Rolüne göre yönlendir
            if ($kullanici_bilgileri['rol'] == 'ogrenci') {
                $_SESSION['ogrenci_id'] = $kullanici_bilgileri['ogrenci_id'];
                header("Location: ogrenci_ana_sayfa.php");
                exit();
            } elseif ($kullanici_bilgileri['rol'] == 'ogretmen') {
                $_SESSION['ogretmen_id'] = $kullanici_bilgileri['ogretmen_id'];
                $_SESSION['uzmanlik_alanlari'] = $kullanici_bilgileri['brans'] ?? ''; // 'brans' yerine 'uzmanlik_alanlari' olarak kaydet
                header("Location: ogretmen_ana_sayfa.php");
                exit();
            } else {
                // Tanımsız rol durumu, güvenlik için çıkış yap
                session_unset();
                session_destroy();
                header("Location: giris.php?hata=rol_tanimsiz");
                exit();
            }
        } else {
            // Hatalı giriş
            header("Location: giris.php?hata=giris_basarisiz");
            exit();
        }
    }
    // Profil güncelleme işlemi
    elseif ($islem == 'profil_guncelle') {
        // CSRF Token kontrolü
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            // Geri dönülecek sayfa rolüne göre belirlenir
            $redirect_page = ($_SESSION['rol'] === 'ogrenci' ? 'ogrenci_profil.php' : 'ogretmen_profil.php');
            header("Location: " . $redirect_page . "?hata=csrf_gecersiz");
            exit();
        }

        // Oturum kontrolü
        if (!isset($_SESSION['giris_yapti']) || $_SESSION['giris_yapti'] !== true) {
            header("Location: giris.php?hata=yetkisiz");
            exit();
        }

        $kullanici_id = $_SESSION['kullanici_id'] ?? 0;
        $rol = $_SESSION['rol'] ?? '';
        $ad = $_POST['ad'] ?? '';
        $soyad = $_POST['soyad'] ?? '';
        $eposta = $_POST['eposta'] ?? '';
        $yeni_sifre = $_POST['yeni_sifre'] ?? null;
        $sifre_tekrar = $_POST['sifre_tekrar'] ?? null;
        // Öğretmen ise uzmanlık alanı bilgisini al
        $uzmanlik_alani = ($rol === 'ogretmen') ? ($_POST['uzmanlik_alanlari'] ?? null) : null; 

        // Alanların boş olup olmadığını kontrol et
        if (empty($ad) || empty($soyad) || empty($eposta)) {
            $redirect_page = ($rol === 'ogrenci' ? 'ogrenci_profil.php' : 'ogretmen_profil.php');
            header("Location: " . $redirect_page . "?hata=bos_alanlar");
            exit();
        }
        // E-posta format kontrolü
        if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
            $redirect_page = ($rol === 'ogrenci' ? 'ogrenci_profil.php' : 'ogretmen_profil.php');
            header("Location: " . $redirect_page . "?hata=gecersiz_eposta");
            exit();
        }

        // Şifreler girildiyse eşleşip eşleşmediğini kontrol et
        if (($yeni_sifre !== null && !empty($yeni_sifre)) || ($sifre_tekrar !== null && !empty($sifre_tekrar))) {
            if ($yeni_sifre !== $sifre_tekrar) {
                $redirect_page = ($rol === 'ogrenci' ? 'ogrenci_profil.php' : 'ogretmen_profil.php');
                header("Location: " . $redirect_page . "?hata=sifreler_eslesmiyor");
                exit();
            }
            if (strlen($yeni_sifre) < 6) { // Minimum şifre uzunluğu kontrolü
                $redirect_page = ($rol === 'ogrenci' ? 'ogrenci_profil.php' : 'ogretmen_profil.php');
                header("Location: " . $redirect_page . "?hata=sifre_kisa");
                exit();
            }
        } else {
            // Şifre güncellenmeyecekse null gönder
            $yeni_sifre = null;
        }

        // Kullanıcı bilgilerini güncelle
        $guncelleme_basarili = kullaniciBilgileriniGuncelle($kullanici_id, $rol, $ad, $soyad, $eposta, $yeni_sifre, $uzmanlik_alani); // uzmanlik_alani'nı pass et

        if ($guncelleme_basarili) {
            // Oturum bilgilerini güncelle
            $_SESSION['ad'] = $ad;
            $_SESSION['soyad'] = $soyad;
            if ($rol === 'ogretmen') {
                $_SESSION['uzmanlik_alanlari'] = $uzmanlik_alani; // Oturum anahtarını doğru kolon adı ile güncelle
            }
            $redirect_page = ($rol === 'ogrenci' ? 'ogrenci_profil.php' : 'ogretmen_profil.php');
            header("Location: " . $redirect_page . "?durum=guncelleme_basarili");
            exit();
        } else {
            $redirect_page = ($rol === 'ogrenci' ? 'ogrenci_profil.php' : 'ogretmen_profil.php');
            header("Location: " . $redirect_page . "?hata=guncelleme_basarisiz");
            exit();
        }
    }
    else {
        // Geçersiz işlem
        header("Location: giris.php?hata=gecersiz_istek");
        exit();
    }
} else {
    // POST isteği değilse ana sayfaya yönlendir
    header("Location: giris.php");
    exit();
}
?>
