<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Код за Верификацију</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); overflow: hidden;">
        <div style="background-color: #667eea; color: #ffffff; padding: 30px 20px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 600;">🔐 Верификација Двофакторске Аутентификације</h1>
        </div>

        <div style="padding: 40px 30px;">
            <div style="font-size: 16px; margin-bottom: 20px; color: #555;">
                <p>Поштовани/а <strong>{{ $userName }}</strong>,</p>
            </div>

            <p>Примили сте овај email јер се неко покушава пријавити на ваш налог у Employee Attendance систему.</p>

            <div style="background-color: #667eea; border-radius: 8px; padding: 25px; text-align: center; margin: 30px 0;">
                <p style="font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #ffffff; font-family: 'Courier New', Courier, monospace; margin: 0; background-color: #667eea;">{{ $code }}</p>
                <p style="margin-top: 15px; font-size: 14px; color: #ffffff;">⏱ Код истиче за {{ $expiryMinutes }} минута</p>
            </div>

            <p>Унесите овај код на страници за верификацију како бисте завршили процес пријављивања.</p>

            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0; color: #856404; font-size: 14px;">⚠️ <strong>Упозорење:</strong> Уколико нисте покушали да се пријавите, молимо вас да занемарите овај email и контактирате администратора система.</p>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                Овај код је валидан само {{ $expiryMinutes }} минута и може се користити само једном. Немојте делити овај код ни са ким, укључујући и запослене.
            </p>
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e9ecef;">
            <p style="margin: 5px 0;"><strong>Employee Attendance System</strong></p>
            <p style="margin: 5px 0;">Ово је аутоматски генерисан email. Молимо вас да не одговарате на ову поруку.</p>
            <p style="margin: 5px 0;">&copy; {{ date('Y') }} Employee Attendance. Сва права задржана.</p>
        </div>
    </div>
</body>
</html>
