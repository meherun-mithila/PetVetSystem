# Email Verification Troubleshooting Guide

## Common Issues and Solutions

### 1. Database Setup Issues

#### Problem: "Table doesn't exist" errors
**Solution:** Run the setup script first
```bash
# Navigate to your PetVet directory and run:
http://localhost/PetVet/setup_email_verification.php
```

#### Problem: "Column doesn't exist" errors
**Solution:** The setup script will automatically add the required column
- Run `setup_email_verification.php` to add the `is_verified` column
- Or manually run: `ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0;`

### 2. Email Not Sending

#### Problem: PHP mail() function not working
**Solutions:**
1. **Check XAMPP Mail Configuration:**
   - Open `C:\xampp\php\php.ini`
   - Find `[mail function]` section
   - Configure SMTP settings:
   ```ini
   [mail function]
   SMTP = localhost
   smtp_port = 25
   sendmail_from = your-email@domain.com
   ```

2. **Use Gmail SMTP (Recommended for testing):**
   - Install PHPMailer or configure Gmail SMTP
   - Update the `sendVerificationEmail()` function

3. **Test Email Function:**
   ```php
   // Add this to test_email_verification.php
   $result = mail('your-email@domain.com', 'Test', 'Test message');
   var_dump($result);
   ```

#### Problem: Emails going to spam
**Solutions:**
1. Check spam folder
2. Add sender domain to email whitelist
3. Configure proper SPF/DKIM records (for production)

### 3. Verification Links Not Working

#### Problem: "Invalid or expired verification link"
**Causes and Solutions:**
1. **Token expired:** Links expire after 24 hours
   - Use "Resend Verification" feature
   
2. **Database connection issues:**
   - Check `config.php` database settings
   - Verify database is running
   
3. **URL generation problems:**
   - Check `$_SERVER['HTTP_HOST']` value
   - Ensure proper server configuration

#### Problem: "Verification failed" errors
**Solutions:**
1. Check database error logs
2. Verify table structure
3. Run `setup_email_verification.php` again

### 4. User Registration Issues

#### Problem: "Registration failed" errors
**Solutions:**
1. Check database permissions
2. Verify table structure
3. Check PHP error logs
4. Ensure all required fields are filled

#### Problem: Duplicate email errors
**Solutions:**
1. Use different email address
2. Check if user already exists
3. Verify email format

### 5. System Integration Issues

#### Problem: Users can't login after verification
**Solutions:**
1. Check if `is_verified` column was added to users table
2. Verify the verification process completed successfully
3. Check login system integration

#### Problem: Navigation links broken
**Solutions:**
1. Verify file paths in navigation
2. Check file permissions
3. Ensure all files exist in correct locations

## Step-by-Step Setup Process

### 1. Initial Setup
```bash
# 1. Run database setup
http://localhost/PetVet/setup_email_verification.php

# 2. Test system functionality
http://localhost/PetVet/test_email_verification.php

# 3. Access email verification system
http://localhost/PetVet/email_verification.php
```

### 2. Database Verification
```sql
-- Check if tables exist
SHOW TABLES LIKE 'email_verifications';
SHOW TABLES LIKE 'users';

-- Check users table structure
DESCRIBE users;

-- Check email_verifications table structure
DESCRIBE email_verifications;

-- Count users and verification status
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as verified_users FROM users WHERE is_verified = 1;
```

### 3. Email Configuration Testing
```php
// Test email functionality
<?php
$to = "test@example.com";
$subject = "Test Email";
$message = "This is a test email";
$headers = "From: noreply@yourdomain.com";

$result = mail($to, $subject, $message, $headers);
echo $result ? "Email sent successfully" : "Email failed to send";
?>
```

## Debug Mode

### Enable Error Reporting
```php
// Add to the top of your PHP files for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

### Check Error Logs
- **XAMPP:** `C:\xampp\apache\logs\error.log`
- **PHP:** Check `php.ini` for log file location
- **Database:** Check MySQL error logs

## Performance Optimization

### Database Indexes
```sql
-- Ensure proper indexing
CREATE INDEX idx_verification_token ON email_verifications(verification_token);
CREATE INDEX idx_user_email ON email_verifications(email);
CREATE INDEX idx_user_verified ON users(is_verified);
```

### Cleanup Expired Tokens
```sql
-- Remove expired verification tokens (run periodically)
DELETE FROM email_verifications WHERE expires_at < NOW();
```

## Security Considerations

### Token Security
- Tokens are cryptographically secure (32 bytes random)
- 24-hour expiration prevents long-term abuse
- One-time use prevents replay attacks

### Email Security
- Use HTTPS for verification links
- Implement rate limiting for registration
- Monitor for abuse patterns

## Getting Help

### 1. Check System Requirements
- PHP 7.0+ with mail() function
- MySQL 5.6+ or MariaDB 10.0+
- Proper server configuration

### 2. Common Error Messages
- **"Database connection failed"** → Check `config.php`
- **"Table doesn't exist"** → Run setup script
- **"Email failed to send"** → Check mail configuration
- **"Verification failed"** → Check database and logs

### 3. Support Resources
- Check PHP error logs
- Verify database connectivity
- Test email functionality
- Review system requirements

## Quick Fix Checklist

- [ ] Run `setup_email_verification.php`
- [ ] Check database connection in `config.php`
- [ ] Verify table structure exists
- [ ] Test email functionality
- [ ] Check PHP error logs
- [ ] Verify file permissions
- [ ] Test verification process end-to-end

## Still Having Issues?

If you're still experiencing problems after following this guide:

1. **Check the test file:** `test_email_verification.php`
2. **Review error logs** for specific error messages
3. **Verify system requirements** are met
4. **Test with a simple email** first
5. **Check database permissions** and connectivity

The email verification system is designed to be robust and self-healing. Most issues can be resolved by running the setup script and checking the configuration.
