<!DOCTYPE html>
<html>
<head>
    <title>Sesi Berakhir</title>
    <style>
        body { text-align: center; padding: 50px; font-family: sans-serif; }
        .container { max-width: 500px; margin: auto; }
        .btn { 
            display: inline-block; padding: 10px 20px; 
            background: #3490dc; color: white; 
            text-decoration: none; border-radius: 5px; 
            margin-top: 20px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sesi Anda Telah Berakhir</h1>
        <p>Maaf, sesi Anda sudah kadaluwarsa karena tidak ada aktivitas. Silakan klik tombol di bawah untuk kembali ke halaman login.</p>
        
        <a href="{{ route('login') }}" class="btn">Kembali ke Login</a>
    </div>
</body>
</html>