<?php
// Set JSON header first
header('Content-Type: application/json');

// Disable error display to prevent breaking JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

try {
    // Database connection configuration
    if (!file_exists('db_config.php')) {
        throw new Exception("Database configuration file not found. Please create db_config.php");
    }
    
    require_once 'db_config.php';

    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        ob_clean();
        http_response_code(500);
        echo json_encode(["message" => "Database connection failed. Please check your database configuration."]);
        exit();
    }

    // Set charset to utf8mb4 for proper character encoding
    $conn->set_charset("utf8mb4");

    // Include PHPMailer classes (optional - only if PHPMailer is installed)
    $phpmailer_available = false;
    if (file_exists('PHPMailer/src/PHPMailer.php')) {
        require_once 'PHPMailer/src/Exception.php';
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        $phpmailer_available = true;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get form data
        $name = isset($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name']))) : '';
        $email = isset($_POST['email']) ? htmlspecialchars(strip_tags(trim($_POST['email']))) : '';
        $subject = isset($_POST['subject']) ? htmlspecialchars(strip_tags(trim($_POST['subject']))) : '';
        $message = isset($_POST['message']) ? htmlspecialchars(strip_tags(trim($_POST['message']))) : '';

        // Validate required fields
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["message" => "All fields are required."]);
            exit();
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["message" => "Invalid email format."]);
            exit();
        }

        // Validate and map subject to ENUM values
        $valid_subjects = ['General Inquiry', 'Support', 'Feedback'];
        $subject_lower = strtolower(trim($subject));
        
        // Default mapped subject
        $mapped_subject = 'General Inquiry';
        
        foreach ($valid_subjects as $valid_subject) {
            if (strtolower($valid_subject) === $subject_lower || strpos($subject_lower, strtolower($valid_subject)) !== false) {
                $mapped_subject = $valid_subject;
                break;
            }
        }
        
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO inquiry (full_name, email_address, subject, message) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
            ob_clean();
            http_response_code(500);
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(["message" => "Database error: " . $conn->error]);
            exit();
        }
        
        // Use mapped subject for the database
        $message_to_store = $message;

        // Bind parameters with error handling
        if (!$stmt->bind_param("ssss", $name, $email, $mapped_subject, $message_to_store)) {
            ob_clean();
            http_response_code(500);
            error_log("Bind failed: " . $stmt->error);
            echo json_encode(["message" => "Failed to process form data. Please try again."]);
            $stmt->close();
            exit();
        }

        // Execute the statement with error tracking
        $execute_result = $stmt->execute();
        
        if ($execute_result) {
            $success_message = "Message stored successfully.";
            $email_sent = false;
            
            // Try to send email notification if PHPMailer is available
            if ($phpmailer_available) {
                $gmail_user = "hidayahlasiman@gmail.com"; // Your Gmail address - UPDATE THIS!
                $gmail_password = "xfvb dlhl bzsj clqk"; // Gmail App Password - UPDATE THIS!
                $recipient_email = "hidayahlasiman@gmail.com"; // Where to send notifications
                
                if ($gmail_user === "your-email@gmail.com" || $gmail_password === "your-app-password") {
                    error_log("Email not sent: Gmail credentials not configured in email.php");
                    $success_message = "Message stored successfully. Email notification not configured - please set up Gmail credentials in email.php";
                } else {
                    // Create PHPMailer instance
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    
                    // Enable verbose debug output (disable in production)
                    $mail->SMTPDebug = 0; // Set to 2 for detailed debug info
                    $mail->Debugoutput = function($str, $level) {
                        error_log("PHPMailer Debug: $str");
                    };
                    
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = $gmail_user;
                        $mail->Password = $gmail_password;
                        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Use TLS
                        $mail->Port = 587;
                        $mail->CharSet = 'UTF-8';
                        
                        // Recipients
                        $mail->setFrom($email, $name); // Use the submitted email
                        $mail->addAddress($recipient_email); // Recipient email
                        $mail->addReplyTo($email, $name); // Use the same submitted email
                        
                        // Content
                        $mail->isHTML(true);
                        // Updated Subject: No categorization
                        $mail->Subject = 'NEW INQUIRY';
                        $mail->Body = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background-color: #f59e0b; color: white; padding: 20px; text-align: center; }
                                .content { background-color: #f9f9f9; padding: 20px; }
                                .field { margin-bottom: 15px; }
                                .label { font-weight: bold; color: #555; }
                                .value { margin-top: 5px; padding: 10px; background-color: white; border-left: 3px solid #f59e0b; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h2>New Inquiry</h2>
                                </div>
                                <div class='content'>
                                    <div class='field'>
                                        <div class='label'>Name:</div>
                                        <div class='value'>" . htmlspecialchars($name) . "</div>
                                    </div>
                                    <div class='field'>
                                        <div class='label'>Email:</div>
                                        <div class='value'>" . htmlspecialchars($email) . "</div>
                                    </div>
                                    <div class='field'>
                                        <div class='label'>Subject:</div>
                                        <div class='value'>" . htmlspecialchars($subject) . "</div>
                                    </div>
                                    <div class='field'>
                                        <div class='label'>Message:</div>
                                        <div class='value'>" . nl2br(htmlspecialchars($message_to_store)) . "</div>
                                    </div>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";

                        // Plain text version
                        $mail->AltBody = "Name: $name\nEmail: $email\nSubject: " . htmlspecialchars($subject) . "\nMessage:\n$message";

                        // Send email
                        $mail->send();
                        $email_sent = true;
                        $success_message = "Message sent successfully!";
                    } catch (\PHPMailer\PHPMailer\Exception $e) {
                        // Log detailed error
                        $error_info = $mail->ErrorInfo;
                        error_log("Email sending failed: " . $error_info);
                        $success_message = "Message stored successfully, but email notification failed. Error: " . $error_info;
                    }
                }
            } else {
                // PHPMailer not installed
                error_log("Email not sent: PHPMailer library not found. Please install PHPMailer.");
                $success_message = "Message stored successfully. Email notification disabled - PHPMailer not installed.";
            }
            
            ob_clean();
            echo json_encode(["message" => $success_message]);
        } else {
            ob_clean();
            http_response_code(500);
            
            // Get detailed error information
            $error_code = $stmt->errno;
            $error_message = $stmt->error;
            
            // Also check connection errors
            if (empty($error_message) && $conn->error) {
                $error_message = $conn->error;
                $error_code = $conn->errno;
            }
            
            // Handle specific error cases
            $user_message = "Failed to save message. Please try again.";
            
            if ($error_code == 1062) { // Duplicate entry error - UNIQUE constraint violation (should not happen)
                $user_message = "Database error: UNIQUE constraint detected. Please run http://localhost/kbse2/setup_allow_duplicate_emails.php to fix this.";
            } elseif ($error_code == 1264) { // Out of range for column
                $user_message = "One of the fields is too long. Please shorten your message.";
            } elseif ($error_code == 1048) { // Column cannot be null
                $user_message = "Please fill in all required fields.";
            } else {
                // Log the actual error for debugging
                error_log("=== CONTACT FORM ERROR ===");
                error_log("Error Code: $error_code");
                error_log("Error Message: $error_message");
                error_log("Connection Error: " . $conn->error);
                error_log("Attempted to insert:");
                error_log("  - Name: $name");
                error_log("  - Email: $email");
                error_log("  - Subject (mapped): $mapped_subject");
                error_log("  - Message length: " . strlen($message_to_store));
                error_log("========================");
                
                // For development: show detailed error (remove in production)
                $user_message = "Failed to save message. Error: " . $error_message . " (Code: $error_code)";
            }
            
            echo json_encode(["message" => $user_message]);
        }

        $stmt->close();
    } else {
        // Not a POST request - redirect back to the form if accessed directly
        header("Location: contact.html");
        exit();
    }

    $conn->close();
    
} catch (Exception $e) {
    // Clean any output buffer
    ob_clean();
    
    // Log the error with full details
    error_log("Contact form error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    
    // Return JSON error response (don't expose technical details to user)
    http_response_code(500);
    echo json_encode(["message" => "An error occurred. Please try again."]);
    exit();
}

// Clean output buffer before closing
ob_end_flush();
?>