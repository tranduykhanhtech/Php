<?php
/**
 * Email Helper Functions
 * Các hàm hỗ trợ gửi email qua SMTP
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Gửi email sử dụng SMTP trực tiếp qua socket
 */
function sendEmail($to, $subject, $body, $from_email = null, $from_name = null) {
    $from_email = $from_email ?? SMTP_FROM_EMAIL;
    $from_name = $from_name ?? SMTP_FROM_NAME;
    
    try {
        // Kết nối tới SMTP server
        $smtp = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
        if (!$smtp) {
            throw new Exception("Không thể kết nối SMTP: {$errstr} ({$errno})");
        }
        
        // Đọc banner
        $response = fgets($smtp);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("SMTP banner lỗi: {$response}");
        }
        
        // EHLO
        fputs($smtp, "EHLO " . SMTP_HOST . "\r\n");
        $response = '';
        while ($line = fgets($smtp)) {
            $response .= $line;
            if (preg_match('/^\d{3} /', $line)) break;
        }
        
        // STARTTLS
        fputs($smtp, "STARTTLS\r\n");
        $response = fgets($smtp);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("STARTTLS lỗi: {$response}");
        }
        
        // Bật TLS
        if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("Không thể bật TLS");
        }
        
        // EHLO lại sau TLS
        fputs($smtp, "EHLO " . SMTP_HOST . "\r\n");
        while ($line = fgets($smtp)) {
            if (preg_match('/^\d{3} /', $line)) break;
        }
        
        // AUTH LOGIN
        fputs($smtp, "AUTH LOGIN\r\n");
        fgets($smtp);
        
        fputs($smtp, base64_encode(SMTP_USER) . "\r\n");
        fgets($smtp);
        
        fputs($smtp, base64_encode(SMTP_PASS) . "\r\n");
        $response = fgets($smtp);
        if (substr($response, 0, 3) != '235') {
            throw new Exception("Xác thực SMTP thất bại: {$response}");
        }
        
        // MAIL FROM
        fputs($smtp, "MAIL FROM: <{$from_email}>\r\n");
        $response = fgets($smtp);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("MAIL FROM lỗi: {$response}");
        }
        
        // RCPT TO
        fputs($smtp, "RCPT TO: <{$to}>\r\n");
        $response = fgets($smtp);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("RCPT TO lỗi: {$response}");
        }
        
        // DATA
        fputs($smtp, "DATA\r\n");
        fgets($smtp);
        
        // Email content
        $email_content = "From: {$from_name} <{$from_email}>\r\n";
        $email_content .= "To: <{$to}>\r\n";
        $email_content .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $email_content .= "MIME-Version: 1.0\r\n";
        $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
        $email_content .= "Content-Transfer-Encoding: base64\r\n";
        $email_content .= "\r\n";
        $email_content .= chunk_split(base64_encode($body));
        $email_content .= "\r\n.\r\n";
        
        fputs($smtp, $email_content);
        $response = fgets($smtp);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("DATA lỗi: {$response}");
        }
        
        // QUIT
        fputs($smtp, "QUIT\r\n");
        fgets($smtp);
        fclose($smtp);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Lỗi gửi email tới {$to}: " . $e->getMessage());
        return false;
    }
}

/**
 * Gửi email OTP reset mật khẩu
 * 
 * @param string $to Email người nhận
 * @param string $otp Mã OTP
 * @param string $user_name Tên người dùng
 * @return bool True nếu gửi thành công
 */
