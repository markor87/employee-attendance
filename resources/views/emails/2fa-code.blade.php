<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Код за Верификацију</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555;
        }
        .code-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #ffffff;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
        }
        .expiry {
            margin-top: 15px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Верификација Двофакторске Аутентификације</h1>
        </div>

        <div class="content">
            <div class="greeting">
                <p>Поштовани/а <strong>{{ $userName }}</strong>,</p>
            </div>

            <p>Примили сте овај email јер се неко покушава пријавити на ваш налог у Employee Attendance систему.</p>

            <div class="code-container">
                <p class="code">{{ $code }}</p>
                <p class="expiry">⏱ Код истиче за {{ $expiryMinutes }} минута</p>
            </div>

            <p>Унесите овај код на страници за верификацију како бисте завршили процес пријављивања.</p>

            <div class="warning">
                <p>⚠️ <strong>Упозорење:</strong> Уколико нисте покушали да се пријавите, молимо вас да занемарите овај email и контактирате администратора система.</p>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                Овај код је валидан само {{ $expiryMinutes }} минута и може се користити само једном. Немојте делити овај код ни са ким, укључујући и запослене.
            </p>
        </div>

        <div class="footer">
            <p><strong>Employee Attendance System</strong></p>
            <p>Ово је аутоматски генерисан email. Молимо вас да не одговарате на ову поруку.</p>
            <p>&copy; {{ date('Y') }} Employee Attendance. Сва права задржана.</p>
        </div>
    </div>
</body>
</html>
