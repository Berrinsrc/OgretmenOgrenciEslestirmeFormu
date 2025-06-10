<?php
// giris.php - Kullanıcı Giriş Sayfası
// Bu dosya, kullanıcıların sisteme giriş yapmasını sağlayan ana sayfadır.

require_once 'ayarlar.php'; // Veritabanı bağlantı ayarlarını ve oturum yönetimini içeren dosyayı dahil et

// Eğer kullanıcı zaten giriş yapmışsa, rolüne göre ilgili ana sayfaya yönlendir.
if (isset($_SESSION['giris_yapti']) && $_SESSION['giris_yapti'] === true) {
    if (isset($_SESSION['rol'])) {
        if ($_SESSION['rol'] == 'ogrenci') {
            header("Location: ogrenci_ana_sayfa.php"); // Öğrenci ana sayfasına yönlendir
            exit();
        } elseif ($_SESSION['rol'] == 'ogretmen') {
            header("Location: ogretmen_ana_sayfa.php"); // Öğretmen ana sayfasına yönlendir
            exit();
        }
    }
}

// Giriş formuna CSRF token ekleyeceğiz.
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Öğrenci Mentor Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .message-box {
            padding: 0.75rem 1.25rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col font-sans">
    <!-- Üst bilgi (header) kısmı -->
    <header class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Öğrenci Mentor Sistemi</h1>
            <!-- Giriş ve Kayıt sayfalarında basit bir başlık yeterlidir -->
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center py-8 px-4">
        <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Hesabınıza Giriş Yapın</h1>
            
            <?php
            // Hata veya başarı mesajlarını göster
            if (isset($_GET['hata'])) {
                $hata_mesaji = '';
                switch ($_GET['hata']) {
                    case 'giris_basarisiz':
                        $hata_mesaji = 'Geçersiz e-posta veya şifre. Lütfen tekrar deneyin.';
                        break;
                    case 'bos_alanlar':
                        $hata_mesaji = 'Lütfen e-posta ve şifrenizi girin.';
                        break;
                    case 'yetkisiz':
                        $hata_mesaji = 'Bu sayfaya erişim yetkiniz yok. Lütfen giriş yapın.';
                        break;
                    case 'rol_tanimsiz':
                        $hata_mesaji = 'Hesap rolünüz tanımlı değil. Yöneticinizle iletişime geçin.';
                        break;
                    case 'gecersiz_istek':
                        $hata_mesaji = 'Geçersiz istek. Lütfen formu kullanarak giriş yapın.';
                        break;
                    case 'csrf_gecersiz':
                        $hata_mesaji = 'Güvenlik hatası: Geçersiz istek. Lütfen tekrar deneyin.';
                        break;
                    case 'sifre_kisa': // Kayıt sayfasından gelen şifre hatası
                        $hata_mesaji = 'Şifreniz en az 6 karakter olmalıdır.';
                        break;
                    case 'gecersiz_eposta': // Kayıt sayfasından gelen e-posta hatası
                        $hata_mesaji = 'Lütfen geçerli bir e-posta adresi girin.';
                        break;
                    default:
                        $hata_mesaji = 'Giriş sırasında bilinmeyen bir hata oluştu. Lütfen tekrar deneyin.';
                }
                echo '<div class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-md text-center" role="alert">' . sanitizeOutput($hata_mesaji) . '</div>';
            }

            // Kayıt sonrası başarı mesajı
            if (isset($_GET['kayit_basarili'])) {
                 echo '<div class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-md text-center" role="alert">Kaydınız başarıyla tamamlandı! Şimdi giriş yapabilirsiniz.</div>';
            }

            // Çıkış yapıldığında gösterilecek mesaj
            if (isset($_GET['cikis']) && $_GET['cikis'] == 'basarili') {
                echo '<div class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-md text-center" role="alert">Başarıyla çıkış yaptınız. Tekrar görüşmek üzere!</div>';
            }
            ?>

            <form action="kontrol.php?islem=giris" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo sanitizeOutput($csrf_token); ?>">
                <div>
                    <label for="eposta" class="block text-sm font-medium text-gray-700">E-posta Adresi</label>
                    <input type="email" id="eposta" name="eposta" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="ornek@email.com">
                </div>
                <div>
                    <label for="sifre" class="block text-sm font-medium text-gray-700">Şifre</label>
                    <input type="password" id="sifre" name="sifre" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Şifrenizi girin">
                </div>
                <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Giriş Yap
                </button>
            </form>
            <p class="mt-8 text-center text-sm text-gray-600">
                Henüz bir hesabınız yok mu?
                <a href="kayit_ol.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">
                    Kayıt Olun
                </a>
            </p>
        </div>
    </main>

    <!-- Alt bilgi (footer) kısmı -->
    <?php include_once 'footer.php'; ?>
</body>
</html>