function sendPasswordResetOTP($to, $otp, $user_name) {
    $subject = 'Mã OTP đặt lại mật khẩu - ' . SMTP_FROM_NAME;
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #10B981; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; }
            .otp-box { background: white; border: 2px dashed #10B981; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
            .otp-code { font-size: 32px; font-weight: bold; color: #10B981; letter-spacing: 5px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .warning { background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 15px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Đặt lại mật khẩu</h1>
            </div>
            <div class="content">
                <p>Xin chào <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                
                <div class="otp-box">
                    <p style="margin: 0; font-size: 14px; color: #666;">Mã OTP của bạn là:</p>
                    <div class="otp-code">' . $otp . '</div>
                    <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">Mã này có hiệu lực trong 10 phút</p>
                </div>
                
                <p>Vui lòng nhập mã OTP này vào trang đặt lại mật khẩu để tiếp tục.</p>
                
                <div class="warning">
                    <strong>⚠️ Lưu ý:</strong> Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này. 
                    Không chia sẻ mã OTP này với bất kỳ ai.
                </div>
                
                <p>Trân trọng,<br><strong>' . SMTP_FROM_NAME . '</strong></p>
            </div>
            <div class="footer">
                <p>Email này được gửi tự động, vui lòng không trả lời.</p>
                <p>&copy; ' . date('Y') . ' ' . SMTP_FROM_NAME . '. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return sendEmail($to, $subject, $body);
}

/**
 * Generate OTP 6 số
 * 
 * @return string Mã OTP 6 số
 */
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Lưu OTP vào database
 * 
 * @param string $email Email
 * @param string $otp Mã OTP
 * @param int $expires_minutes Thời gian hết hạn (phút)
 * @return bool True nếu thành công
 */
function savePasswordResetOTP($email, $otp, $expires_minutes = 10) {
    global $pdo;
    
    try {
        // Xóa OTP cũ của email này
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
        
        // Tạo OTP mới
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $expires_minutes . ' minutes'));
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$email, $otp, $expires_at]);
    } catch (Exception $e) {
        error_log('Save OTP error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Xác thực OTP
 * 
 * @param string $email Email
 * @param string $otp Mã OTP
 * @return bool True nếu OTP hợp lệ
 */
function verifyPasswordResetOTP($email, $otp) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM password_resets 
            WHERE email = ? AND otp = ? AND expires_at > NOW() AND is_used = 0
            LIMIT 1
        ");
        $stmt->execute([$email, $otp]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log('Verify OTP error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Đánh dấu OTP đã sử dụng
 * 
 * @param string $email Email
 * @param string $otp Mã OTP
 * @return bool True nếu thành công
 */
function markOTPAsUsed($email, $otp) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE password_resets SET is_used = 1 WHERE email = ? AND otp = ?");
        return $stmt->execute([$email, $otp]);
    } catch (Exception $e) {
        error_log('Mark OTP as used error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Gửi email thông báo contact mới
 * 
 * @param string $name Tên người gửi
 * @param string $email Email người gửi
 * @param string $phone Số điện thoại
 * @param string $subject Chủ đề
 * @param string $message Nội dung
 * @return bool True nếu gửi thành công
 */
function sendContactNotification($name, $email, $phone, $subject, $message) {
    $subject_map = [
        'product_inquiry' => 'Tư vấn sản phẩm',
        'order_support' => 'Hỗ trợ đơn hàng',
        'complaint' => 'Khiếu nại',
        'partnership' => 'Hợp tác',
        'other' => 'Khác'
    ];
    
    $subject_text = isset($subject_map[$subject]) ? $subject_map[$subject] : $subject;
    
    $email_subject = "[Gecko Shop] Liên hệ mới: {$subject_text}";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='color: white; margin: 0; font-size: 28px;'>
                📧 Liên hệ mới
            </h1>
        </div>
        
        <div style='background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none;'>
            <div style='background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                <h2 style='color: #22c55e; margin-top: 0; margin-bottom: 20px; font-size: 22px;'>
                    Thông tin liên hệ
                </h2>
                
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e5e7eb;'>
                            <strong style='color: #6b7280; display: inline-block; width: 120px;'>Họ tên:</strong>
                            <span style='color: #111827;'>{$name}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e5e7eb;'>
                            <strong style='color: #6b7280; display: inline-block; width: 120px;'>Email:</strong>
                            <a href='mailto:{$email}' style='color: #22c55e; text-decoration: none;'>{$email}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e5e7eb;'>
                            <strong style='color: #6b7280; display: inline-block; width: 120px;'>Điện thoại:</strong>
                            <span style='color: #111827;'>" . ($phone ? $phone : 'Không có') . "</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 0; border-bottom: 1px solid #e5e7eb;'>
                            <strong style='color: #6b7280; display: inline-block; width: 120px;'>Chủ đề:</strong>
                            <span style='background: #22c55e; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;'>{$subject_text}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 12px 0;'>
                            <strong style='color: #6b7280; display: block; margin-bottom: 8px;'>Nội dung:</strong>
                            <div style='background: #f9fafb; padding: 15px; border-radius: 6px; border-left: 3px solid #22c55e;'>
                                " . nl2br(htmlspecialchars($message)) . "
                            </div>
                        </td>
                    </tr>
                </table>
                
                <div style='margin-top: 25px; padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;'>
                    <p style='margin: 0; color: #92400e; font-size: 14px;'>
                        <strong>⚡ Lưu ý:</strong> Vui lòng phản hồi khách hàng trong vòng 24 giờ để đảm bảo chất lượng dịch vụ.
                    </p>
                </div>
                
                <div style='margin-top: 20px; text-align: center;'>
                    <a href='mailto:{$email}?subject=Re: {$subject_text}' 
                       style='display: inline-block; background: #22c55e; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>
                        📧 Trả lời ngay
                    </a>
                </div>
            </div>
        </div>
        
        <div style='background: #f9fafb; padding: 20px; text-align: center; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px;'>
            <p style='margin: 0; color: #6b7280; font-size: 12px;'>
                Email này được gửi tự động từ hệ thống Gecko Shop<br>
                <a href='https://gecko.io.vn' style='color: #22c55e; text-decoration: none;'>gecko.io.vn</a>
            </p>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail('contact@gecko.io.vn', $email_subject, $body);
}
?>
