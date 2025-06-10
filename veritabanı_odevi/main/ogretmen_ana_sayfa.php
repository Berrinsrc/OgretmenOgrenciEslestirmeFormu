<?php
// ogretmen_ana_sayfa.php - Öğretmen Ana Sayfası
// Bu sayfa öğretmenlerin öğrenci listesini ve randevu isteklerini görmesini sağlar.

require_once 'ayarlar.php'; // Veritabanı bağlantısı ve oturum için
require_once 'ogretmen_islemleri.php'; // Öğretmen detaylarını çekmek için (eğer gerekirse)

// Oturum kontrolü: Giriş yapıp yapmadığını ve öğretmen olup olmadığını kontrol ederiz.
if (!isset($_SESSION['giris_yapti']) || $_SESSION['giris_yapti'] !== true || $_SESSION['rol'] !== 'ogretmen') {
    header("Location: giris.php?hata=yetkisiz");
    exit();
}

$ad = $_SESSION['ad'] ?? 'Öğretmen';
$soyad = $_SESSION['soyad'] ?? '';
$ogretmen_id = $_SESSION['ogretmen_id'] ?? 0; // Öğretmenin ID'si
$kullanici_id = $_SESSION['kullanici_id'] ?? 0; // Kullanıcı ID'si

// Mesajları göster (URL parametrelerinden)
$mesaj = '';
$mesaj_tipi = ''; // 'success' veya 'error'

