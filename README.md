# 📚 Book Exchange Platform

A web application built with PHP and MySQL that allows university students to list, rent, and swap books with each other. The platform features a full approval workflow, real-time notifications, and an admin panel for system management.

---

## What is this?

Book Exchange is a peer-to-peer book sharing platform designed for university communities. Instead of buying expensive new textbooks every semester, students can:

- **Rent** books from other students for a specific date range and daily price
- **Swap** books — offer one of your books in exchange for someone else's
- **Discover** available books through a searchable, filterable marketplace
- **Manage** their listings and track incoming rental/swap requests

Admins can monitor all activity, handle user reports, and suspend accounts that violate platform rules.

---

## Features

### Student Features

| Feature | Description |
|---|---|
| Registration & Login | Secure account creation and login with hashed passwords |
| Book Discovery | Browse all available books with search, category, status, and year filters |
| Add / Edit / Delete Book | List your books with title, author, year, condition, category, price, and cover image |
| Rent a Book | Request to rent a book by selecting a start and end date |
| Swap a Book | Propose a swap by offering one of your own books in return |
| My Books | View your listings and see how many pending rent/swap requests each book has |
| Books Rented by Me | Track books you are currently renting from others |
| Notifications | Receive and respond to incoming rent/swap requests; view system messages |
| Profile Settings | Update your personal information and change your password |
| Report a Book | Flag inappropriate or incorrectly listed books for admin review |

### Admin Features

| Feature | Description |
|---|---|
| Admin Dashboard | Overview of total students, books, reports, and suspended accounts |
| User Management | View all registered users and their activity |
| Book Management | Browse and manage all book listings on the platform |
| Reports Panel | Review reported books and take action (ignore or remove) |
| User Suspension | Suspend users with a reason and end date; view suspension history |

---

## Tech Stack

- **PHP 8.2** — PDO with prepared statements for all database operations
- **MySQL 8.0** — Relational database
- **Pure CSS** — No external UI frameworks, fully custom styling
- **PHP Built-in Server** — For local development (no Apache required)

---

## Requirements

- **PHP 8.2+** (with `pdo_mysql` extension enabled)
- **MySQL 8.0+**

---

## Installation

### 1. Download the project

Download or clone the repository to your machine:

```
book-exchange/
├── database.sql          # Clean schema, no data
├── database_sample.sql   # Schema + sample data (recommended for testing)
└── src/                  # All PHP application files
```

### 2. Install PHP

Install PHP 8.2 using winget (Windows):

```powershell
winget install PHP.PHP.8.2
```

Restart your terminal after installation, then verify:

```powershell
php -v
```

#### Enable pdo_mysql extension

Find where PHP was installed:

```powershell
where.exe php
```

In that folder, copy `php.ini-development` to `php.ini`, then open `php.ini` and find the following line and remove the `;` at the beginning:

```ini
;extension=pdo_mysql
```

Change it to:

```ini
extension=pdo_mysql
```

Also find and uncomment the `extension_dir` line, pointing it to the `ext` folder inside your PHP installation directory:

```ini
extension_dir = "C:\path\to\your\php\ext"
```

### 3. Set up the database

1. Open **MySQL Workbench** and connect to your local MySQL instance
2. Create a new schema named `book_exchange`
3. Go to **Server → Data Import**
4. Select **Import from Self-Contained File** and choose `database_sample.sql`
5. Set **Default Target Schema** to `book_exchange`
6. Click **Start Import**

#### Apply required schema updates

Run these SQL statements in MySQL Workbench:

```sql
ALTER TABLE users ADD COLUMN is_suspended TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN suspension_end_date DATE NULL;
ALTER TABLE users ADD COLUMN suspension_reason TEXT NULL;
ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'info';
```

### 4. Configure environment

Inside the `src/` folder, create a file named `.env`:

```env
DB_HOST=localhost
DB_NAME=book_exchange
DB_USER=root
DB_PASS=your_mysql_password
BASE_URL=
```

Replace `your_mysql_password` with your actual MySQL root password.

### 5. Start the application

Open a terminal in the `src/` directory and run:

```powershell
php -S localhost:8000
```

Then open your browser and go to:

```
http://localhost:8000
```

---

## Default Test Accounts

After importing `database_sample.sql`, the following accounts are available:

| Role | Email | Password |
|---|---|---|
| Admin | user1@univ.edu | 123456 |
| Student | user2@univ.edu | 123456 |

> To make a user an admin, update their `role` column to `admin` in the `users` table via MySQL Workbench.

---

## Configuration Notes

- **Admin Access** — Register any user and set their `role` to `admin` in the database
- **Book Images** — Uploaded cover images are stored in `src/uploads/`. Make sure this folder exists and is writable
- **Email Domain** — To restrict registration to a specific university domain, edit the `is_valid_university_email()` function in `src/auth.php`

