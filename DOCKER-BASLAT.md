# 🐳 Docker Nasıl Başlatılır?

## ❌ HATA: "unable to get image" veya "docker_engine: The system cannot find the file specified"

Bu hata **Docker Desktop'ın çalışmadığını** gösterir.

---

## ✅ ÇÖZÜM - Adım Adım:

### **1. Docker Desktop'ı Başlat**

**İki yöntemden biriyle:**

#### **Yöntem A: Masaüstünden**
- Masaüstündeki **Docker Desktop** ikonuna çift tıkla
- Sağ alttaki system tray'de (saat yanında) Docker balina ikonunu bekle
- İkon **yeşil** olunca hazır demektir

#### **Yöntem B: Başlat Menüsünden**
1. Windows tuşuna bas
2. "Docker Desktop" yaz
3. Enter'a bas
4. 30-60 saniye bekle (ilk açılış uzun sürebilir)

---

### **2. Docker'ın Hazır Olduğunu Kontrol Et**

System tray'deki (saat yanındaki) Docker ikonuna tıkla:
- ✅ **"Docker Desktop is running"** yazıyorsa → HAZIR
- ❌ **"Docker Desktop is starting..."** yazıyorsa → BEKLE

**Alternatif Kontrol (PowerShell):**
```powershell
docker version
```

Eğer versiyon bilgisi gelirse Docker çalışıyor demektir.

---

### **3. Projeyi Başlat**

Docker hazır olduktan sonra:

```powershell
cd C:\xampp\htdocs
docker compose up -d
```

**İlk çalıştırmada:**
- PHP ve Apache imajları indirilecek (2-5 dakika)
- Container'lar oluşturulacak
- Otomatik başlatılacak

---

### **4. Tarayıcıda Aç**

Başarılı olduysa:
```
http://localhost:8080
```

---

## 🔥 HIZLI KONTROL LİSTESİ:

- [ ] Docker Desktop yüklü mü?
- [ ] Docker Desktop açık mı? (System tray'de balina ikonu var mı?)
- [ ] İkon yeşil mi? (Çalışıyor mu?)
- [ ] `docker version` komutu çalışıyor mu?
- [ ] PowerShell'i **Admin olarak** açtın mı? (bazı durumlarda gerekir)

---

## ⚠️ SORUN ÇÖZME:

### **Sorun 1: Docker Desktop açılmıyor**
**Çözüm:** Bilgisayarı yeniden başlat

### **Sorun 2: "WSL 2 installation is incomplete"**
**Çözüm:**
```powershell
# PowerShell'i ADMIN olarak aç:
wsl --install
wsl --set-default-version 2
```
Sonra bilgisayarı yeniden başlat.

### **Sorun 3: "Access is denied" veya "elevated privileges" hatası**
**Çözüm:** PowerShell'i **Administrator olarak** aç:
- PowerShell ikonuna sağ tıkla
- "Run as Administrator" seç

### **Sorun 4: Docker çok yavaş**
**Çözüm:** Docker Desktop → Settings → Resources → Memory'yi 4GB'a çıkar

---

## 🚀 XAMPP İLE ÇALIŞTIRMAK İSTERSEN:

Docker'sız da çalışır! Zaten XAMPP kurulu:

1. **XAMPP Control Panel'i aç**
2. **Apache'yi başlat**
3. **Tarayıcıda aç:** `http://localhost/`

**O zaman Docker'a gerek yok!** 😊

---

## 📌 DOCKER vs XAMPP Karşılaştırması:

| Özellik | Docker | XAMPP |
|---------|---------|--------|
| **Kurulum** | Uzun (30-60 dk) | ✅ Zaten kurulu |
| **İlk Başlatma** | Yavaş (5 dk) | ✅ Hızlı (10 sn) |
| **RAM Kullanımı** | 2-4 GB | 200 MB |
| **Port** | 8080 | 80 |
| **Taşınabilirlik** | ✅ Her yerde çalışır | Sadece Windows |
| **Profesyonel** | ⭐⭐⭐ | ⭐⭐ |

---

## 💡 ÖNERİ:

**Hızlı test için:** XAMPP kullan  
**Production/Deployment için:** Docker kullan  
**İş başvurusu için:** "Docker biliyorum" de 😉

---

## ❓ HALA SORUN MU VAR?

### Kontrol Et:
```powershell
# Docker çalışıyor mu?
docker ps

# Docker Compose versiyonu
docker compose version

# Container'lar var mı?
docker compose ps
```

### Log'ları İncele:
```powershell
docker compose logs -f
```

### Tamamen Sıfırla:
```powershell
docker compose down -v
docker compose up -d --build
```

---

## 🎯 SONRAKİ ADIMLAR:

1. ✅ Docker Desktop'ı başlat
2. ✅ System tray'de yeşil ikonu bekle
3. ✅ `docker compose up -d` komutunu çalıştır
4. ✅ `http://localhost:8080` adresini aç
5. ✅ Tadını çıkar! 🎉

