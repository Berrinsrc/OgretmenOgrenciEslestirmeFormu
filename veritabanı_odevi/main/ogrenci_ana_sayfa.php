<?php
// ogrenci_ana_sayfa.php - Öğrenci Paneli Ana Sayfası
// Öğrenci burada randevu oluşturabilir ve bekleyen/geçmiş randevularını görebilir.

require_once 'ayarlar.php'; // Veritabanı bağlantısı ve oturum için
require_once 'ogrenci_islemleri.php'; // ogrenciDetayGetir fonksiyonu için bu dosya dahil edildi

// Oturum kontrolü: Öğrenci giriş yapmış mı?
if (!isset($_SESSION['giris_yapti']) || $_SESSION['rol'] !== 'ogrenci') {
    header("Location: giris.php?hata=yetkisiz"); // Giriş yapmamışsa veya öğrenci değilse giriş sayfasına yönlendir.
    exit();
}

$ogrenci_adi = $_SESSION['ad'] ?? 'Misafir';
$ogrenci_soyadi = $_SESSION['soyad'] ?? '';
$ogrenci_id = $_SESSION['ogrenci_id'] ?? 0;
$kullanici_id = $_SESSION['kullanici_id'] ?? 0;

// Mesajları göster (URL parametrelerinden)
$mesaj = '';
$mesaj_tipi = ''; // 'success' veya 'error'

