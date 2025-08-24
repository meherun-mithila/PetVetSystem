# PetVet Database Setup Guide

## 📋 Overview

This document provides instructions for setting up the complete PetVet veterinary clinic management system database.

## 🗄️ Database File

- **Main Database File:** `petvet_database.sql`
- **Contains:** All tables, sample data, indexes, views, and triggers
- **Size:** Complete database structure with all features

## 🚀 Quick Setup

### Method 1: Using phpMyAdmin

1. **Open phpMyAdmin** in your browser
2. **Create a new database** named `petvet` (or your preferred name)
3. **Import the SQL file:**
   - Click on the database you created
   - Go to "Import" tab
   - Click "Choose File" and select `petvet_database.sql`
   - Click "Go" to import

### Method 2: Using MySQL Command Line

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE petvet CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Import the SQL file
mysql -u root -p petvet < petvet_database.sql
```

### Method 3: Using XAMPP

1. **Start XAMPP** and ensure MySQL is running
2. **Open phpMyAdmin** at `http://localhost/phpmyadmin`
3. **Create database** named `petvet`
4. **Import** the `petvet_database.sql` file

## 📊 Database Structure

### Core Tables

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `users` | Pet owners | Email verification, user types |
| `admin` | System administrators | Role-based access |
| `staff` | Clinic staff | Role management |
| `doctors` | Veterinary doctors | Specialization tracking |

### Patient Management

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `patients` | Pet information | Owner relationships, medical history |
| `appointments` | Scheduling | Date/time, status tracking |
| `services` | Available services | Pricing, descriptions |

### Medical Records

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `medicalrecords` | Treatment history | Diagnosis, prescriptions |
| `vaccinationrecords` | Vaccine tracking | Due dates, schedules |

### Content Management

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `articles` | Educational content | SEO-friendly, comments |
| `article_comments` | User engagement | Moderation system |

### Communication

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `notifications` | System alerts | Audience targeting |
| `notification_reads` | Read tracking | User engagement |

### Adoption System

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `adoptionlistings` | Pet listings | Approval workflow |
| `adoptionrequests` | Adoption applications | Status tracking |

### Email System

| Table | Purpose | Key Features |
|-------|---------|--------------|
| `email_otps` | Verification codes | Expiration, security |
| `email_verifications` | Legacy support | Token-based verification |

## 🔧 Features Included

### ✅ User Management
- User registration with email verification
- Password hashing for security
- User type differentiation (user/admin)
- Account deletion with cascade cleanup

### ✅ Appointment System
- Flexible scheduling
- Status tracking (scheduled, completed, cancelled, no-show)
- Doctor and patient relationships
- Service linking

### ✅ Medical Records
- Comprehensive patient history
- Treatment tracking
- Prescription management
- Vaccine scheduling

### ✅ Content Management
- Article publishing system
- Comment functionality
- SEO optimization
- View tracking

### ✅ Notification System
- Multi-audience targeting
- Read status tracking
- Type categorization
- Priority management

### ✅ Adoption System
- Pet listing management
- Application workflow
- Approval process
- Status tracking

### ✅ Email Verification
- OTP-based verification
- Expiration handling
- Security measures
- Legacy support

## 📈 Performance Optimizations

### Indexes
- **Primary Keys:** All tables have proper primary keys
- **Foreign Keys:** Proper relationships with cascade options
- **Search Indexes:** Full-text search on articles
- **Performance Indexes:** Date, status, and type indexes

### Views
- **appointment_details:** Complete appointment information
- **patient_stats:** Patient statistics and history

### Triggers
- **User ID Management:** Prevents ID reuse
- **Article Updates:** Automatic timestamp updates

## 🔐 Security Features

### Password Security
- All passwords are hashed using bcrypt
- Default password for sample users: `password`
- **Important:** Change default passwords in production

### Data Integrity
- Foreign key constraints
- Cascade delete options
- Unique constraints on emails
- Proper data types and lengths

### Email Verification
- OTP expiration (10 minutes)
- Attempt tracking
- Secure token generation

## 📝 Sample Data

### Users
- **10 sample users** with verified accounts
- **Emails:** user1@email.com to user10@email.com
- **Password:** `password` (hashed)

### Admin Users
- **5 admin accounts** with different roles
- **Emails:** admin@petvet.com, bilal.hossain@gmail.com, etc.
- **Password:** `password` (hashed)

### Staff & Doctors
- **3 staff members** with different roles
- **3 doctors** with specializations
- **Password:** `password` (hashed)

### Services
- **10 veterinary services** with pricing
- **Range:** $500 - $5,000
- **Types:** Checkups, surgeries, emergency care

### Articles
- **2 sample articles** about pet care
- **Topics:** Pet care tips, vaccination schedules
- **Status:** Published and ready

## ⚙️ Configuration

### Database Connection
Update `config.php` with your database credentials:

```php
<?php
$host = 'localhost';
$dbname = 'petvet';
$username = 'your_username';
$password = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

### Email Configuration
Update `auth_system/mailer_config.php` with your SMTP settings:

```php
<?php
// Google SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', '465');
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
define('SMTP_FROM_EMAIL', 'your_email@gmail.com');
define('SMTP_FROM_NAME', 'PetVet - Caring Paws Veterinary Clinic');
define('SMTP_SECURE', 'ssl');
?>
```

## 🚨 Important Notes

### Production Setup
1. **Change all default passwords** immediately
2. **Update email configuration** with real SMTP credentials
3. **Secure database credentials** in config files
4. **Enable SSL** for secure connections
5. **Regular backups** of the database

### Default Credentials
- **Admin:** admin@petvet.com / password
- **Users:** user1@email.com to user10@email.com / password
- **Staff:** john.smith@petvet.com / password
- **Doctors:** dr.sarah@petvet.com / password

### File Permissions
Ensure proper file permissions:
- **Config files:** 644 (readable by web server)
- **Upload directories:** 755 (writable by web server)
- **Log files:** 666 (writable by web server)

## 🔄 Updates and Maintenance

### Regular Maintenance
- **Backup database** daily/weekly
- **Monitor log files** for errors
- **Update passwords** regularly
- **Check for updates** to the system

### Troubleshooting
- **Connection issues:** Check database credentials
- **Email problems:** Verify SMTP settings
- **Performance issues:** Check indexes and queries
- **Security concerns:** Review access logs

## 📞 Support

For database-related issues:
1. Check the error logs
2. Verify configuration settings
3. Test database connectivity
4. Review the system requirements

The PetVet database is now ready for use! 🎉
