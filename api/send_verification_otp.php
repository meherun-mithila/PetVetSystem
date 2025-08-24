<?php
// Verification OTP sending API
header('Content-Type: application/json');

// Basic validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$otp = $input['otp'] ?? '';
$name = $input['name'] ?? 'User';

if (empty($email) || empty($otp)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and OTP are required']);
    exit;
}

// Professional verification email template
$htmlMessage = "
<html>
<head>
    <title>Email Verification - PetVet</title>
</head>
<body>
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f8f9fa;'>
        <div style='background-color: #2c5aa0; color: white; padding: 20px; text-align: center;'>
            <h1 style='margin: 0;'>🐾 PetVet</h1>
            <p style='margin: 5px 0 0 0;'>Caring Paws Veterinary Clinic</p>
        </div>
        
        <div style='padding: 30px; background-color: white;'>
            <h2 style='color: #2c5aa0; margin-top: 0;'>Hello $name!</h2>
            
            <p>Thank you for registering with PetVet. To complete your registration, please use the verification code below:</p>
            
            <div style='text-align: center; margin: 30px 0; padding: 20px; background-color: #f8f9fa; border-radius: 8px;'>
                <p style='margin: 0 0 10px 0; color: #666; font-size: 14px;'>Your verification code:</p>
                <div style='font-size: 32px; font-weight: bold; color: #10B981; letter-spacing: 5px;'>$otp</div>
            </div>
            
            <p><strong>Important:</strong> This verification code will expire in 10 minutes.</p>
            
            <p>If you didn't create this account, please ignore this email.</p>
            
            <hr style='margin: 30px 0; border: none; border-top: 1px solid #e9ecef;'>
            
            <p style='color: #666; font-size: 14px;'>
                Best regards,<br>
                The PetVet Team
            </p>
        </div>
        
        <div style='background-color: #343a40; color: white; padding: 20px; text-align: center; font-size: 12px;'>
            <p>&copy; " . date('Y') . " PetVet - Caring Paws Veterinary Clinic. All rights reserved.</p>
        </div>
    </div>
</body>
</html>";

$textMessage = "Hello $name!\n\nThank you for registering with PetVet. Your verification code is: $otp\n\nThis code will expire in 10 minutes.\n\nBest regards,\nThe PetVet Team";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: PetVet <noreply@petvet.local>" . "\r\n";
$headers .= "Reply-To: support@petvet.local" . "\r\n";

$sent = mail($email, 'Verify Your Email - PetVet', $htmlMessage, $headers);

if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Verification email sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send verification email']);
}
?>
