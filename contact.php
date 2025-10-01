<?php
// contact.php — simple mail handler (GoDaddy compatible, GCP/SendGrid ready)
// Notes:
// - On shared hosts like GoDaddy, PHP mail() may work if From is your domain mailbox.
// - On Google App Engine Standard, outbound mail() is blocked — use SendGrid (API) instead.
// - Configure environment variables in app.yaml (for GCP) or your hosting panel.

// CONFIG (env first, fallback to defaults)
$siteName = getenv('APP_NAME') ?: 'DiligentWorks';
$to = getenv('TO_EMAIL') ?: 'sales@yourdomain.com'; // e.g., info@diligentworks.example
$from = getenv('FROM_EMAIL') ?: 'no-reply@yourdomain.com'; // use mailbox on your domain
$sendgridApiKey = getenv('SENDGRID_API_KEY') ?: '';

function respond_json($success, $message){
  header('Content-Type: application/json');
  echo json_encode(['success'=>$success,'message'=>$message]);
  exit;
}

function is_ajax(){
  $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
  $xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) : '';
  return (strpos($accept, 'application/json') !== false) || ($xhr === 'xmlhttprequest');
}

function send_via_sendgrid($apiKey, $to, $from, $subject, $body, $replyName, $replyEmail, $siteName){
  $payload = [
    'personalizations' => [[
      'to' => [[ 'email' => $to ]],
      'subject' => $subject
    ]],
    'from' => [ 'email' => $from, 'name' => $siteName ],
    'reply_to' => [ 'email' => $replyEmail, 'name' => $replyName ],
    'content' => [[ 'type' => 'text/plain', 'value' => $body ]]
  ];
  $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
  ]);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);
  if($err){ return false; }
  // SendGrid returns 202 on success
  return ($code >= 200 && $code < 300);
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  if(is_ajax()) respond_json(false, 'Method not allowed');
}

// Honeypot
$hp = isset($_POST['website']) ? trim($_POST['website']) : '';
if($hp !== ''){
  if(is_ajax()) respond_json(true, 'Thank you!');
  echo "<p>Thank you!</p>"; exit;
}

// Basic timing check
$ts = isset($_POST['_form_ts']) ? (int)$_POST['_form_ts'] : 0;
if($ts > 0 && (time()*1000 - $ts) < 3000){ // less than 3s
  if(is_ajax()) respond_json(false, 'Please wait a moment before submitting.');
}

// Collect & validate
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$company = isset($_POST['company']) ? trim($_POST['company']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if($name === '' || $email === '' || $message === ''){
  if(is_ajax()) respond_json(false, 'Please fill in name, email, and message.');
  echo '<p>Please fill in required fields.</p>'; exit;
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
  if(is_ajax()) respond_json(false, 'Please enter a valid email address.');
  echo '<p>Invalid email.</p>'; exit;
}

$subject = "New enquiry from $siteName";
$body = "Name: $name\nEmail: $email\nCompany: $company\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? 'n/a') . "\n---\n$message\n";

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/plain; charset=UTF-8';
$headers[] = 'From: ' . $siteName . ' <' . $from . '>';
$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
$headers[] = 'X-Mailer: PHP/' . phpversion();

// If SendGrid API is configured, use it (required on GCP App Engine Standard)
$sent = false;
if(!empty($sendgridApiKey)){
  $sent = send_via_sendgrid($sendgridApiKey, $to, $from, $subject, $body, $name, $email, $siteName);
} else {
  // Fallback to PHP mail() for hosts that allow it (e.g., GoDaddy)
  $sent = @mail($to, $subject, $body, implode("\r\n", $headers));
}

if($sent){
  if(is_ajax()) respond_json(true, 'Thanks! Your message has been sent.');
  echo '<p>Thanks! Your message has been sent.</p>';
}else{
  if(is_ajax()) respond_json(false, 'Unable to send email at the moment. Please try again later.');
  echo '<p>Unable to send email at the moment. Please try again later.</p>';
}
