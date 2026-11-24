# DEPLOYMENT GUIDE — TCET SPECIALIZATION TRACKER
**Application:** TCET Specialization Tracker (TCET_ST)  
**Target Environment:** Windows XAMPP / On-Premises Apache Server  
**Authoritative Database:** `tcet_st` (MariaDB 10.4.32)  
**Version:** 3.2.0 Production Release  

---

## 1. System Requirements

### Hardware Prerequisites
- **Processor:** 4 cores (2.4 GHz or higher)
- **RAM:** 8 GB minimum (16 GB recommended for concurrent exam/enrollment cycles)
- **Disk Space:** 50 GB available SSD storage

### Software Prerequisites
- **Operating System:** Windows 10/11 Enterprise or Windows Server 2019/2022
- **XAMPP Environment:** XAMPP 8.2 or 8.3 (Apache 2.4.58+, MariaDB 10.4.32+)
- **PHP Extensions Required:** `pdo`, `pdo_mysql`, `mysqli`, `mbstring`, `json`, `curl`, `openssl`, `gd`, `zip`

---

## 2. Production Installation & Configuration

### Step 1: Deploy Application Files
Extract or copy the codebase to the Apache webroot:
```cmd
xcopy /E /I /H /Y "C:\xampp\htdocs\st" "C:\xampp\htdocs\st"
```

### Step 2: Configure Apache VirtualHost
In `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, configure the production host:
```apache
<VirtualHost *:80>
    ServerAdmin webmaster@tcetmumbai.in
    DocumentRoot "C:/xampp/htdocs/st"
    ServerName tcet-st.local
    ErrorLog "logs/tcet_st_error.log"
    CustomLog "logs/tcet_st_access.log" combined

    <Directory "C:/xampp/htdocs/st">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Step 3: Configure PHP Runtime Settings
In `C:\xampp\php\php.ini`:
```ini
memory_limit = 512M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 300
date.timezone = "Asia/Kolkata"
display_errors = Off
log_errors = On
error_log = "C:/xampp/php/logs/php_error.log"
```

### Step 4: Database Connection Setup
Verify `C:\xampp\htdocs\st\database\db_connect.php`:
```php
<?php
// Production Database Connection Configuration
$host = "localhost";
$user = "root";
$pass = ""; // Set production password
$db   = "tcet_st";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Database connection failure: " . $conn->connect_error);
    die("Database service temporarily unavailable.");
}
$conn->set_charset("utf8mb4");
```

---

## 3. Database Restoration & Immutability Verification

To restore the authoritative production baseline:
```cmd
mysql -u root -p tcet_st < C:\xampp\htdocs\st\database\backup\tcet_st_final_backup_20261123_001718.sql
```

Verify trigger defenses:
```cmd
php scratch/verify_db_integrity_post_redesign.php
```

---

## 4. Disaster Recovery & Backup Runbook

1. **Automated Daily Backups:**
   Schedule a daily Windows Task Scheduler job running `mysqldump`:
   ```cmd
   mysqldump -u root -p --single-transaction --routines --triggers tcet_st > C:\xampp\htdocs\st\database\backup\tcet_st_daily_%date:~-4,4%%date:~-10,2%%date:~-7,2%.sql
   ```
2. **Cold Storage:** Copy backup SQL dumps to an offsite encrypted network share.
