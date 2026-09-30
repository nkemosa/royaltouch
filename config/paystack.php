<?php
/*
 * Paystack configuration.
 *
 * 1. Create a Paystack account.
 * 2. Copy your TEST secret key while developing.
 * 3. Replace the value below.
 * 4. Before going live, replace it with your LIVE secret key.
 *
 * Never put the secret key in JavaScript or expose it in the browser.
 */
$paystackSecretKey = 'sk_test_REPLACE_WITH_YOUR_PAYSTACK_SECRET_KEY';

/*
 * Change this to the public URL of this project when uploaded online.
 * Example: https://yourdomain.com/cart
 */
$siteUrl = 'http://localhost/cart';

$paystackApiUrl = 'https://api.paystack.co';
