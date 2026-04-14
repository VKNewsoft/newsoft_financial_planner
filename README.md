# **Newsoft Personal Cashflow — Simple Financial Tracking App**

Newsoft Personal Cashflow adalah aplikasi pencatatan keuangan pribadi yang dirancang untuk membantu pengguna mengelola arus kas harian secara sederhana, cepat, dan efisien.

Aplikasi ini memungkinkan pengguna untuk mencatat setiap pemasukan dan pengeluaran, serta menampilkan ringkasan kondisi keuangan melalui dashboard yang informatif. Dengan tampilan yang ringan dan responsif, aplikasi ini juga nyaman digunakan pada perangkat mobile.

Aplikasi ini merupakan hasil pengembangan dari sistem **Admin Panel (Web Sec)** yang berfungsi sebagai pusat kontrol manajemen user, role, dan akses sistem, sehingga memiliki fondasi keamanan dan struktur yang kuat.

> **Stack:**  
> - **Framework:** CodeIgniter 4.x  
> - **Database:** MySQL 8.x  

---

## **Quick Start**

1. **Clone/Download** project ini  
2. **Jalankan** XAMPP (Apache + MySQL)  
3. **Akses** aplikasi via browser:  http://localhost/newsoft/personal_cashflow
4. **Sistem otomatis mendeteksi** database belum ada → Redirect ke **Web Installer**  
5. **Isi form** konfigurasi database (default: localhost, root, no password)  
6. **Klik Install** → Tunggu proses selesai  
7. **Login** dengan kredensial default  
8. **Done!** 🎉  

> 💡 **Tidak perlu terminal/command line** - Semua bisa dilakukan via browser!

---

## **Fitur Utama**

### **1. Dashboard Informatif**

Menampilkan ringkasan arus kas harian secara real-time, sehingga pengguna dapat dengan cepat memahami kondisi keuangan mereka tanpa perlu analisis yang kompleks.

---

### **2. Manajemen Pemasukan & Pengeluaran**

Pengguna dapat mencatat:
- Pemasukan harian  
- Pengeluaran harian  
- Transfer antar cash account / wallet  

Dirancang dengan pendekatan sederhana agar mudah digunakan oleh siapa saja tanpa latar belakang akuntansi.

---

### **3. Cash Account / Wallet**

Setiap transaksi kini terhubung ke wallet sehingga pencatatan kas menjadi lebih jelas:
- CRUD wallet / cash account  
- Saldo awal per wallet  
- Summary saldo per wallet di dashboard  
- Transfer A ke B dicatat otomatis sebagai pengeluaran di wallet asal dan pemasukan di wallet tujuan  

---

### **4. Report Cash Flow**

Module report terpisah tersedia tanpa mengubah dashboard utama:
- Daftar transaksi dengan filter tanggal, wallet, kategori, tipe, dan deskripsi  
- Report transfer A ke B dengan filter wallet asal dan wallet tujuan  
- Summary per wallet yang tetap menghitung saldo akhir berdasarkan seluruh transaksi wallet  
- Desktop menggunakan DataTable server-side / lazy loading  
- Mobile menggunakan card/list view dengan load more  

---

### **5. User Management**

Mengelola akun pengguna menjadi lebih mudah:
- Tambah, edit, hapus user  
- Aktivasi / nonaktifkan user  
- Monitoring penggunaan sistem  

---

### **6. Role Access & Permissions**

Sistem hak akses yang fleksibel:
- Multi level role  
- Pengaturan izin (view, create, update, delete)  
- Mendukung multi company  

---

### **7. Modular System**

- Modul dapat diaktifkan/nonaktifkan  
- Struktur menu fleksibel  
- Mudah dikembangkan sesuai kebutuhan  

---

## **Keunggulan Sistem**

- Antarmuka sederhana dan mudah digunakan  
- Mobile-friendly (responsif di berbagai perangkat)  
- Mendukung multi wallet / cash account  
- Report transaksi dan transfer terpisah untuk data besar  
- Sistem keamanan berbasis role & permission  
- Struktur modular untuk pengembangan lanjutan  
- Cocok untuk kebutuhan personal maupun pengembangan sistem lebih besar  

---

## **Base System (Admin Panel)**

Aplikasi ini dikembangkan dari:

**Admin Panel — Built-in Management Control**

Yang berfungsi sebagai:
- Sistem Web Security (Web Sec)  
- Kontrol user, role, dan menu  
- Pengelolaan akses terpusat  

Mendukung:
- Multi role  
- Multi company  
- Struktur modular sebagai starter kit aplikasi  

---

## **Instalasi Database**

Database tersedia pada file: app/Database/newsoft_base.sql


### **Isi Database**

- Struktur tabel aplikasi  
- Data awal sistem  
- Konfigurasi dasar  

---

### **Instalasi Cepat**

**Metode 1: Web Installer (Recommended)** ⭐  
1. Akses aplikasi via browser  
2. Sistem otomatis redirect ke installer  
3. Isi konfigurasi database  
4. Klik "Install Database"  
5. Login ke sistem  

---

### **Kredensial Default**

- **Username:** `admin`  
- **Password:** `123456`  

---

## **Tujuan Pengembangan**

Aplikasi ini dibuat untuk:
- Membantu pencatatan keuangan pribadi secara sederhana  
- Memberikan insight terhadap pola pemasukan dan pengeluaran  
- Menjadi solusi ringan tanpa kompleksitas sistem akuntansi  

---

## **Pengembangan Selanjutnya**

- Grafik analitik keuangan  
- Export laporan (PDF / Excel)  
- Integrasi API  
- Notifikasi & reminder keuangan  
- Versi mobile app  

---

## **Lisensi**

Silakan disesuaikan dengan kebutuhan project Anda.
