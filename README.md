# Otobüs Bileti Satış Platformu

Modern, güvenli ve hızlı otobüs bileti satış ve rezervasyon sistemi.

## Özellikler

- 🎫 Bilet satın alma ve rezervasyon
- 💺 Koltuk seçimi
- 🎟️ Kupon sistemi
- 👥 Kullanıcı, Firma ve Admin panelleri
- 💰 Bakiye yönetimi
- 📱 Responsive tasarım
- 🔒 Güvenli oturum yönetimi

## Teknolojiler

- **Backend:** PHP 8.2
- **Veritabanı:** SQLite
- **Frontend:** Bootstrap 5, Font Awesome
- **Container:** Docker

## Kurulum

### Docker ile (Önerilen)

```bash
docker-compose up -d
```

Uygulama: `http://localhost:8080`

### Manuel Kurulum

```bash
chmod 666 bilet-satis-veritabani.db
chmod 777 temp/
```

Apache/Nginx ile `/var/www/html` dizinine kopyalayın.

## Docker Komutları

```bash
docker-compose up -d          # Başlat
docker-compose down           # Durdur
docker-compose logs -f        # Loglar
docker-compose restart        # Yeniden başlat
docker exec -it bilet-satis-app bash   # Container'a gir
```

## Kullanıcı Rolleri

- **Visitor:** Sefer arama
- **User:** Bilet satın alma, kupon kullanma
- **Company Admin:** Sefer ve kupon yönetimi
- **Admin:** Tüm sistem yönetimi

## Proje Yapısı

```
.
├── classes/          # PHP sınıfları
├── config/           # Yapılandırma
├── includes/         # Yardımcı dosyalar
├── pages/            # Sayfalar (admin, company)
├── assets/           # CSS, resimler
├── Dockerfile
└── docker-compose.yml
```

## Bilinen Sorunlar

- [ ] Koltuk seçiminde çift tıklamada bazen yavaşlık oluyor
- [ ] Mobil cihazlarda navbar dropdown bazen geç açılıyor
- [ ] IE11 desteği yok (modern tarayıcılar gerekli)
- [ ] Çok fazla eşzamanlı rezervasyonda race condition olabiliyor
- [ ] PDF indirme bazen timeout veriyor (büyük biletlerde)

## Yapılacaklar

- [ ] Email bildirimleri eklenecek (bilet onayı, iptal)
- [ ] SMS doğrulama sistemi
- [ ] Ödeme gateway entegrasyonu (Stripe/PayPal)
- [ ] Admin dashboard'a analytics eklenecek
- [ ] Unit testler yazılacak
- [ ] API documentation
- [ ] Redis cache entegrasyonu
- [ ] Multi-language support
- [ ] Seat map görseli (şu an sadece numara)
- [ ] Otomatik yedekleme sistemi


Kullanıcı giriş bilgileri :

company1@company.com - company1234
company2@company.com - company1234
company3@company.com - company1234
user@user.com - user1234
admin@admin.com - Admin1234 veya admin1234