<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Email</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f9fc;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #47799C; /* Header color */
            color: #ffde59;
            text-align: center;
            padding: 20px;
            border-radius: 8px 8px 0 0;

        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .greeting {
            text-align: center;
            margin: 20px 0;
            color: #333;
            font-weight: 600;
            font-size: 22px;
        }
        .greeting h2 {
            font-style: italic; /* Italicized name */
        }
        p {
            color: #000000; /* Updated text color */
            line-height: 1.6;
            margin: 10px 0;
            font-size: 16px; /* Increased font size */
        }
        .verification-code {
            background-color: #e3f7e9; /* Light green background */
            border: 1px solid #b6e3c4;
            padding: 15px;
            font-size: 20px;
            color: #31708f;
            margin: 20px 0;
            border-radius: 5px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }
        .border {
            border-right: 1px solid #212529;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <img class="border" src="https://konsultap.com/images/lics-logo.png" width="40" alt="">
                <img src="https://konsultap.com/images/logomain.png" width="210" height="40" alt="">
            </div>
            <h1>Password Reset</h1>
        </div>
        <div class="greeting">
            <h2>Greetings <span style="font-style: italic; color:#05066d">{{userName}}</span> !</h2>
        </div>
        <p>We hope this message finds you well. This is a friendly reminder that your password has been successfully reset. Your new password is provided below:</p>
        <div class="verification-code">
            <strong>NEW PASSWORD:</strong> {{newPassword}}<br>
        </div>
        <p>For your security purposes, we recommend that you change your password as soon as possible after logging in. If you experience any issues or need assistance, please don't hesitate to contact the ITC Department.</p>
        <p>Thank you for your immediate attention to this matter.</p><br>
        <p>Best regards,<br>KonsulTAP Team</p>
    </div>
    <div class="footer">
        &copy; {{year}} KonsulTAP. All rights reserved.
    </div>
</body>
</html>