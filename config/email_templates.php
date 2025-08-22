<?php
/**
 * Email Templates for JLM
 * Includes logo, social media handles, and ticket numbers
 */



/**
 * Get the JLM logo HTML (base64 encoded or URL)
 */
function getJLMLogo() {
    // You can replace this with your actual logo URL or base64 encoded image
    $logoUrl = 'https://jlm.com.ng/assets/images/jlm.png'; // Replace with your actual logo URL
    
    return '<img src="' . $logoUrl . '" alt="JLM" style="max-width: 150px; height: auto; margin-bottom: 20px;">';
}

/**
 * Get social media links HTML
 */
function getSocialMediaLinks() {
    return '
    <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <h4 style="margin: 0 0 15px 0; color: #333; text-align: center;">Connect With Us</h4>
        <div style="text-align: center;">
            <a href="https://wa.me/2348150917741" style="display: inline-block; text-decoration: none; color: #25D366; background: white; padding: 10px 15px; border-radius: 6px; border: 2px solid #25D366; font-weight: 600; font-size: 14px; margin: 5px; min-width: 110px; text-align: center;">
                WhatsApp
            </a>
            <a href="https://instagram.com/jlukamedia" style="display: inline-block; text-decoration: none; color: #E4405F; background: white; padding: 10px 15px; border-radius: 6px; border: 2px solid #E4405F; font-weight: 600; font-size: 14px; margin: 5px; min-width: 110px; text-align: center;">
                Instagram
            </a>
            <a href="https://facebook.com/jlukamedia" style="display: inline-block; text-decoration: none; color: #1877F2; background: white; padding: 10px 15px; border-radius: 6px; border: 2px solid #1877F2; font-weight: 600; font-size: 14px; margin: 5px; min-width: 110px; text-align: center;">
                Facebook
            </a>
            <a href="https://twitter.com/jlukamedia" style="display: inline-block; text-decoration: none; color: #1DA1F2; background: white; padding: 10px 15px; border-radius: 6px; border: 2px solid #1DA1F2; font-weight: 600; font-size: 14px; margin: 5px; min-width: 110px; text-align: center;">
                Twitter
            </a>
        </div>
    </div>';
}

/**
 * Get email header with logo and branding
 */
function getEmailHeader($title = 'JLM') {
    return '
    <div style="background: linear-gradient(135deg, #1b2299 0%, #e4306d 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0;">
        ' . getJLMLogo() . '
        <h1 style="margin: 0; font-size: 24px; font-weight: 600;">' . $title . '</h1>
    </div>';
}

/**
 * Get email footer with contact info and social media
 */
function getEmailFooter() {
    return '
    <div style="background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; margin-top: 20px;">
        <div style="text-align: center; margin-bottom: 15px;">
            <p style="margin: 0; color: #666; font-size: 14px;">
                <strong>JLM</strong><br>
                Email: info@jlm.com.ng | Phone: +2348150917741<br>
                Website: <a href="https://jlm.com.ng" style="color: #1b2299;">jlm.com.ng</a>
            </p>
        </div>
        ' . getSocialMediaLinks() . '
        <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #999; font-size: 12px;">
                This email was sent from JLM. Please do not reply to this automated message.
            </p>
        </div>
    </div>';
}

/**
 * Get ticket number display
 */
function getTicketNumberDisplay($ticketNumber) {
    return '
    <div style="background: #e3f2fd; border: 2px solid #2196f3; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center;">
        <h3 style="margin: 0 0 10px 0; color: #1976d2;">Reference Ticket</h3>
        <p style="margin: 0; font-size: 18px; font-weight: bold; color: #2196f3; font-family: monospace;">' . $ticketNumber . '</p>
        <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Please keep this number for future reference</p>
    </div>';
}

/**
 * Get form data display
 */
function getFormDataDisplay($formData) {
    $html = '<div style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0;">';
    $html .= '<h3 style="margin: 0 0 15px 0; color: #333;">Form Details</h3>';
    
    foreach ($formData as $field => $value) {
        if (!empty($value)) {
            $fieldName = ucfirst(str_replace('_', ' ', $field));
            $html .= '<div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;">';
            $html .= '<strong style="color: #666; display: inline-block; width: 120px;">' . $fieldName . ':</strong>';
            $html .= '<span style="color: #333;">' . htmlspecialchars($value) . '</span>';
            $html .= '</div>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Complete email template wrapper
 */
function getCompleteEmailTemplate($title, $content, $ticketNumber = null) {
    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $title . '</title>
        <style>
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background: #f5f5f5;
            }
            .email-container {
                max-width: 600px; 
                margin: 0 auto; 
                background: white; 
                border-radius: 10px; 
                overflow: hidden; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .content {
                padding: 30px 20px;
            }
            .button {
                display: inline-block; 
                background: linear-gradient(135deg, #1b2299 0%, #e4306d 100%); 
                color: white; 
                padding: 12px 24px; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 20px 0; 
                font-weight: 600;
            }
            .highlight {
                background: #fff3cd; 
                border: 1px solid #ffeaa7; 
                border-radius: 5px; 
                padding: 15px; 
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            ' . getEmailHeader($title) . '
            <div class="content">';
    
    if ($ticketNumber) {
        $html .= getTicketNumberDisplay($ticketNumber);
    }
    
    $html .= $content . '
            </div>
            ' . getEmailFooter() . '
        </div>
    </body>
    </html>';
    
    return $html;
} 