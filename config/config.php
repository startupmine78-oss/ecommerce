<?php
date_default_timezone_set('Asia/Ulaanbaatar');
define('SITE_NAME',    'ShopMN');
define('SITE_URL',     'http://localhost/ecommerce');
define('SITE_EMAIL',   'noreply@shopmn.mn');

define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_SECURE',   'tls');
define('SMTP_USERNAME', 'bathvlegbattulga204@gmail.com');  
define('SMTP_PASSWORD', 'cujfaccehgcmjvcy');    
define('SMTP_FROM',     'bathvlegbattulga204@gmail.com');
define('SMTP_FROM_NAME','ShopMN');

define('OTP_EXPIRE_MINUTES', 10);    
define('OTP_MAX_ATTEMPTS',   5);    
define('OTP_RESEND_SECONDS', 60);    

define('QPAY_USERNAME',  'TEST_MERCHANT');        
define('QPAY_PASSWORD',  'TEST_MERCHANT');        
define('QPAY_INVOICE_CODE', 'TEST_INVOICE');      
define('QPAY_URL',       'https://merchant.qpay.mn/v2');
define('QPAY_CALLBACK',  SITE_URL . '/auth/payment_callback.php');

define('SOCIALPAY_MERCHANT',  'test_merchant');
define('SOCIALPAY_SECRET',    'test_secret_key');
define('SOCIALPAY_TERMINAL',  'test_terminal');
define('SOCIALPAY_URL',       'https://ecommerce.socialpay.mn');

define('MONPAY_USERNAME', 'test_user');
define('MONPAY_APP_NAME', 'ShopMN');
define('MONPAY_URL',      'https://api.monpay.mn');

define('KHANPAY_MERCHANT_ID', 'TEST001');
define('KHANPAY_KEY',         'test_khanpay_key');
define('KHANPAY_URL',         'https://khanpay.mn/api');
