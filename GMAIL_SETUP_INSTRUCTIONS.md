# Gmail Email Notification Setup Instructions

## Step 1: Install PHPMailer

You have two options to install PHPMailer:

### Option A: Using Composer (Recommended)
1. If you don't have Composer installed, download it from https://getcomposer.org/
2. Open terminal/command prompt in your project directory
3. Run: `composer require phpmailer/phpmailer`

### Option B: Manual Installation
1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract the ZIP file
3. Copy the `PHPMailer` folder to your project root directory (same level as `email.php`)
4. The structure should be: `kbse2/PHPMailer/src/`

## Step 2: Create Gmail App Password

Since Gmail requires 2-Step Verification for SMTP access, you need to create an App Password:

1. Go to your Google Account: https://myaccount.google.com/
2. Click on **Security** (left sidebar)
3. Under **2-Step Verification**, make sure it's enabled (if not, enable it first)
4. Scroll down and click on **App passwords**
5. Select **Mail** as the app and **Other (Custom name)** as the device
6. Enter "KBSE Website" as the name
7. Click **Generate**
8. Copy the 16-character password (you'll see it only once)

## Step 3: Update email.php Configuration

1. Open `email.php`
2. Find these lines (around line 36-38):
   ```php
   $gmail_user = "your-email@gmail.com"; // Your Gmail address
   $gmail_password = "your-app-password"; // Gmail App Password
   $recipient_email = "hidayahlasiman@gmail.com"; // Where to send notifications
   ```
3. Replace:
   - `your-email@gmail.com` with your actual Gmail address
   - `your-app-password` with the 16-character App Password from Step 2
   - `hidayahlasiman@gmail.com` with the email where you want to receive notifications (can be the same Gmail or different)

## Step 4: Test the Email Functionality

1. Fill out the contact form on your website
2. Submit it
3. Check the recipient email inbox (and spam folder if needed)
4. You should receive a nicely formatted HTML email notification

## Troubleshooting

### Error: "Could not instantiate mail function"
- Make sure PHPMailer is properly installed
- Check that the file paths in `require_once` statements are correct

### Error: "SMTP connect() failed"
- Verify your Gmail App Password is correct (no spaces)
- Make sure 2-Step Verification is enabled on your Gmail account
- Check that your server/firewall allows outbound connections on port 587

### Error: "Authentication failed"
- Double-check your Gmail email address
- Verify the App Password is correct (copy-paste to avoid typos)
- Make sure you're using the App Password, not your regular Gmail password

### Emails going to Spam
- This is normal for new email setups
- Recipients should mark emails as "Not Spam"
- Over time, Gmail will learn to trust your emails

## Security Notes

- **Never commit your email.php with real credentials to Git**
- Consider using environment variables or a config file outside the web root
- Keep your App Password secure and don't share it

## Alternative: Use a Config File

For better security, you can create a `config.php` file (outside web root if possible):

```php
<?php
// config.php
define('GMAIL_USER', 'your-email@gmail.com');
define('GMAIL_PASSWORD', 'your-app-password');
define('RECIPIENT_EMAIL', 'hidayahlasiman@gmail.com');
?>
```

Then include it in `email.php`:
```php
require_once 'config.php';
$gmail_user = GMAIL_USER;
$gmail_password = GMAIL_PASSWORD;
$recipient_email = RECIPIENT_EMAIL;
```