if (isset($_GET['durum'])) {
    if ($_GET['durum'] == 'randevu_basarili') {
        $mesaj = 'Randevu talebiniz başarıyla oluşturuldu!';
        $mesaj_tipi = 'success';
    }
} elseif (isset($_GET['hata'])) {
    switch ($_GET['hata']) {
        case 'bos_alanlar':
            $mesaj = 'Lütfen tüm randevu bilgilerini doldurun.';
            break;
        case 'gecersiz_tarih':
            $mesaj = 'Lütfen geçerli bir randevu tarihi seçin.';
            break;
        case 'gecmis_tarih':
            $mesaj = 'Geçmiş bir tarihe randevu oluşturamazsınız.';
            break;
        case 'randevu_sirasi_hata':
            $mesaj = 'Randevu oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.';
            break;
        case 'ayni_randevu_var':
            $mesaj = 'Bu tarihte ve saatte zaten bir randevu talebiniz bulunuyor.';
            break;
        case 'ogretmen_musait_degil':
            $mesaj = 'Seçtiğiniz öğretmen bu saatte müsait değil. Lütfen başka bir saat veya öğretmen seçin.';
            break;
        default:
            $mesaj = 'Bir hata oluştu.';
    }
    $mesaj_tipi = 'error';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Paneli - Öğrenci Mentor Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Özel stil eklemeleri */
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
    <header class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Hoş Geldin, <?php echo htmlspecialchars($ogrenci_adi); ?>!</h1>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="ogrenci_ana_sayfa.php" class="hover:underline font-bold">Ana Sayfa</a></li>
                    <li><a href="ogrenci_profil.php" class="hover:underline">Profilim</a></li>
                    <li><a href="cikis.php" class="hover:underline">Çıkış Yap</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 flex-grow">
        <?php if (!empty($mesaj)): ?>
            <div class="message-box <?php echo $mesaj_tipi === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($mesaj); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Randevu Oluşturma Bölümü -->
            <div class="bg-white p-8 rounded-lg shadow-xl">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">Yeni Randevu Oluştur</h2>
                <form id="randevuForm" action="randevu_olustur.php" method="POST" class="space-y-6">
                    <div>
                        <label for="ders" class="block text-sm font-medium text-gray-700">Ders Seçin</label>
                        <select id="ders" name="ders_id" required 
                                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Ders Seçin...</option>
                            <!-- Dersler buraya JS ile yüklenecek -->
                        </select>
                    </div>
                    <div>
                        <label for="randevu_tarihi" class="block text-sm font-medium text-gray-700">Randevu Tarihi</label>
                        <input type="date" id="randevu_tarihi" name="randevu_tarihi" required 
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="randevu_saati" class="block text-sm font-medium text-gray-700">Randevu Saati</label>
                        <input type="time" id="randevu_saati" name="randevu_saati" required step="1800"
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <p class="text-xs text-gray-500 mt-1">Örn: 09:00, 09:30, 10:00</p>
                    </div>
                    <div>
                        <label for="ogretmen" class="block text-sm font-medium text-gray-700">Müsait Öğretmen Seçin</label>
                        <select id="ogretmen" name="ogretmen_id" required disabled
                                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm bg-gray-50">
                            <option value="">Ders, Tarih ve Saat seçerek öğretmenleri listeleyin.</option>
                            <!-- Müsait öğretmenler buraya JS ile yüklenecek -->
                        </select>
                        <p id="ogretmenYuklemeMesaji" class="text-sm text-gray-600 mt-2">Yukarıdaki alanları doldurarak müsait öğretmenleri listeleyebilirsiniz.</p>
                    </div>
                    <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Randevu Oluştur
                    </button>
                </form>
            </div>

            <!-- Bekleyen Randevular Bölümü -->
            <div class="bg-white p-8 rounded-lg shadow-xl">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">Bekleyen Randevularım</h2>
                <div id="bekleyenRandevularListesi" class="space-y-4">
                    <p class="text-gray-500 text-center">Randevular yükleniyor...</p>
                    <!-- Bekleyen randevular buraya JS ile yüklenecek -->
                </div>
            </div>
        </div>
    </main>

    <?php include_once 'footer.php'; ?>

    <div id="messageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full text-center">
            <p id="modalMessage" class="text-lg mb-4"></p>
            <button id="modalCloseBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">Tamam</button>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dersSelect = document.getElementById('ders');
            const randevuTarihiInput = document.getElementById('randevu_tarihi');
            const randevuSaatiInput = document.getElementById('randevu_saati');
            const ogretmenSelect = document.getElementById('ogretmen');
            const ogretmenYuklemeMesaji = document.getElementById('ogretmenYuklemeMesaji');
            const bekleyenRandevularListesi = document.getElementById('bekleyenRandevularListesi');
            const messageModal = document.getElementById('messageModal');
            const modalMessage = document.getElementById('modalMessage');
            const modalCloseBtn = document.getElementById('modalCloseBtn');
            const randevuForm = document.getElementById('randevuForm'); // Formu seç

            function showMessageModal(message) {
                modalMessage.textContent = message;
                messageModal.classList.remove('hidden');
            }

            modalCloseBtn.addEventListener('click', function() {
                messageModal.classList.add('hidden');
            });

            // Minimum tarih bugün
            const bugun = new Date();
            const yyyy = bugun.getFullYear();
            const mm = String(bugun.getMonth() + 1).padStart(2, '0'); // Ay 0-indexed olduğu için +1
            const dd = String(bugun.getDate()).padStart(2, '0');
            randevuTarihiInput.min = `${yyyy}-${mm}-${dd}`;

            async function loadDersler() {
                try {
                    const response = await fetch('get_dersler.php');
                    const data = await response.json();
                    if (data.success && data.dersler.length > 0) {
                        data.dersler.forEach(ders => {
                            const option = document.createElement('option');
                            option.value = ders.ders_id;
                            option.textContent = ders.ders_adi;
                            dersSelect.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Ders bulunamadı.';
                        dersSelect.appendChild(option);
                        dersSelect.disabled = true;
                    }
                } catch (error) {
                    console.error('Dersler yüklenirken hata oluştu:', error);
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Dersler yüklenemedi.';
                    dersSelect.appendChild(option);
                    dersSelect.disabled = true;
                }
            }

            async function loadOgretmenler() {
                const dersId = dersSelect.value;
                const tarih = randevuTarihiInput.value;
                const saat = randevuSaatiInput.value; // Corrected: Used 'saat'

                ogretmenSelect.innerHTML = '<option value="">Öğretmenler yükleniyor...</option>';
                ogretmenSelect.disabled = true;
                ogretmenYuklemeMesaji.textContent = 'Müsait öğretmenler aranıyor...';

                if (dersId && tarih && saat) {
                    try {
                        const response = await fetch(`musait_ogretmenleri_getir.php?ders_id=${dersId}&randevu_tarihi=${tarih}&randevu_saati=${saat}`); // Corrected: Used 'saat'
                        const data = await response.json();

                        ogretmenSelect.innerHTML = ''; // Önceki seçenekleri temizle
                        if (data.success && data.ogretmenler.length > 0) {
                            data.ogretmenler.forEach(ogretmen => {
                                const option = document.createElement('option');
                                option.value = ogretmen.ogretmen_id;
                                // Ad ve soyadı doğru kolondan alıyoruz
                                option.textContent = `${sanitizeOutput(ogretmen.adi)} ${sanitizeOutput(ogretmen.soyadi)}`;
                                ogretmenSelect.appendChild(option);
                            });
                            ogretmenSelect.disabled = false;
                            ogretmenYuklemeMesaji.textContent = '';
                        } else {
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'Bu ders, tarih ve saat için müsait öğretmen bulunamadı.';
                            ogretmenSelect.appendChild(option);
                            ogretmenSelect.disabled = true;
                            ogretmenYuklemeMesaji.textContent = data.message || 'Müsait öğretmen bulunamadı.';
                        }
                    } catch (error) {
                        console.error('Müsait öğretmenler yüklenirken hata oluştu:', error);
                        ogretmenSelect.innerHTML = '<option value="">Öğretmenler yüklenemedi.</option>';
                        ogretmenSelect.disabled = true;
                        ogretmenYuklemeMesaji.textContent = 'Öğretmenler yüklenirken bir ağ hatası oluştu.';
                    }
                } else {
                    ogretmenSelect.innerHTML = '<option value="">Ders, Tarih ve Saat seçerek öğretmenleri listeleyin.</option>';
                    ogretmenSelect.disabled = true;
                    ogretmenYuklemeMesaji.textContent = 'Yukarıdaki alanları doldurarak müsait öğretmenleri listeleyebilirsiniz.';
                }
            }

            async function loadBekleyenRandevular() {
                bekleyenRandevularListesi.innerHTML = '<p class="text-gray-500 text-center">Randevular yükleniyor...</p>';
                try {
                    const response = await fetch('ogrenci_randevularini_getir.php');
                    const data = await response.json();

                    bekleyenRandevularListesi.innerHTML = ''; // Önceki mesajı temizle
                    if (data.success && data.randevular.length > 0) {
                        data.randevular.forEach(randevu => {
                            const randevuItem = document.createElement('div');
                            randevuItem.className = 'bg-gray-50 p-4 rounded-md shadow-sm border border-gray-200';
                            randevuItem.innerHTML = `
                                <p class="text-lg font-semibold text-gray-900">
                                    <span class="text-blue-700">${sanitizeOutput(randevu.ders_adi)}</span> dersi için
                                    <span class="text-purple-700">${sanitizeOutput(randevu.ogretmen_adi)} ${sanitizeOutput(randevu.ogretmen_soyadi)}</span> ile
                                </p>
                                <p class="text-gray-700 mt-1">
                                    <strong class="text-gray-800">Tarih:</strong> ${new Date(randevu.randevu_tarihi).toLocaleDateString('tr-TR')} |
                                    <strong class="text-gray-800">Saat:</strong> ${randevu.randevu_baslangic_saati.substring(0, 5)}
                                </p>
                                <p class="text-gray-700 mt-1">
                                    <strong class="text-gray-800">Durum:</strong> 
                                    <span class="${
                                        randevu.randevu_durumu === 'onaylandi' ? 'text-green-600 font-medium' :
                                        randevu.randevu_durumu === 'beklemede' ? 'text-yellow-600 font-medium' :
                                        'text-red-600 font-medium'
                                    }">
                                    ${sanitizeOutput(randevu.randevu_durumu === 'beklemede' ? 'Beklemede' :
                                       randevu.randevu_durumu === 'onaylandi' ? 'Onaylandı' :
                                       randevu.randevu_durumu === 'reddedildi' ? 'Reddedildi' :
                                       'İptal Edildi')}
                                    </span>
                                </p>
                                ${randevu.ogretmen_aciklama ? `<p class="text-gray-600 text-sm mt-2"><strong class="text-gray-800">Öğretmen Açıklaması:</strong> ${sanitizeOutput(randevu.ogretmen_aciklama)}</p>` : ''}
                            `;
                            bekleyenRandevularListesi.appendChild(randevuItem);
                        });
                    } else {
                        bekleyenRandevularListesi.innerHTML = '<p class="text-gray-600 text-center">Henüz bekleyen randevunuz bulunmuyor.</p>';
                    }
                } catch (error) {
                    console.error('Bekleyen randevular yüklenirken hata oluştu:', error);
                    bekleyenRandevularListesi.innerHTML = '<p class="text-red-500 text-center">Randevular yüklenemedi. Bir hata oluştu.</p>';
                }
            }

            // --- Form Gönderme İşlemi (AJAX) ---
            randevuForm.addEventListener('submit', async function(event) {
                event.preventDefault(); // Formun varsayılan gönderilmesini engelle

                const formData = new FormData(randevuForm);
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true; // Butonu devre dışı bırak
                submitButton.textContent = 'Randevu Oluşturuluyor...'; // Buton metnini değiştir

                try {
                    const response = await fetch('randevu_olustur.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json(); // JSON yanıtını parse et

                    if (data.success) {
                        showMessageModal(data.message);
                        // Başarılı olursa sayfayı yeniden yönlendir
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1500); // 1.5 saniye sonra yönlendir
                        }
                    } else {
                        showMessageModal(data.message); // Hata mesajını göster
                    }
                } catch (error) {
                    console.error('Randevu oluşturulurken ağ hatası:', error);
                    showMessageModal('Randevu oluşturulurken bir ağ hatası oluştu. Lütfen tekrar deneyin.');
                } finally {
                    submitButton.disabled = false; // Butonu tekrar etkinleştir
                    submitButton.textContent = 'Randevu Oluştur'; // Buton metnini geri yükle
                }
            });


            // Olay Dinleyicileri
            dersSelect.addEventListener('change', loadOgretmenler);
            randevuTarihiInput.addEventListener('change', loadOgretmenler);
            randevuSaatiInput.addEventListener('change', loadOgretmenler);

            // Sayfa yüklendiğinde dersleri ve bekleyen randevuları yükle
            loadDersler();
            loadBekleyenRandevular();
        });

        // Global sanitizeOutput fonksiyonu (PHP'den kopyalandı, JS için uyarlandı)
        function sanitizeOutput(text) {
            const element = document.createElement('div');
            element.innerText = text; // Metni güvenli bir şekilde ata
            return element.innerHTML; // HTML olarak geri döndür
        }
    </script>
</body>
</html>
