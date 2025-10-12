# Cloudflare Tunnel Setup cho Natural Cosmetics Shop

## 🚀 Hướng dẫn thiết lập Cloudflare Tunnel

### 1. Cài đặt Cloudflare Tunnel

```bash
# Cài đặt cloudflared
# Ubuntu/Debian
wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared-linux-amd64.deb

# Hoặc sử dụng snap
sudo snap install cloudflared
```

### 2. Đăng nhập Cloudflare

```bash
cloudflared tunnel login
```

### 3. Tạo tunnel

```bash
# Tạo tunnel mới
cloudflared tunnel create natural-cosmetics-shop

# Lưu ý: Ghi lại Tunnel ID được tạo
```

### 4. Cấu hình tunnel

Tạo file `config.yml`:

```yaml
tunnel: YOUR_TUNNEL_ID
credentials-file: /home/tranduykhanh/.cloudflared/YOUR_TUNNEL_ID.json

ingress:
  - hostname: your-domain.trycloudflare.com
    service: http://localhost:80
  - service: http_status:404
```

### 5. Chạy tunnel

```bash
# Chạy tunnel
cloudflared tunnel --config config.yml run

# Hoặc chạy nền
cloudflared tunnel --config config.yml run &
```

### 6. Cập nhật cấu hình website

Trong file `config/database.php`, cập nhật:

```php
define('SITE_URL', 'https://your-domain.trycloudflare.com');
```

### 7. Kiểm tra kết nối

Truy cập: `https://your-domain.trycloudflare.com`

## 🔧 Cấu hình nâng cao

### Custom Domain (nếu có)

```yaml
tunnel: YOUR_TUNNEL_ID
credentials-file: /home/tranduykhanh/.cloudflared/YOUR_TUNNEL_ID.json

ingress:
  - hostname: shop.yourdomain.com
    service: http://localhost:80
  - service: http_status:404
```

### Cấu hình HTTPS redirect

Thêm vào `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 🛠️ Troubleshooting

### Lỗi thường gặp:

1. **Tunnel không kết nối**
   - Kiểm tra port 80 có đang chạy web server không
   - Kiểm tra firewall

2. **SSL Certificate lỗi**
   - Cloudflare tự động cung cấp SSL
   - Kiểm tra DNS settings

3. **Session không hoạt động**
   - Đảm bảo `session.cookie_secure = 1`
   - Kiểm tra SameSite settings

### Kiểm tra trạng thái:

```bash
# Kiểm tra tunnel đang chạy
ps aux | grep cloudflared

# Xem logs
cloudflared tunnel --config config.yml run --loglevel debug
```

## 📝 Lưu ý quan trọng

1. **URL thay đổi**: Cloudflare Tunnel URL có thể thay đổi mỗi lần restart
2. **Custom domain**: Nên sử dụng custom domain cho production
3. **Security**: Đảm bảo cấu hình HTTPS và session security
4. **Backup**: Luôn backup database và files

## 🔗 Liên kết hữu ích

- [Cloudflare Tunnel Documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
- [Cloudflare Dashboard](https://dash.cloudflare.com/)
- [Tunnel Management](https://dash.cloudflare.com/access/tunnels)
