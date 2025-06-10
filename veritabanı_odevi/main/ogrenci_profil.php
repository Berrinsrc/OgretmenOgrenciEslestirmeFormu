<?php
// ogrenci_profil.php - Öğrenci Profil Sayfası
// Bu sayfa, öğrencinin profil bilgilerini ve randevularını gösterir.
// Ayrıca profil bilgilerini güncelleme ve hesap silme işlevselliği sunar.

require_once 'ayarlar.php'; // Veritabanı bağlantısı ve oturum için
require_once 'ogrenci_islemleri.php'; // ogrenciDetayGetir fonksiyonu için bu dosya dahil edildi

// Oturum kontrolü: Öğrenci giriş yapmış mı?
if (!isset($_SESSION['giris_yapti']) || $_SESSION['rol'] !== 'ogrenci') {
    header("Location: giris.php?hata=yetkisiz");
    exit();
}

$ogrenci_id = $_SESSION['ogrenci_id'] ?? 0;
$kullanici_id = $_SESSION['kullanici_id'] ?? 0; // Kullanici_id, hesap silme için kullanılacak

// ogrenci_islemleri.php'deki fonksiyonu kullanarak öğrenci detaylarını çek
$ogrenci = ogrenciDetayGetir($ogrenci_id);

if (!$ogrenci) {
    // Öğrenci bilgileri bulunamazsa hata sayfasına veya ana sayfaya yönlendir
    header("Location: ogrenci_ana_sayfa.php?hata=profil_yok");
    exit();
}