if (isset($_GET['durum'])) {
    if ($_GET['durum'] == 'guncelleme_basarili') {
        $mesaj = 'Randevu durumu başarıyla güncellendi.';
        $mesaj_tipi = 'success';
    }
} elseif (isset($_GET['hata'])) {
    switch ($_GET['hata']) {
        case 'guncelleme_basarisiz':
            $mesaj = 'Randevu durumu güncellenirken bir hata oluştu.';
            break;
        case 'yetkisiz':
            $mesaj = 'Bu işlemi yapmaya yetkiniz yok.';
            break;
        case 'csrf_gecersiz':
            $mesaj = 'Güvenlik hatası: Geçersiz istek. Lütfen tekrar deneyin.';
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
    <title>Öğretmen Ana Sayfası</title>
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
    <header class="bg-blue-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">Hoş Geldin, <?php echo sanitizeOutput($ad); ?>!</h1>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="ogretmen_ana_sayfa.php" class="hover:underline font-bold">Ana Sayfa</a></li>
                    <li><a href="ogretmen_profil.php" class="hover:underline">Profilim</a></li>
                    <li><a href="cikis.php" class="hover:underline">Çıkış Yap</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container mx-auto p-6 flex-grow">
        <?php if (!empty($mesaj)): ?>
            <div class="message-box <?php echo $mesaj_tipi === 'success' ? 'success' : 'error'; ?>">
                <?php echo sanitizeOutput($mesaj); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Randevu Talepleri Bölümü -->
            <div class="bg-white p-8 rounded-lg shadow-xl">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">Randevu Talepleri</h2>
                <div id="randevuTalepleriListesi" class="space-y-4">
                    <p class="text-gray-500 text-center">Randevu talepleri yükleniyor...</p>
                    <!-- Randevu talepleri buraya JS ile yüklenecek -->
                </div>
            </div>

            <!-- Öğrenci Listesi Bölümü -->
            <div class="bg-white p-8 rounded-lg shadow-xl">
                <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">Tüm Öğrenciler</h2>
                <div id="tumOgrencilerListesi" class="space-y-4">
                    <p class="text-gray-500 text-center">Öğrenciler yükleniyor...</p>
                    <!-- Öğrenci listesi buraya JS ile yüklenecek -->
                </div>
            </div>
        </div>

        <div class="profil-ayarlar-bolumu bg-white p-8 rounded-lg shadow-xl mt-8">
            <h3 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">Hesap Ayarları</h3>
            <button id="hesabimiSilBtn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-md shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                Hesabımı Sil
            </button>
        </div>
    </main>

    <?php include_once 'footer.php'; ?>

    <!-- Modal Yapısı -->
    <div id="messageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full text-center">
            <p id="modalMessage" class="text-lg mb-4"></p>
            <textarea id="aciklamaTextarea" class="w-full p-2 border rounded-md hidden mb-4" placeholder="Açıklama ekle (isteğe bağlı)"></textarea>
            <button id="modalConfirmBtn" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md mr-2 hidden">Onayla</button>
            <button id="modalCancelBtn" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-md hidden">İptal</button>
            <button id="modalCloseBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">Tamam</button>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const randevuTalepleriListesi = document.getElementById('randevuTalepleriListesi');
            const tumOgrencilerListesi = document.getElementById('tumOgrencilerListesi');
            const hesabimiSilBtn = document.getElementById('hesabimiSilBtn');

            const messageModal = document.getElementById('messageModal');
            const modalMessage = document.getElementById('modalMessage');
            const aciklamaTextarea = document.getElementById('aciklamaTextarea');
            const modalConfirmBtn = document.getElementById('modalConfirmBtn');
            const modalCancelBtn = document.getElementById('modalCancelBtn');
            const modalCloseBtn = document.getElementById('modalCloseBtn');

            let currentRandevuId = null;
            let currentDurum = null;

            // Modal'ı gösteren ve opsiyonel olarak onay/iptal butonlarını ayarlayan fonksiyon
            function showMessageModal(message, type = 'info', randevuId = null, durum = null) {
                modalMessage.textContent = message;
                aciklamaTextarea.value = ''; // Her açılışta temizle
                aciklamaTextarea.classList.add('hidden');
                modalConfirmBtn.classList.add('hidden');
                modalCancelBtn.classList.add('hidden');
                modalCloseBtn.classList.remove('hidden'); // Tamam butonu her zaman görünür

                currentRandevuId = randevuId;
                currentDurum = durum;

                if (type === 'confirm') { // Onay/Reddetme işlemi için
                    aciklamaTextarea.classList.remove('hidden');
                    modalConfirmBtn.classList.remove('hidden');
                    modalCancelBtn.classList.remove('hidden');
                    modalCloseBtn.classList.add('hidden'); // Onay/İptal varsa Tamam gizlensin
                } else if (type === 'delete_confirm') { // Hesap silme için
                    modalConfirmBtn.classList.remove('hidden');
                    modalCancelBtn.classList.remove('hidden');
                    modalCloseBtn.classList.add('hidden'); // Onay/İptal varsa Tamam gizlensin
                }
                messageModal.classList.remove('hidden');
            }

            // Modal Kapatma
            modalCloseBtn.addEventListener('click', function() {
                messageModal.classList.add('hidden');
            });

            // Randevu Onay/Reddetme İşlemi için modal onay
            modalConfirmBtn.addEventListener('click', async function() {
                if (currentRandevuId && currentDurum) { // Randevu durumu güncelleme
                    const aciklama = aciklamaTextarea.value.trim();
                    try {
                        const response = await fetch('randevu_durumunu_guncelle.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `randevu_id=${currentRandevuId}&durum=${currentDurum}&aciklama=${encodeURIComponent(aciklama)}`
                        });
                        const data = await response.json();

                        if (data.success) {
                            showMessageModal(data.message, 'info');
                            // Randevu başarıyla güncellendiğinde sayfayı yenile veya ilgili bölümü yeniden yükle
                            if (data.redirect) {
                                setTimeout(() => {
                                    window.location.href = data.redirect;
                                }, 1500); // 1.5 saniye sonra yönlendir
                            } else {
                                loadTeacherAppointments(); // Sadece randevu listesini yenile
                            }
                        } else {
                            showMessageModal(data.message, 'info');
                        }
                    } catch (error) {
                        console.error('Randevu durumu güncellenirken ağ hatası:', error);
                        showMessageModal('Randevu durumu güncellenirken bir ağ hatası oluştu.', 'info');
                    }
                } else if (modalMessage.textContent.includes('Hesabınızı silmek')) { // Hesap silme onay
                    deleteAccount();
                }
                // Modalı onaylandıktan sonra kapat (redirect varsa zaten kapanacak)
                if (!currentRandevuId || (currentRandevuId && !modalMessage.textContent.includes('Yönlendiriliyorsunuz'))) {
                    messageModal.classList.add('hidden');
                }
            });

            // Modal İptal
            modalCancelBtn.addEventListener('click', function() {
                messageModal.classList.add('hidden');
                aciklamaTextarea.value = ''; // Temizle
            });

            // --- Öğrenci Listesini Yükleme ---
            async function loadAllStudents() {
                tumOgrencilerListesi.innerHTML = '<p class="text-gray-500 text-center">Öğrenciler yükleniyor...</p>';
                try {
                    const response = await fetch('tum_ogrencileri_getir.php');
                    const data = await response.json();

                    tumOgrencilerListesi.innerHTML = ''; // Önceki mesajı temizle
                    if (data.success && data.ogrenciler.length > 0) {
                        data.ogrenciler.forEach(ogrenci => {
                            const studentItem = document.createElement('div');
                            studentItem.className = 'bg-gray-50 p-4 rounded-md shadow-sm border border-gray-200';
                            studentItem.innerHTML = `
                                <p class="text-lg font-semibold text-gray-900">${sanitizeOutput(ogrenci.adi)} ${sanitizeOutput(ogrenci.soyadi)}</p>
                                <p class="text-gray-600 text-sm">${sanitizeOutput(ogrenci.e_posta)}</p>
                            `;
                            tumOgrencilerListesi.appendChild(studentItem);
                        });
                    } else {
                        tumOgrencilerListesi.innerHTML = '<p class="text-gray-600 text-center">Sistemde kayıtlı öğrenci bulunmuyor.</p>';
                    }
                } catch (error) {
                    console.error('Öğrenciler yüklenirken hata oluştu:', error);
                    tumOgrencilerListesi.innerHTML = '<p class="text-red-500 text-center">Öğrenci listesi yüklenemedi. Bir hata oluştu.</p>';
                }
            }

            // --- Öğretmen Randevularını Yükleme (Randevu Talepleri) ---
            async function loadTeacherAppointments() {
                randevuTalepleriListesi.innerHTML = '<p class="text-gray-500 text-center">Randevu talepleri yükleniyor...</p>';
                try {
                    const response = await fetch('ogretmen_randevularini_getir.php');
                    const data = await response.json();

                    randevuTalepleriListesi.innerHTML = ''; // Önceki mesajı temizle
                    if (data.success && data.randevular.length > 0) {
                        data.randevular.forEach(randevu => {
                            const randevuItem = document.createElement('div');
                            randevuItem.className = 'bg-gray-50 p-4 rounded-md shadow-sm border border-gray-200';
                            randevuItem.innerHTML = `
                                <p class="text-lg font-semibold text-gray-900">
                                    <span class="text-blue-700">${sanitizeOutput(randevu.ders_adi)}</span> dersi için
                                    <span class="text-purple-700">${sanitizeOutput(randevu.ogrenci_adi)} ${sanitizeOutput(randevu.ogrenci_soyadi)}</span>'ndan
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
                                ${randevu.ogretmen_aciklama ? `<p class="text-gray-600 text-sm mt-2"><strong class="text-gray-800">Açıklamam:</strong> ${sanitizeOutput(randevu.ogretmen_aciklama)}</p>` : ''}
                                ${randevu.randevu_durumu === 'beklemede' ? `
                                <div class="mt-3 flex space-x-2">
                                    <button data-randevu-id="${randevu.randevu_id}" data-durum="onaylandi"
                                            class="durum-guncelle-btn bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        Onayla
                                    </button>
                                    <button data-randevu-id="${randevu.randevu_id}" data-durum="reddedildi"
                                            class="durum-guncelle-btn bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Reddet
                                    </button>
                                </div>` : ''}
                            `;
                            randevuTalepleriListesi.appendChild(randevuItem);
                        });
                        // Durum güncelleme butonlarına olay dinleyicileri ekle
                        document.querySelectorAll('.durum-guncelle-btn').forEach(button => {
                            button.addEventListener('click', function() {
                                showMessageModal(
                                    `Bu randevuyu ${this.dataset.durum === 'onaylandi' ? 'onaylamak' : 'reddetmek'} istediğinizden emin misiniz? Dilerseniz bir açıklama ekleyebilirsiniz.`,
                                    'confirm',
                                    this.dataset.randevuId,
                                    this.dataset.durum
                                );
                            });
                        });
                    } else {
                        randevuTalepleriListesi.innerHTML = '<p class="text-gray-600 text-center">Henüz bekleyen randevu talebi bulunmuyor.</p>';
                    }
                } catch (error) {
                    console.error('Randevu talepleri yüklenirken hata oluştu:', error);
                    randevuTalepleriListesi.innerHTML = '<p class="text-red-500 text-center">Randevu talepleri yüklenemedi. Bir hata oluştu.</p>';
                }
            }

            // --- Hesap Silme İşlemi ---
            hesabimiSilBtn.addEventListener('click', function() {
                showMessageModal('Hesabınızı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz ve tüm ilişkili verileriniz silinecektir.', 'delete_confirm');
            });

            function deleteAccount() {
                // hesap_sil.php zaten SESSION'dan alıyor, bu yüzden bir ID göndermeye gerek yok.
                // Güvenlik için POST metodu kullanılmalı, ancak mevcut kod GET kullanıyor.
                // Burayı POST'a çevirmenizi öneririm ve CSRF token kontrolü ekleyin.
                fetch(`hesap_sil.php`, { method: 'POST' }) // Methodu POST'a çevirdim.
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessageModal(data.message, 'info');
                            setTimeout(() => {
                                window.location.href = 'giris.php'; // Başarılıysa giriş sayfasına yönlendir
                            }, 2000);
                        } else {
                            showMessageModal(data.message, 'info');
                        }
                    })
                    .catch(error => {
                        console.error('Hesap silinirken ağ hatası:', error);
                        showMessageModal('Hesabınız silinirken bir ağ hatası oluştu.', 'info');
                    });
            }

            // Sayfa ilk açıldığında öğrenci listesini ve randevu taleplerini yükleriz.
            loadAllStudents();
            loadTeacherAppointments();
        });

        // Global sanitizeOutput fonksiyonu (PHP'den kopyalandı)
        function sanitizeOutput(text) {
            const element = document.createElement('div');
            element.innerText = text;
            return element.innerHTML;
        }
    </script>
</body>
</html>
