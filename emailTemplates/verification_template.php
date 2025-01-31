<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code Email</title>
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
            text-align: center;
            border-radius: 5px;
            font-weight: bold;
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
            <h1>Two-Factor Login Authentication</h1>
        </div>
        <div class="greeting">
            <h2>Greetings <span style="font-style: italic; color:#05066d">{{name}}</span> !</h2>
        </div>
        <p>You are receiving this email because you or someone is attempting to access your account to log in to KonsulTap. Please use the verification code below within the next 5 minutes to complete your login process securely.</p>
        <div class="verification-code">
            <strong>VERIFICATION CODE: {{verification_code}}</strong>
        </div>
        <p>If you did not initiate this login attempt or if you encounter any issues, please contact the ITC Department immediately for assistance.</p>
        <p>Thank you for your immediate attention to this matter.</p><br>
        <p>Best regards,<br>KonsulTAP Team</p>
    </div>
    <div class="footer">
        &copy; {{year}} KonsulTAP. All rights reserved.
    </div>
</body>
</html>