---

## Project Structure

```
book-exchange/
├── database.sql                    # Database schema (no data)
├── database_sample.sql             # Schema + sample test data
├── README.md
└── src/
    ├── .env                        # Environment variables (DB credentials)
    ├── config.php                  # DB connection, loads .env
    ├── auth.php                    # Authentication helpers
    ├── header.php                  # Shared navigation header
    ├── footer.php                  # Shared footer
    ├── style.css                   # All application styles
    ├── index.php                   # Book discovery / home page
    ├── login.php                   # Login page
    ├── register.php                # Registration page
    ├── logout.php                  # Session logout
    ├── add_book.php                # Add new book listing
    ├── edit_book.php               # Edit existing book
    ├── delete_book.php             # Delete book listing
    ├── book_detail.php             # Book detail view
    ├── my_books.php                # User's own listings + rented books
    ├── rent_confirm.php            # Rental date selection
    ├── rental_action.php           # Accept / decline rental
    ├── swap_request.php            # Initiate a swap
    ├── swap_action.php             # Accept / decline swap
    ├── notifications.php           # Notifications & pending requests
    ├── profile.php                 # Profile settings
    ├── admin_dashboard.php         # Admin overview
    ├── admin_books.php             # Admin book management
    ├── admin_users.php             # Admin user management
    ├── admin_actions.php           # Admin action handlers
    ├── admin_reports.php           # Reported books panel
    ├── admin_suspend.php           # User suspension panel
    ├── admin_suspend_history.php   # Suspension history
    ├── suspension_helpers.php      # Suspension utility functions
    ├── suspension_notice.php       # Suspension notice page
    └── uploads/                    # User-uploaded book cover images
```

---

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---
---

# 📚 Book Exchange Platformu

PHP ve MySQL ile geliştirilmiş, üniversite öğrencilerinin birbirleriyle kitap listelemesine, kiralamasına ve takas etmesine olanak tanıyan bir web uygulamasıdır. Platform; onay iş akışı, gerçek zamanlı bildirimler ve sistem yönetimi için bir admin paneli içermektedir.

---

## Bu Uygulama Ne İşe Yarar?

Book Exchange, üniversite topluluklarına yönelik tasarlanmış bir eşten eşe kitap paylaşım platformudur. Her dönem pahalı yeni ders kitabı satın almak yerine öğrenciler şunları yapabilir:

- Diğer öğrencilerden belirli bir tarih aralığı ve günlük ücret karşılığında kitap **kiralayabilir**
- Kendi kitaplarından birini teklif ederek başkasının kitabıyla **takas** yapabilir
- Arama, kategori ve durum filtrelerini kullanarak mevcut kitapları **keşfedebilir**
- Kendi ilanlarını **yönetebilir** ve gelen kira/takas taleplerini takip edebilir

Adminler tüm aktiviteyi izleyebilir, kullanıcı şikayetlerini değerlendirebilir ve kural ihlali yapan hesapları askıya alabilir.

---

## Özellikler

### Öğrenci Özellikleri

| Özellik | Açıklama |
|---|---|
| Kayıt & Giriş | Şifreli ve güvenli hesap oluşturma ve giriş |
| Kitap Keşfi | Arama, kategori, durum ve yıl filtresiyle tüm kitaplara göz at |
| Kitap Ekle / Düzenle / Sil | Başlık, yazar, yıl, durum, kategori, fiyat ve kapak görseli ile ilan ver |
| Kitap Kirala | Başlangıç ve bitiş tarihi seçerek kiralama talebi oluştur |
| Kitap Takası | Kendi kitaplarından birini teklif ederek takas öner |
| Kitaplarım | İlanlarını gör ve her kitap için kaç bekleyen kira/takas talebi olduğunu takip et |
| Kiraladıklarım | Başkalarından kiraladığın kitapları görüntüle |
| Bildirimler | Gelen kira/takas taleplerini al ve yanıtla; sistem mesajlarını görüntüle |
| Profil Ayarları | Kişisel bilgilerini güncelle ve şifreni değiştir |
| Kitap Şikayet Et | Uygunsuz veya hatalı listelenen kitapları admin incelemesi için işaretle |

### Admin Özellikleri

| Özellik | Açıklama |
|---|---|
| Admin Paneli | Toplam öğrenci, kitap, şikayet ve askıya alınan hesap özeti |
| Kullanıcı Yönetimi | Tüm kayıtlı kullanıcıları ve aktivitelerini görüntüle |
| Kitap Yönetimi | Platformdaki tüm kitap ilanlarını gözden geçir ve yönet |
| Şikayet Paneli | Şikayet edilen kitapları incele ve aksiyon al (yoksay veya kaldır) |
| Kullanıcı Askıya Alma | Gerekçe ve bitiş tarihi ile kullanıcıyı askıya al; askıya alma geçmişini görüntüle |

