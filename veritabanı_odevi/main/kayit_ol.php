<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Öğrenci Mentor Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col font-sans">
    <!-- Üst bilgi (header) kısmı -->
    <header class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Öğrenci Mentor Sistemi</h1>
            <!-- Giriş ve Kayıt sayfalarında basit bir başlık yeterlidir -->
        </div>
    </header>

    <div class="flex-grow flex items-center justify-center py-8 px-4">
        <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Yeni Hesap Oluştur</h1>
            
            <?php
            // ayarlar.php dosyasını dahil et
            require_once 'ayarlar.php';

            // Hata mesajlarını göster
            if (isset($_GET['hata'])) {
                $hata_mesaji = '';
                switch ($_GET['hata']) {
                    case 'kayit_var':
                        $hata_mesaji = 'Bu e-posta adresi zaten kayıtlı!';
                        break;
                    case 'bos_alanlar':
                        $hata_mesaji = 'Lütfen tüm gerekli alanları doldurun.';
                        break;
                    case 'gecersiz_eposta':
                        $hata_mesaji = 'Lütfen geçerli bir e-posta adresi girin.';
                        break;
                    case 'sifre_kisa':
                        $hata_mesaji = 'Şifreniz en az 6 karakter olmalıdır.';
                        break;
                    case 'kayit_sirasi_hata':
                        $hata_mesaji = 'Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.';
                        break;
                    case 'csrf_gecersiz':
                        $hata_mesaji = 'Güvenlik hatası: Geçersiz istek. Lütfen tekrar deneyin.';
                        break;
                    default:
                        $hata_mesaji = 'Bir hata oluştu. Lütfen tekrar deneyin.';
                }
                echo '<div class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-md text-center" role="alert">' . sanitizeOutput($hata_mesaji) . '</div>';
            }

            // CSRF token oluştur
            $csrf_token = generateCsrfToken();
            ?>

            <form action="kontrol.php?islem=kayit" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo sanitizeOutput($csrf_token); ?>">
                <div>
                    <label for="ad" class="block text-sm font-medium text-gray-700">Adınız</label>
                    <input type="text" id="ad" name="ad" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Adınızı girin">
                </div>
                <div>
                    <label for="soyad" class="block text-sm font-medium text-gray-700">Soyadınız</label>
                    <input type="text" id="soyad" name="soyad" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Soyadınızı girin">
                </div>
                <div>
                    <label for="eposta" class="block text-sm font-medium text-gray-700">E-posta Adresiniz</label>
                    <input type="email" id="eposta" name="eposta" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="ornek@email.com">
                </div>
                <div>
                    <label for="sifre" class="block text-sm font-medium text-gray-700">Şifreniz</label>
                    <input type="password" id="sifre" name="sifre" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="En az 6 karakter">
                </div>
                <div>
                    <label for="rol" class="block text-sm font-medium text-gray-700">Rolünüz</label>
                    <select id="rol" name="rol" required 
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Lütfen seçin...</option>
                        <option value="ogrenci">Öğrenci</option>
                        <option value="ogretmen">Öğretmen</option>
                    </select>
                </div>

                <div id="ogretmenEkAlanlari" class="hidden space-y-6">
                    <div>
                        <label for="uzmanlik_alanlari" class="block text-sm font-medium text-gray-700">Uzmanlık Alanınız (Branş)</label>
                        <input type="text" id="uzmanlik_alanlari" name="uzmanlik_alanlari"
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Örn: Matematik, Fizik, İngilizce">
                    </div>
                    <div id="dersSecimAlani">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Verdiğiniz Dersler (Birden Fazla Seçilebilir)</label>
                        <div id="derslerListesi" class="space-y-2">
                            <!-- Dersler buraya JavaScript ile yüklenecek -->
                            <p class="text-gray-500">Dersler yükleniyor...</p>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Kayıt Ol
                </button>
            </form>
            <p class="mt-8 text-center text-sm text-gray-600">
                Zaten bir hesabınız var mı?
                <a href="giris.php" class="font-medium text-blue-600 hover:text-blue-500 hover:underline">
                    Giriş Yapın
                </a>
            </p>
        </div>
    </div>
    <footer class="bg-gray-800 text-white p-4 text-center text-sm mt-auto">
        <p>&copy; <?php echo date("Y"); ?> Öğrenci Mentor Sistemi. Tüm hakları saklıdır.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rolSelect = document.getElementById('rol');
            const ogretmenEkAlanlari = document.getElementById('ogretmenEkAlanlari');
            const derslerListesi = document.getElementById('derslerListesi');
            const uzmanlikAlanlariInput = document.getElementById('uzmanlik_alanlari');

            function toggleOgretmenFields() {
                if (rolSelect.value === 'ogretmen') {
                    ogretmenEkAlanlari.classList.remove('hidden');
                    uzmanlikAlanlariInput.setAttribute('required', 'required'); // Branş zorunlu olsun
                    loadDersler(); // Öğretmen seçilince dersleri yükle
                } else {
                    ogretmenEkAlanlari.classList.add('hidden');
                    uzmanlikAlanlariInput.removeAttribute('required'); // Zorunluluğu kaldır
                    derslerListesi.innerHTML = '<p class="text-gray-500">Dersler yükleniyor...</p>'; // Alanı temizle
                }
            }

            async function loadDersler() {
                derslerListesi.innerHTML = '<p class="text-gray-500">Dersler yükleniyor...</p>';
                try {
                    const response = await fetch('get_dersler.php');
                    const data = await response.json();

                    if (data.success && data.dersler.length > 0) {
                        derslerListesi.innerHTML = ''; // Önceki mesajı temizle
                        data.dersler.forEach(ders => {
                            const checkboxDiv = document.createElement('div');
                            checkboxDiv.className = 'flex items-center';
                            checkboxDiv.innerHTML = `
                                <input id="ders_${ders.ders_id}" name="dersler[]" type="checkbox" value="${ders.ders_id}"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="ders_${ders.ders_id}" class="ml-2 block text-sm text-gray-900">
                                    ${sanitizeOutput(ders.ders_adi)}
                                </label>
                            `;
                            derslerListesi.appendChild(checkboxDiv);
                        });
                    } else {
                        derslerListesi.innerHTML = '<p class="text-gray-500">Hiç ders bulunamadı.</p>';
                    }
                } catch (error) {
                    console.error('Dersler yüklenirken hata oluştu:', error);
                    derslerListesi.innerHTML = '<p class="text-red-500">Dersler yüklenemedi. Bir hata oluştu.</p>';
                }
            }
            
            // Output sanitization for JS, assuming sanitizeOutput is available or handled by PHP
            function sanitizeOutput(text) {
                const element = document.createElement('div');
                element.innerText = text;
                return element.innerHTML;
            }


            // Sayfa yüklendiğinde ve rol değiştiğinde kontrol et
            rolSelect.addEventListener('change', toggleOgretmenFields);
            toggleOgretmenFields(); // Sayfa yüklendiğinde başlangıç durumunu ayarla
        });
    </script>
</body>
</html>
