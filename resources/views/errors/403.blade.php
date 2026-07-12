@php($messages = ['403' => 'Forbidden', '404' => 'Not found', '419' => 'Page expired', '500' => 'Server error'])
<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><title>Jawla</title></head>
<body style="font-family:system-ui;padding:2rem"><h1>{{ $messages['403'] ?? 'Error' }}</h1></body>
</html>