---

## Teknoloji Yığını

- **PHP 8.2** — Tüm veritabanı işlemleri için PDO ve hazırlanmış sorgular
- **MySQL 8.0** — İlişkisel veritabanı
- **Saf CSS** — Harici UI kütüphanesi kullanılmamıştır, tamamen özel tasarım
- **PHP Built-in Server** — Yerel geliştirme için (Apache gerekmez)

---

## Gereksinimler

- **PHP 8.2+** (`pdo_mysql` eklentisi etkin olmalı)
- **MySQL 8.0+**

---

## Kurulum

### 1. Projeyi İndir

Repoyu bilgisayarına indirin veya klonlayın:

```
book-exchange/
├── database.sql          # Yalnızca şema, veri yok
├── database_sample.sql   # Şema + örnek veriler (test için önerilir)
└── src/                  # Tüm PHP uygulama dosyaları
```

### 2. PHP Kur

PHP 8.2'yi winget ile kur (Windows):

```powershell
winget install PHP.PHP.8.2
```

Kurulumdan sonra terminali yeniden başlat ve doğrula:

```powershell
php -v
```

#### pdo_mysql Eklentisini Etkinleştir

PHP'nin kurulu olduğu klasörü bul:

```powershell
where.exe php
```

O klasörde `php.ini-development` dosyasını `php.ini` olarak kopyala, ardından `php.ini` dosyasını aç ve şu satırın başındaki `;` işaretini kaldır:

```ini
;extension=pdo_mysql
```

Şu hale getir:

```ini
extension=pdo_mysql
```

Ayrıca `extension_dir` satırını da yorum olmaktan çıkar ve PHP kurulum klasörünün içindeki `ext` klasörüne yönlendir:

```ini
extension_dir = "C:\php-kurulum-yolu\ext"
```

### 3. Veritabanını Kur

1. **MySQL Workbench**'i aç ve yerel MySQL bağlantına bağlan
2. `book_exchange` adında yeni bir şema oluştur
3. **Server → Data Import** menüsüne git
4. **Import from Self-Contained File** seçeneğini seç ve `database_sample.sql` dosyasını göster
5. **Default Target Schema** olarak `book_exchange` seç
6. **Start Import** butonuna bas

#### Gerekli Şema Güncellemelerini Uygula

MySQL Workbench'te şu SQL komutlarını çalıştır:

```sql
ALTER TABLE users ADD COLUMN is_suspended TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN suspension_end_date DATE NULL;
ALTER TABLE users ADD COLUMN suspension_reason TEXT NULL;
ALTER TABLE notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'info';
```

### 4. Ortam Değişkenlerini Yapılandır

`src/` klasörünün içine `.env` adında bir dosya oluştur:

```env
DB_HOST=localhost
DB_NAME=book_exchange
DB_USER=root
DB_PASS=mysql_sifren
BASE_URL=
```

`mysql_sifren` kısmını kendi MySQL şifrenle değiştir.

### 5. Uygulamayı Başlat

`src/` klasöründe bir terminal aç ve şunu çalıştır:

```powershell
php -S localhost:8000
```

Ardından tarayıcını aç ve şu adrese git:

```
http://localhost:8000
```

---

## Varsayılan Test Hesapları

`database_sample.sql` içe aktarıldıktan sonra şu hesaplar kullanılabilir:

| Rol | E-posta | Şifre |
|---|---|---|
| Admin | user1@univ.edu | 123456 |
| Öğrenci | user2@univ.edu | 123456 |

> Bir kullanıcıyı admin yapmak için MySQL Workbench üzerinden `users` tablosundaki `role` sütununu `admin` olarak güncelle.

---

## Yapılandırma Notları

- **Admin Erişimi** — Herhangi bir kullanıcı kaydettikten sonra veritabanında `role` değerini `admin` yap
- **Kitap Görselleri** — Yüklenen kapak görselleri `src/uploads/` klasöründe saklanır. Bu klasörün mevcut ve yazılabilir olduğundan emin ol
- **E-posta Domain Kısıtlaması** — Kaydı belirli bir üniversite domainiye kısıtlamak için `src/auth.php` içindeki `is_valid_university_email()` fonksiyonunu düzenle

---

## Proje Yapısı

