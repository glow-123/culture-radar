# 🚀 CultureRadar Deployment Guide

This guide will help you deploy CultureRadar to various hosting environments. The application supports multiple deployment methods including Docker, traditional hosting, and cloud platforms.

## 📋 Prerequisites

Before deploying CultureRadar, ensure you have:

- **PHP 7.4+** with extensions: PDO, MySQL, JSON, cURL, OpenSSL
- **MySQL 8.0+** or MariaDB 10.4+
- **Web server**: Apache 2.4+ or Nginx 1.18+
- **SSL certificate** (recommended for production)
- **Domain name** pointed to your server

### Optional for AI features:
- **Redis** for caching (recommended)
- **Cron job** access for AI training
- **External API keys** (OpenAgenda, Google Maps, etc.)

## 🐳 Docker Deployment (Recommended)

Docker deployment is the easiest and most reliable method.

### 1. Quick Start

```bash
# Clone or upload your CultureRadar files
cd /path/to/culture-radar

# Copy environment configuration
cp .env.example .env

# Edit configuration (see Configuration section below)
nano .env

# Run deployment script
./deploy.sh
```

### 2. Manual Docker Deployment

```bash
# Start services
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f app
```

### 3. Production Docker Setup

```bash
# Deploy with SSL
./deploy.sh --ssl

# Deploy with cleanup
./deploy.sh --cleanup

# Deploy without backup
./deploy.sh --skip-backup
```

## 🏢 Traditional Hosting Deployment

For shared hosting or VPS without Docker.

### 1. File Upload

Upload all files to your web server's document root:

```bash
# Using SCP
scp -r . user@your-server:/path/to/web/root/

# Using FTP client or hosting control panel
# Upload all files maintaining directory structure
```

### 2. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE culture_radar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'culture_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON culture_radar.* TO 'culture_user'@'localhost';
FLUSH PRIVILEGES;

# Run setup script
php setup-database.php
```

### 3. Web Server Configuration

#### Apache (.htaccess)

```apache
RewriteEngine On

# Redirect to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Pretty URLs
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^?]*) index.php?url=$1 [L,QSA]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

#### Nginx

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    
    root /path/to/culture-radar;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to sensitive files
    location ~ /\.(env|git) {
        deny all;
    }
}
```

## ☁️ Cloud Platform Deployment

### Heroku

```bash
# Install Heroku CLI
# Create Heroku app
heroku create your-app-name

# Add MySQL addon
heroku addons:create jawsdb:kitefin

# Set environment variables
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false

# Deploy
git push heroku main
```

### AWS EC2

```bash
# Launch EC2 instance (Ubuntu 20.04 LTS recommended)
# Connect to instance
ssh -i your-key.pem ubuntu@your-instance-ip

# Install Docker
sudo apt update
sudo apt install docker.io docker-compose
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker ubuntu

# Deploy application
git clone your-repo
cd culture-radar
cp .env.example .env
# Edit .env file
./deploy.sh --ssl
```

### Google Cloud Platform

```bash
# Use Cloud Run for container deployment
gcloud run deploy culture-radar \
  --image gcr.io/PROJECT_ID/culture-radar \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated
```

## ⚙️ Configuration

Edit the `.env` file with your specific settings:

### Essential Configuration

```bash
# Application
APP_NAME="Culture Radar"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_HOST=localhost
DB_NAME=culture_radar
DB_USER=your_db_user
DB_PASS=your_secure_password

# Security
APP_KEY=your-32-character-secret-key
SESSION_SECRET=your-session-secret-key
```

### Optional API Keys

```bash
# For enhanced features
OPENAGENDA_API_KEY=your_api_key
GOOGLE_MAPS_API_KEY=your_api_key
OPENWEATHER_API_KEY=your_api_key
GOOGLE_ANALYTICS_ID=GA_TRACKING_ID
```

### Email Configuration

```bash
# For notifications
MAIL_HOST=smtp.your-provider.com
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

## 🔧 Post-Deployment Setup

### 1. Admin Account Creation

```bash
# Access your application
# Register first account (becomes admin)
# Or create manually in database:
INSERT INTO users (name, email, password, is_admin, created_at) 
VALUES ('Admin', 'admin@yourdomain.com', '$2y$10$hashed_password', 1, NOW());
```

### 2. AI Training Setup

```bash
# Set up cron job for AI training
crontab -e

# Add this line for daily training at 2 AM
0 2 * * * cd /path/to/culture-radar && php scripts/train_ai.php >> logs/ai-training.log 2>&1
```

### 3. SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d your-domain.com

# Auto-renewal
sudo systemctl enable certbot.timer
```

## 📊 Monitoring & Maintenance

### Health Checks

```bash
# Check application status
curl -f https://your-domain.com/health || echo "Application down"

# Check database
mysql -u culture_user -p -e "SELECT 1" culture_radar

# Check logs
tail -f logs/application.log
```

### Backup Strategy

```bash
# Database backup script
#!/bin/bash
BACKUP_DIR="/backups/culture-radar"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR
mysqldump -u culture_user -p culture_radar > "$BACKUP_DIR/backup_$DATE.sql"

# Keep only last 30 days
find $BACKUP_DIR -name "backup_*.sql" -mtime +30 -delete
```

### Updates

```bash
# Backup before updating
./deploy.sh --skip-backup  # Creates backup automatically

# Pull latest code
git pull origin main

# Deploy updates
./deploy.sh
```

## 🔍 Troubleshooting

### Common Issues

#### Database Connection Error
```bash
# Check database credentials in .env
# Verify database server is running
sudo systemctl status mysql

# Test connection
mysql -u culture_user -p culture_radar
```

#### Permission Issues
```bash
# Set correct permissions
sudo chown -R www-data:www-data /path/to/culture-radar
sudo chmod -R 755 /path/to/culture-radar
sudo chmod -R 777 uploads logs
```

#### PHP Extensions Missing
```bash
# Install required extensions
sudo apt install php-mysql php-curl php-json php-mbstring php-zip
sudo systemctl restart apache2
```

#### SSL Certificate Issues
```bash
# Check certificate status
sudo certbot certificates

# Renew certificate
sudo certbot renew --dry-run
```

### Performance Optimization

#### Enable OPcache
```php
// Add to php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

#### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_events_location ON events(city, start_date);
CREATE INDEX idx_user_interactions_user_date ON user_interactions(user_id, created_at);
```

#### Caching Setup
```bash
# Install Redis
sudo apt install redis-server

# Update .env
CACHE_DRIVER=redis
```

## 📞 Support

### Log Files
- **Application**: `logs/application.log`
- **AI Training**: `logs/ai-training.log`
- **Web Server**: `/var/log/apache2/` or `/var/log/nginx/`
- **Database**: `/var/log/mysql/`

### Getting Help
1. Check logs for error messages
2. Verify configuration in `.env`
3. Test database connectivity
4. Check file permissions
5. Review server requirements

### Resources
- **Documentation**: `/docs/`
- **API Reference**: `/api/docs/`
- **GitHub Issues**: [Report bugs here]
- **Community**: [Join our forum]

---

🎉 **Congratulations!** Your CultureRadar application should now be successfully deployed and running. Users can discover cultural events with AI-powered recommendations!