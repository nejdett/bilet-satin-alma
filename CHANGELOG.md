# Changelog

Tüm önemli değişiklikler bu dosyada belgelenmiştir.

## [1.2.0] - 2024-10-23

### Added
- Docker desteği eklendi
- Kullanıcı bakiye yönetim sistemi (admin için)
- Firma bazlı kupon sistemi

### Fixed
- Koltuk seçiminde race condition sorunu düzeltildi
- PDF oluşturma hatası giderildi
- Navbar dropdown responsive problemi çözüldü

### Changed
- Database bağlantı havuzu optimize edildi
- Session timeout süresi 30 dakikaya çıkarıldı

## [1.1.0] - 2024-10-19

### Added
- Kupon sistemi implementasyonu
- Firma paneli eklendi
- Admin paneli yenilendi

### Fixed
- Bilet iptal işleminde bakiye iadesi hatası düzeltildi
- Sefer arama türkçe karakter sorunu giderildi

## [1.0.0] - 2024-10-15

### Added
- İlk stabil sürüm
- Kullanıcı kayıt ve giriş
- Sefer arama ve listeleme
- Bilet satın alma
- Koltuk seçimi
- PDF bilet indirme
- Admin paneli (basit)
- Responsive tasarım

### Security
- Password hashing (Argon2ID)
- CSRF protection
- SQL injection koruması
- XSS koruması

## [0.5.0] - 2025-10-5

### Added
- Veritabanı şeması oluşturuldu
- Temel class yapısı kuruldu
- Login/register sistemitemel işlevleri

### Changed
- SQLite3 kullanımına geçildi (MySQL yerine)

