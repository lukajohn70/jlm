<?php
// thankyou.php - Booking Success Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Received | JLM Creative</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f7f8fa; font-family: 'Inter', Arial, sans-serif; margin:0; }
        .center-wrap { display:flex;justify-content:center;align-items:center;min-height:100vh; }
        .thankyou-card {
            background:#fff;
            border-radius:18px;
            box-shadow:0 4px 24px rgba(27,34,153,0.07);
            padding:48px 32px 40px 32px;
            max-width:420px;
            width:100%;
            text-align:center;
        }
        .thankyou-emoji { font-size:2.5rem;line-height:1;margin-bottom:18px; }
        .thankyou-title { font-size:1.5rem;font-weight:700;margin-bottom:10px;color:#1b2299; }
        .thankyou-desc { font-size:1.1rem;color:#222;margin-bottom:32px; }
        .thankyou-ctas { display:flex;flex-direction:column;gap:18px;align-items:center; }
        .submit-btn, .whatsapp-btn {
            border: none;
            outline: none;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(27,34,153,0.08);
            transition: background 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .submit-btn {
            background: linear-gradient(90deg, #5b4ee6, #e4306d);
            color: #fff;
            padding: 18px 0;
            font-size: 1.1rem;
            margin-bottom: 0;
            max-width:340px;width:100%;
        }
        .submit-btn:hover {
            background: linear-gradient(90deg, #1b2299, #e4306d);
            box-shadow: 0 4px 16px rgba(27,34,153,0.12);
        }
        .whatsapp-btn {
            background: #25d366;
            color: #fff;
            padding: 18px 0;
            font-size: 1.1rem;
            max-width:340px;width:100%;
        }
        .whatsapp-btn:hover {
            background: #1ebe57;
            box-shadow: 0 4px 16px rgba(27,34,153,0.12);
        }
    </style>
</head>
<body>
<div class="center-wrap">
  <div class="thankyou-card">
    <div class="thankyou-emoji">🎉</div>
    <div class="thankyou-title">Your booking has been received!</div>
    <div class="thankyou-desc">We appreciate your interest in JLM Creative.<br>Our team will contact you within 24 hours to confirm your consultation.</div>
    <div class="thankyou-ctas">
      <a href="index.php" class="submit-btn">Return to Home</a>
      <a href="booking.php" class="submit-btn" style="background:linear-gradient(90deg,#1b2299,#e4306d);">Book Another Project</a>
      <a href="https://chat.whatsapp.com/EXAMPLEGROUPID" target="_blank" class="whatsapp-btn" style="display:flex;align-items:center;justify-content:center;gap:10px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 32 32"><path fill="#fff" d="M16 3C9.373 3 4 8.373 4 15c0 2.64.86 5.09 2.33 7.09L4 29l7.18-2.28A12.93 12.93 0 0 0 16 27c6.627 0 12-5.373 12-12S22.627 3 16 3zm0 22c-1.98 0-3.84-.58-5.41-1.58l-.39-.25-4.27 1.36 1.4-4.14-.25-.4A9.93 9.93 0 0 1 6 15c0-5.52 4.48-10 10-10s10 4.48 10 10-4.48 10-10 10zm5.07-7.75c-.28-.14-1.65-.81-1.9-.9-.25-.09-.43-.14-.61.13-.18.27-.7.92-.87 1.11-.16.19-.32.21-.6.07-.28-.14-1.17-.42-2.23-1.37-.82-.74-1.37-1.65-1.53-1.92-.16-.27-.02-.43.12-.57.13-.13.28-.33.41-.5.13-.17.18-.28.28-.47.09-.19.05-.35-.02-.48-.07-.13-.61-1.48-.84-2.03-.22-.54-.45-.46-.61-.47-.16-.01-.34-.01-.54-.01-.18 0-.48.07-.73.35-.25.28-.96.97-.96 2.37 0 1.4 1 2.75 1.14 2.94.13.18 1.97 3.01 4.77 4.12.67.28 1.19.45 1.6.57.67.21 1.28.18 1.76.11.53-.08 1.65-.68 1.88-1.32.23-.64.23-1.2.16-1.32-.07-.12-.25-.18-.53-.32z"/></svg>
        Join Our WhatsApp TV
      </a>
    </div>
  </div>
</div>
</body>
</html> 