```
book-exchange/
├── database.sql                    # Veritabanı şeması (veri yok)
├── database_sample.sql             # Şema + örnek test verileri
├── README.md
└── src/
    ├── .env                        # Ortam değişkenleri (DB bilgileri)
    ├── config.php                  # Veritabanı bağlantısı, .env'yi yükler
    ├── auth.php                    # Kimlik doğrulama yardımcıları
    ├── header.php                  # Ortak navigasyon başlığı
    ├── footer.php                  # Ortak alt bilgi
    ├── style.css                   # Tüm uygulama stilleri
    ├── index.php                   # Kitap keşfi / ana sayfa
    ├── login.php                   # Giriş sayfası
    ├── register.php                # Kayıt sayfası
    ├── logout.php                  # Oturum kapatma
    ├── add_book.php                # Yeni kitap ilanı ekle
    ├── edit_book.php               # Mevcut kitabı düzenle
    ├── delete_book.php             # Kitap ilanını sil
    ├── book_detail.php             # Kitap detay sayfası
    ├── my_books.php                # Kullanıcının ilanları + kiraladıkları
    ├── rent_confirm.php            # Kiralama tarih seçimi
    ├── rental_action.php           # Kiralama kabul / red
    ├── swap_request.php            # Takas başlat
    ├── swap_action.php             # Takas kabul / red
    ├── notifications.php           # Bildirimler ve bekleyen talepler
    ├── profile.php                 # Profil ayarları
    ├── admin_dashboard.php         # Admin genel görünümü
    ├── admin_books.php             # Admin kitap yönetimi
    ├── admin_users.php             # Admin kullanıcı yönetimi
    ├── admin_actions.php           # Admin aksiyon işleyicileri
    ├── admin_reports.php           # Şikayet edilen kitaplar paneli
    ├── admin_suspend.php           # Kullanıcı askıya alma paneli
    ├── admin_suspend_history.php   # Askıya alma geçmişi
    ├── suspension_helpers.php      # Askıya alma yardımcı fonksiyonları
    ├── suspension_notice.php       # Askıya alma bildirim sayfası
    └── uploads/                    # Kullanıcıların yüklediği kitap kapak görselleri
```

---

## Lisans

Bu proje MIT Lisansı ile lisanslanmıştır — detaylar için [LICENSE](LICENSE) dosyasına bakın.

---
---

## Screenshots / Ekran Görüntüleri

### Login Page / Giriş Sayfası
<img width="1917" height="910" alt="Login_Page" src="https://github.com/user-attachments/assets/99621323-f051-4ec3-903f-6a3d4d0bebc6" />


### Main Page / Ana Sayfa
<img width="1896" height="906" alt="Main_Page" src="https://github.com/user-attachments/assets/5a8f27bf-0ec2-4fc7-b062-31dbccc2999b" />


### Book Detail / Kitap Detayı
<img width="1900" height="906" alt="Book_Detail" src="https://github.com/user-attachments/assets/77e0b6a1-ddca-46ef-8cde-9bfc6f3e4b42" />


### Add Book / Kitap Ekle
<img width="1900" height="907" alt="Add_Book" src="https://github.com/user-attachments/assets/8747e27f-a24a-4e76-9cfd-2717e5ab0934" />


### My Book Listings / Kitap İlanlarım
<img width="1917" height="906" alt="My_Book_Listing" src="https://github.com/user-attachments/assets/77a41d6f-09a7-43e9-8b18-b65a4f7467c9" />


### Books Rented by Me / Kiraladığım Kitaplar
<img width="1917" height="902" alt="Books_Rented_by_Me" src="https://github.com/user-attachments/assets/5d194feb-1c06-499e-82bd-870553a4b929" />


### Notifications / Bildirimler
<img width="1897" height="900" alt="Notifications" src="https://github.com/user-attachments/assets/5fd82b01-e525-4004-bb11-640a5f8f0f4f" />


### Profile Settings / Profil Ayarları
<img width="1896" height="906" alt="Profile1" src="https://github.com/user-attachments/assets/2ee8ec8e-e681-4fb3-8810-d3e3dc4ce7bb" />
<img width="1897" height="906" alt="Profile2" src="https://github.com/user-attachments/assets/c9b178d3-9816-4837-987a-cc60a1a0072d" />


### Admin Dashboard / Admin Paneli
<img width="1897" height="906" alt="Admin_Dashboard" src="https://github.com/user-attachments/assets/c1754efc-f85d-4bd1-bcca-1286b8be5354" />


### User Suspension / Kullanıcı Askıya Alma
<img width="1892" height="900" alt="User_Suspension" src="https://github.com/user-attachments/assets/b8cf7912-79bb-4e22-a7d9-76a5968e35c8" />


### Reported Items / Şikayet Edilen Kitaplar
<img width="1895" height="897" alt="Reported_Items" src="https://github.com/user-attachments/assets/f37e14b1-2c59-4387-8815-2e2b600f63ce" />

