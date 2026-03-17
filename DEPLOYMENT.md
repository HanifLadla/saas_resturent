# QB Modern Restaurant System - Deployment Guide

## 🚀 Production Deployment

### Server Requirements
- **PHP**: 8.1+ with extensions (BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML)
- **MySQL**: 8.0+
- **Redis**: 6.0+
- **Node.js**: 16+
- **Web Server**: Nginx/Apache with SSL

### Installation Steps

1. **Clone Repository**
```bash
git clone <repository> saas_resturent
cd saas_resturent
```

2. **Install Dependencies**
```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

3. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database Configuration**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qb_restaurant_system
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Redis Configuration**
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

6. **Run Migrations & Seeders**
```bash
php artisan migrate
php artisan db:seed
```

7. **Set Permissions**
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

8. **Start Services**
```bash
# Queue Worker
php artisan queue:work --daemon

# WebSocket Server
php artisan websockets:serve

# Scheduler (add to crontab)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🔧 Configuration

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/qb-restaurant-system/public;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### SSL Configuration
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get SSL Certificate
sudo certbot --nginx -d your-domain.com
```

## 📊 Performance Optimization

### PHP-FPM Configuration
```ini
; /etc/php/8.1/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### MySQL Optimization
```sql
-- my.cnf optimizations
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_size = 128M
max_connections = 200
```

### Redis Configuration
```conf
# redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

## 🔐 Security Checklist

- [ ] SSL certificate installed
- [ ] Firewall configured (ports 80, 443, 22 only)
- [ ] Database user with minimal privileges
- [ ] Regular backups scheduled
- [ ] Log monitoring enabled
- [ ] Rate limiting configured
- [ ] CSRF protection enabled
- [ ] XSS protection enabled

## 📈 Monitoring

### Application Monitoring
```bash
# Install monitoring tools
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### Log Monitoring
```bash
# Setup log rotation
sudo nano /etc/logrotate.d/laravel

/var/www/qb-restaurant-system/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    notifempty
    create 644 www-data www-data
}
```

## 🔄 Backup Strategy

### Database Backup
```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u username -p password qb_restaurant_system > backup_$DATE.sql
aws s3 cp backup_$DATE.sql s3://your-backup-bucket/
```

### File Backup
```bash
# Backup uploads and storage
tar -czf storage_backup_$DATE.tar.gz storage/
aws s3 cp storage_backup_$DATE.tar.gz s3://your-backup-bucket/
```

## 🚀 Scaling Considerations

### Load Balancing
- Use multiple application servers behind load balancer
- Separate database server
- Redis cluster for high availability
- CDN for static assets

### Database Scaling
- Read replicas for reporting
- Database sharding by restaurant_id
- Connection pooling

### Caching Strategy
- Redis for application cache
- Memcached for session storage
- CDN for static content
- Database query caching

This deployment guide ensures your QB Modern Restaurant System runs efficiently in production with proper security, monitoring, and scalability measures.