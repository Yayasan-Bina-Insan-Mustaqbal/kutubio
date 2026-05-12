<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kutubio - AI-Powered Library Inventory Pipeline</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #020617;
            --card-bg: #0f172a;
            --primary: #f97316;
            --primary-hover: #ea580c;
            --secondary: #8b5cf6;
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
            --accent-gradient: linear-gradient(135deg, #f97316 0%, #d946ef 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* --- Components --- */

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(249, 115, 22, 0.4);
        }

        .btn-outline {
            border: 1px solid var(--border-color);
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--text-dim);
        }

        /* --- Header --- */

        header {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 100;
            padding: 1.5rem 0;
            transition: all 0.3s ease;
        }

        header.scrolled {
            background: rgba(2, 6, 23, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo span {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-dim);
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: white;
        }

        /* --- Hero --- */

        .hero {
            padding: 10rem 0 6rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -10%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.15) 0%, transparent 70%);
            filter: blur(100px);
            z-index: -1;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -0.03em;
        }

        .hero-content h1 span {
            display: block;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-content p {
            font-size: 1.125rem;
            color: var(--text-dim);
            margin-bottom: 2.5rem;
            max-width: 500px;
        }

        .hero-image {
            position: relative;
            border-radius: 2rem;
            overflow: hidden;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border-color);
        }

        .hero-image img {
            width: 100%;
            display: block;
            transition: transform 0.5s ease;
        }

        .hero-image:hover img {
            transform: scale(1.02);
        }

        /* --- Features --- */

        .features {
            padding: 8rem 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: var(--text-dim);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .feature-card {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: rgba(249, 115, 22, 0.3);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .feature-icon {
            width: 3rem;
            height: 3rem;
            background: rgba(249, 115, 22, 0.1);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: var(--text-dim);
            font-size: 0.95rem;
        }

        /* --- CTA Section --- */

        .cta-section {
            padding: 6rem 0;
            text-align: center;
        }

        .cta-banner {
            background: var(--accent-gradient);
            padding: 4rem;
            border-radius: 2.5rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .cta-banner h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .cta-banner p {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
        }

        .cta-banner .btn-primary {
            background: white;
            color: var(--primary);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* --- Footer --- */

        footer {
            padding: 4rem 0 2rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-dim);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 4rem;
            margin-bottom: 3rem;
        }

        .footer-brand h3 {
            color: white;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .footer-links h4 {
            color: white;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .footer-links ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-links a {
            text-decoration: none;
            color: var(--text-dim);
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .copyright {
            text-align: center;
            font-size: 0.85rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* --- Mobile Responsive --- */

        @media (max-width: 968px) {
            .hero-grid { grid-template-columns: 1fr; text-align: center; gap: 3rem; }
            .hero-content h1 { font-size: 3rem; }
            .hero-content p { margin-left: auto; margin-right: auto; }
            .features-grid { grid-template-columns: 1fr; }
            .hero-image { max-width: 600px; margin: 0 auto; }
            .nav-links { display: none; }
        }

        /* Micro-animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .floating {
            animation: float 4s ease-in-out infinite;
        }

    </style>
</head>
<body>

    <header id="header">
        <div class="container">
            <nav>
                <a href="/" class="logo">Kutub<span>io</span></a>
                <ul class="nav-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How it Works</a></li>
                    <li><a href="https://kutubio.insantaqwa.org/admin">Admin Panel</a></li>
                </ul>
                <div class="nav-cta">
                    @auth
                        <a href="{{ url('/admin') }}" class="btn btn-primary">Dashboard</a>
                    @else
                        <a href="{{ url('/admin/login') }}" class="btn btn-primary">Sign In</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-content">
                        <h1>Smart <span>Inventory</span> Pipeline for Libraries.</h1>
                        <p>Accelerate your book cataloging with AI-powered OCR, auto-classification, and barcode scanning. Built for high-volume management.</p>
                        <div class="hero-actions">
                            @auth
                                <a href="{{ url('/admin/capture-book') }}" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                    Start Capturing
                                </a>
                            @else
                                <a href="{{ url('/admin/login') }}" class="btn btn-primary">Get Started Now</a>
                            @endauth
                            <a href="#features" class="btn btn-outline">Explore Features</a>
                        </div>
                    </div>
                    <div class="hero-image floating">
                        <img src="{{ asset('images/hero.png') }}" alt="Kutubio Mobile Experience">
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="container">
                <div class="section-header">
                    <h2>Advanced Capabilities</h2>
                    <p>Designed to streamline the messy process of physical book inventory into a clean, digital pipeline.</p>
                </div>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/><path d="M11 8v6"/></svg>
                        </div>
                        <h3>AI Vision OCR</h3>
                        <p>Automatically extract titles and authors from cover images using advanced computer vision. No manual typing required.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5v14c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2Z"/><path d="M7 7h10"/><path d="M7 12h10"/><path d="M7 17h10"/></svg>
                        </div>
                        <h3>Smart Categorization</h3>
                        <p>Intelligent classification of books into subjects and categories based on extracted metadata and AI analysis.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                        </div>
                        <h3>Bulk Import & Sync</h3>
                        <p>Easily sync your inventory across devices and export data for labels, stickers, and library systems.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-banner">
                    <h2>Ready to Digitally Transform?</h2>
                    <p>Join the future of library management and save thousands of hours on manual entry.</p>
                    @auth
                        <a href="{{ url('/admin') }}" class="btn btn-primary">Go to Admin Dashboard</a>
                    @else
                        <a href="{{ url('/admin/login') }}" class="btn btn-primary">Create Your Account</a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3>Kutubio</h3>
                    <p>The intelligent backbone for modern libraries and book archives. Scalable, fast, and AI-first.</p>
                </div>
                <div class="footer-links">
                    <h4>Platform</h4>
                    <ul>
                        <li><a href="https://kutubio.insantaqwa.org/admin/capture-book">Capture Tool</a></li>
                        <li><a href="https://kutubio.insantaqwa.org/admin/books">Inventory</a></li>
                        <li><a href="https://kutubio.insantaqwa.org/admin/loans">Loan Tracking</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; {{ date('Y') }} Kutubio Inventory Pipeline. Built with ❤️ for Knowledge Preservation.</p>
            </div>
        </div>
    </footer>

    <script>
        window.onscroll = function() {
            var header = document.getElementById('header');
            if (window.pageYOffset > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        };
    </script>
</body>
</html>