// Randevularını çek
$randevular = [];
try {
    $stmt_randevu = $pdo->prepare("SELECT r.randevu_id, r.randevu_tarihi, r.randevu_baslangic_saati, r.randevu_bitis_saati, r.randevu_durumu, r.ogretmen_aciklama,
                                  o.adi AS ogretmen_adi, o.soyadi AS ogretmen_soyadi,
                                  d.ders_adi
                           FROM randevular r
                           JOIN ogretmenler o ON r.ogretmen_id = o.ogretmen_id
                           JOIN dersler d ON r.ders_id = d.ders_id
                           WHERE r.ogrenci_id = :ogrenci_id
                           ORDER BY r.randevu_tarihi DESC, r.randevu_baslangic_saati DESC");
    $stmt_randevu->bindParam(':ogrenci_id', $ogrenci_id, PDO::PARAM_INT);
    $stmt_randevu->execute();
    $randevular = $stmt_randevu->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Öğrenci randevuları çekilirken hata oluştu: " . $e->getMessage());
    // Hata mesajı gösterilebilir veya boş randevu listesi ile devam edilebilir.
}

// Mesajları göster (URL parametrelerinden)
$mesaj = '';
$mesaj_tipi = ''; // 'success' veya 'error'

if (isset($_GET['durum'])) {
    if ($_GET['durum'] == 'guncelleme_basarili') {
        $mesaj = 'Profil bilgileriniz başarıyla güncellendi.';
        $mesaj_tipi = 'success';
    }
} elseif (isset($_GET['hata'])) {
    switch ($_GET['hata']) {
        case 'bos_alanlar':
            $mesaj = 'Lütfen tüm gerekli alanları doldurun.';
            break;
        case 'gecersiz_eposta':
            $mesaj = 'Lütfen geçerli bir e-posta adresi girin.';
            break;
        case 'sifreler_eslesmiyor':
            $mesaj = 'Yeni şifreler uyuşmuyor.';
            break;
        case 'sifre_kisa':
            $mesaj = 'Şifreniz en az 6 karakter olmalıdır.';
            break;
        case 'guncelleme_basarisiz':
            $mesaj = 'Profil bilgileri güncellenirken bir hata oluştu.';
            break;
        case 'csrf_gecersiz':
            $mesaj = 'Güvenlik hatası: Geçersiz istek. Lütfen tekrar deneyin.';
            break;
        default:
            $mesaj = 'Bir hata oluştu.';
    }
    $mesaj_tipi = 'error';
}

// CSRF token oluştur
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Profilim - Öğrenci Mentor Sistemi</title>
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
            <h1 class="text-2xl font-bold">Öğrenci Paneli</h1>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="ogrenci_ana_sayfa.php" class="hover:underline">Ana Sayfa</a></li>
                    <li><a href="ogrenci_profil.php" class="hover:underline font-bold">Profilim</a></li>
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

        <div class="bg-white p-8 rounded-lg shadow-xl mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">Profil Bilgilerim</h2>
            
            <form action="kontrol.php?islem=profil_guncelle" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo sanitizeOutput($csrf_token); ?>">
                <div>
                    <label for="ad" class="block text-sm font-medium text-gray-700">Adınız</label>
                    <input type="text" id="ad" name="ad" value="<?php echo sanitizeOutput($ogrenci['adi'] ?? ''); ?>" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="soyad" class="block text-sm font-medium text-gray-700">Soyadınız</label>
                    <input type="text" id="soyad" name="soyad" value="<?php echo sanitizeOutput($ogrenci['soyadi'] ?? ''); ?>" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="eposta" class="block text-sm font-medium text-gray-700">E-posta Adresi</label>
                    <input type="email" id="eposta" name="eposta" value="<?php echo sanitizeOutput($ogrenci['e_posta'] ?? ''); ?>" required 
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="ilgi_alanlari" class="block text-sm font-medium text-gray-700">İlgi Alanlarınız</label>
                    <textarea id="ilgi_alanlari" name="ilgi_alanlari" rows="3"
                           class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="Örn: Tarih, Edebiyat, Yazılım"><?php echo sanitizeOutput($ogrenci['ilgi_alanlari'] ?? ''); ?></textarea>
                </div>
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h3 class="text-xl font-semibold text-gray-700 mb-4">Şifre Değişikliği (İsteğe Bağlı)</h3>
                    <div>
                        <label for="yeni_sifre" class="block text-sm font-medium text-gray-700">Yeni Şifre</label>
                        <input type="password" id="yeni_sifre" name="yeni_sifre" 
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Değiştirmek istemiyorsanız boş bırakın">
                    </div>
                    <div class="mt-4">
                        <label for="sifre_tekrar" class="block text-sm font-medium text-gray-700">Yeni Şifre (Tekrar)</label>
                        <input type="password" id="sifre_tekrar" name="sifre_tekrar" 
                               class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Yeni şifreyi tekrar girin">
                    </div>
                </div>

                <button type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-semibold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mt-6">
                    Bilgileri Güncelle
                </button>
            </form>
        </div>

        <div class="bg-white p-8 rounded-lg shadow-xl mb-8 randevular-bolumu">
            <h3 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-4">Randevularım</h3>
            <ul class="space-y-4">
                <?php if (!empty($randevular)): ?>
                    <?php foreach ($randevular as $randevu): ?>
                        <li class="bg-gray-50 p-4 rounded-md shadow-sm border border-gray-200">
                            <p class="text-lg font-semibold text-gray-900">
                                <span class="text-blue-700"><?php echo sanitizeOutput($randevu['ders_adi']); ?></span> dersi için
                                <span class="text-purple-700"><?php echo sanitizeOutput($randevu['ogretmen_adi'] . ' ' . $randevu['ogretmen_soyadi']); ?></span> ile
                            </p>
                            <p class="text-gray-700 mt-1">
                                <strong class="text-gray-800">Tarih:</strong> <?php echo date('d.m.Y', strtotime($randevu['randevu_tarihi'])); ?> |
                                <strong class="text-gray-800">Saat:</strong> <?php echo date('H:i', strtotime($randevu['randevu_baslangic_saati'])); ?> - <?php echo date('H:i', strtotime($randevu['randevu_bitis_saati'])); ?>
                            </p>
                            <p class="text-gray-700 mt-1">
                                <strong class="text-gray-800">Durum:</strong> 
                                <span class="<?php 
                                    if ($randevu['randevu_durumu'] === 'onaylandi') echo 'text-green-600 font-medium';
                                    elseif ($randevu['randevu_durumu'] === 'beklemede') echo 'text-yellow-600 font-medium';
                                    elseif ($randevu['randevu_durumu'] === 'reddedildi' || $randevu['randevu_durumu'] === 'iptal_edildi') echo 'text-red-600 font-medium';
                                ?>">
                                <?php echo sanitizeOutput(ucfirst($randevu['randevu_durumu'])); ?>
                                </span>
                            </p>
                            <?php if (!empty($randevu['ogretmen_aciklama'])): ?>
                                <p class="text-gray-600 text-sm mt-2">
                                    <strong class="text-gray-800">Öğretmen Açıklaması:</strong> <?php echo sanitizeOutput($randevu['ogretmen_aciklama']); ?>
                                </p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="p-4 bg-gray-50 rounded-md text-gray-600 text-center">Henüz bir randevunuz bulunmuyor.</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Hesap Silme Bölümü -->
        <div class="bg-red-100 border border-red-400 text-red-700 p-8 rounded-lg shadow-xl">
            <h3 class="text-3xl font-bold mb-4">Hesabınızı Silin</h3>
            <p class="mb-4">Hesabınızı sildiğinizde, tüm profil bilgileriniz ve randevu kayıtlarınız kalıcı olarak silinecektir. Bu işlem geri alınamaz.</p>
            <button id="hesabimiSilBtn" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-md shadow-md transition duration-300 ease-in-out">
                Hesabımı Sil
            </button>
        </div>
    </main>

    <?php include_once 'footer.php'; ?>

    <div id="messageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm w-full text-center">
            <p id="modalMessage" class="text-lg mb-4"></p>
            <button id="modalConfirmBtn" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md mr-2 hidden">Evet, Sil</button>
            <button id="modalCloseBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md">İptal</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageModal = document.getElementById('messageModal');
            const modalMessage = document.getElementById('modalMessage');
            const modalConfirmBtn = document.getElementById('modalConfirmBtn');
            const modalCloseBtn = document.getElementById('modalCloseBtn');
            const hesabimiSilBtn = document.getElementById('hesabimiSilBtn');

            function showMessageModal(message, isConfirm = false) {
                modalMessage.textContent = message;
                if (isConfirm) {
                    modalConfirmBtn.classList.remove('hidden');
                    modalCloseBtn.textContent = 'İptal';
                } else {
                    modalConfirmBtn.classList.add('hidden');
                    modalCloseBtn.textContent = 'Tamam';
                }
                messageModal.classList.remove('hidden');
            }

            modalCloseBtn.addEventListener('click', function() {
                messageModal.classList.add('hidden');
            });

            hesabimiSilBtn.addEventListener('click', function() {
                showMessageModal('Hesabınızı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.', true);
            });

            modalConfirmBtn.addEventListener('click', async function() {
                // Hesap silme işlemi
                try {
                    // Kullanıcı ID'si PHP oturumundan zaten alınacak, JS'e doğrudan göndermeye gerek yok.
                    // hesap_sil.php zaten SESSION'dan alıyor.
                    const response = await fetch('hesap_sil.php', { method: 'POST' }); // Güvenlik için POST kullanmak daha iyidir
                    const data = await response.json();

                    if (data.success) {
                        showMessageModal(data.message);
                        setTimeout(() => {
                            window.location.href = 'giris.php'; // Başarılıysa giriş sayfasına yönlendir
                        }, 2000); // 2 saniye sonra yönlendir
                    } else {
                        showMessageModal(data.message);
                    }
                } catch (error) {
                    console.error('Hesap silinirken ağ hatası:', error);
                    showMessageModal('Hesabınız silinirken bir ağ hatası oluştu.');
                }
            });

            // PHP'den gelen mesajları kontrol et ve modalı göster
            <?php if (!empty($mesaj)): ?>
                showMessageModal("<?php echo sanitizeOutput($mesaj); ?>");
            <?php endif; ?>

            // Global sanitizeOutput fonksiyonu (PHP'den kopyalandı, JS için uyarlandı)
            function sanitizeOutput(text) {
                const element = document.createElement('div');
                element.innerText = text; // Metni güvenli bir şekilde ata
                return element.innerHTML; // HTML olarak geri döndür
            }
        });
    </script>
</body>
</html>
