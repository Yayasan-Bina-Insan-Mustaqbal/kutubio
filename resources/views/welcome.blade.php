<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kutubio - Library Inventory Pipeline</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --accent-color: #8b5cf6;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Background Animation */
        .bg-blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, var(--accent-color) 0%, transparent 70%);
            filter: blur(80px);
            opacity: 0.15;
            z-index: -1;
            animation: float 20s infinite alternate;
        }

        .blob-1 { top: -10%; left: -10%; }
        .blob-2 { bottom: -10%; right: -10%; background: radial-gradient(circle, #3b82f6 0%, transparent 70%); }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(100px, 50px) scale(1.1); }
        }

        .container {
            text-align: center;
            padding: 3rem;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 600px;
            width: 90%;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            font-size: 3.5rem;
            font-weight: 700;
            letter-spacing: -0.05em;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #fff, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tagline {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .btn-signin {
            display: inline-block;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            background: var(--accent-color);
            border: none;
            border-radius: 1rem;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);
        }

        .btn-signin:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 25px -5px rgba(139, 92, 246, 0.4);
            filter: brightness(1.1);
        }

        .btn-signin:active {
            transform: translateY(0);
        }

        .footer {
            position: absolute;
            bottom: 2rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div class="container">
        <h1 class="logo">Kutubio</h1>
        <p class="tagline">Stabilizing Library Inventory Pipeline with AI.<br>Effortless classification and management.</p>
        
        @auth
            <a href="{{ url('/admin') }}" class="btn-signin">Go to Dashboard</a>
        @else
            <a href="{{ url('/admin/login') }}" class="btn-signin">Sign In</a>
        @endauth
    </div>

    <footer class="footer">
        &copy; {{ date('Y') }} Kutubio Inventory System
    </footer>
</body>
</html>
