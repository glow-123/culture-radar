# 🚀 CultureRadar MAMP Setup Guide

## ✅ Files Status - ALL UPDATED!

All files have been successfully updated for MAMP configuration:

### Core Files:
- ✅ `config.php` - MAMP defaults configured
- ✅ `index.php` - Updated database connection
- ✅ `login.php` - Updated database connection  
- ✅ `register.php` - Updated database connection
- ✅ `dashboard.php` - Updated database connection
- ✅ `discover.php` - Updated database connection
- ✅ `onboarding.php` - Updated database connection
- ✅ `setup-database.php` - Updated for MAMP ports

### Portal Files:
- ✅ `organizer/login.php` - Updated database connection
- ✅ `organizer/dashboard.php` - Updated database connection
- ✅ `organizer/events.php` - Updated database connection

### Admin Files:
- ✅ `admin/dashboard.php` - Updated database connection
- ✅ `admin/badges.php` - Updated database connection

### API & Scripts:
- ✅ `api/recommendations.php` - Updated database connection
- ✅ `scripts/train_ai.php` - Updated database connection

### Classes:
- ✅ `classes/BadgeSystem.php` - Updated database connection

## 📋 Setup Instructions

### 1. Copy Files
Copy ALL files from `C:\Users\glasi\OneDrive\Bureau\mission_possible` to `C:\MAMP\htdocs\culture-radar\`

### 2. Configure Environment
Rename `.env.mamp` to `.env` in the culture-radar folder

### 3. Start MAMP
- Open MAMP
- Start Apache (port 8888) and MySQL (port 8889) servers

### 4. Create Database
- Visit: http://localhost:8888/phpMyAdmin
- Create database named: `culture_radar`

### 5. Initialize Database
In the culture-radar folder, run:
```bash
php setup-database.php
```

### 6. Test Application
Visit: http://localhost:8888/culture-radar/

## 🎯 Access URLs

- **Homepage:** http://localhost:8888/culture-radar/
- **Register:** http://localhost:8888/culture-radar/register.php
- **Login:** http://localhost:8888/culture-radar/login.php
- **Dashboard:** http://localhost:8888/culture-radar/dashboard.php
- **Discover Events:** http://localhost:8888/culture-radar/discover.php
- **Organizer Portal:** http://localhost:8888/culture-radar/organizer/
- **Admin Panel:** http://localhost:8888/culture-radar/admin/

## 🔧 Configuration Details

### Database Settings (MAMP):
- Host: `localhost:8889`
- Database: `culture_radar`
- Username: `root`
- Password: `root`

### Web Server:
- Apache Port: `8888`
- Document Root: `C:\MAMP\htdocs\culture-radar\`

## ✨ Features Ready:
- ✅ AI-powered personalized recommendations
- ✅ Event discovery with advanced filtering
- ✅ User dashboard with preferences
- ✅ Organizer portal for event management
- ✅ Admin panel with analytics
- ✅ Badge system for independent venues
- ✅ Weather and transport integration
- ✅ Machine learning recommendation engine

## 🆘 Troubleshooting

If you encounter any issues:
1. Check MAMP servers are running (green lights)
2. Verify database `culture_radar` exists
3. Ensure `.env` file is properly configured
4. Check PHP error logs in MAMP

The application is now 100% ready for MAMP! 🎉