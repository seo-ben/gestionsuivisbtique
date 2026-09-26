<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GESP Cloud API • Passerelle Centrale de Gestion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-primary: #0a0e17;
            --bg-secondary: #111827;
            --bg-card: rgba(17, 24, 39, 0.7);
            --border-card: rgba(255, 255, 255, 0.08);
            --accent-primary: #3b82f6;
            --accent-glow: rgba(59, 130, 246, 0.25);
            --accent-green: #10b981;
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow-x: hidden;
            line-height: 1.5;
        }

        /* Subtle glowing background orbs */
        .glow-orb-1 {
            position: absolute;
            top: -150px;
            left: 20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(10, 14, 23, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .glow-orb-2 {
            position: absolute;
            bottom: -150px;
            right: 15%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, rgba(10, 14, 23, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 40px 24px;
            position: relative;
            z-index: 1;
            width: 100%;
        }

        /* Top Navigation Header */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-card);
            margin-bottom: 40px;
        }

        .brand-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
        }

        .brand-text h1 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .brand-text p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 500;
            color: var(--accent-green);
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: var(--accent-green);
            border-radius: 50%;
            position: relative;
        }

        .pulse-dot::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            background-color: var(--accent-green);
            animation: pulse-ring 2s infinite cubic-bezier(0.4, 0, 0.6, 1);
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.9);
                opacity: 0.8;
            }
            70% {
                transform: scale(2.2);
                opacity: 0;
            }
            100% {
                opacity: 0;
            }
        }

        /* Hero section */
        .hero {
            text-align: center;
            max-width: 680px;
            margin: 0 auto 48px;
        }

        .hero-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(59, 130, 246, 0.12);
            color: #60a5fa;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .hero h2 {
            font-size: 38px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.03em;
            margin-bottom: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 16px;
            color: var(--text-secondary);
        }

        /* Grid Cards */
        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 16px;
            padding: 24px;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.16);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-icon {
            font-size: 18px;
        }

        .badge-active {
            font-size: 11px;
            padding: 3px 8px;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-radius: 6px;
            font-weight: 600;
        }

        .card-desc {
            font-size: 13.5px;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        .endpoint-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .endpoint-item {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            padding: 8px 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .method {
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10.5px;
        }

        .method-post {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .method-get {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .route {
            color: #e2e8f0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Terminal card */
        .terminal-card {
            background: #0d1117;
            border: 1px solid var(--border-card);
            border-radius: 16px;
            padding: 22px 24px;
            margin-bottom: 40px;
        }

        .terminal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .terminal-dots {
            display: flex;
            gap: 6px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .dot-red { background: #ef4444; }
        .dot-yellow { background: #f59e0b; }
        .dot-green { background: #10b981; }

        .terminal-title {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: var(--text-muted);
        }

        .code-box {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: #38bdf8;
            word-break: break-all;
            background: rgba(0, 0, 0, 0.4);
            padding: 14px 16px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            user-select: all;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 24px 0 10px;
            font-size: 13px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-card);
        }

        footer a {
            color: #60a5fa;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .hero h2 {
                font-size: 28px;
            }
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="glow-orb-1"></div>
    <div class="glow-orb-2"></div>

    <div class="container">
        <!-- Header -->
        <header>
            <div class="brand-badge">
                <div class="brand-logo">G</div>
                <div class="brand-text">
                    <h1>GESP Gateway API</h1>
                    <p>Système Centralisé de Gestion des Boutiques</p>
                </div>
            </div>

            <div class="status-pill">
                <span class="pulse-dot"></span>
                <span>API Opérationnelle (v1.0.0)</span>
            </div>
        </header>

        <!-- Hero -->
        <section class="hero">
            <span class="hero-tag">Infrastructure Backend Cloud</span>
            <h2>Passerelle Haute Disponibilité pour Flutter & Web</h2>
            <p>API RESTful sécurisée par Sanctum, synchronisation hors-ligne bidirectionnelle et stockage centralisé sur TiDB Cloud MySQL.</p>
        </section>

        <!-- Cards -->
        <div class="grid-cards">
            <!-- Card 1: Auth & Sécurité -->
            <div class="glass-card">
                <div class="card-header">
                    <span class="card-title"><span class="card-icon">🔐</span> Authentification</span>
                    <span class="badge-active">Sanctum</span>
                </div>
                <p class="card-desc">Gestion des sessions et jetons porteurs pour les administrateurs et vendeurs.</p>
                <div class="endpoint-list">
                    <div class="endpoint-item">
                        <span class="method method-post">POST</span>
                        <span class="route">/api/auth/login</span>
                    </div>
                    <div class="endpoint-item">
                        <span class="method method-get">GET</span>
                        <span class="route">/api/auth/me</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Synchronisation Ventes -->
            <div class="glass-card">
                <div class="card-header">
                    <span class="card-title"><span class="card-icon">⚡</span> Sync Offline POS</span>
                    <span class="badge-active">Idempotent</span>
                </div>
                <p class="card-desc">Transmission par lots des tickets de vente offline enregistrés sur mobile.</p>
                <div class="endpoint-list">
                    <div class="endpoint-item">
                        <span class="method method-post">POST</span>
                        <span class="route">/api/ventes/sync</span>
                    </div>
                    <div class="endpoint-item">
                        <span class="method method-get">GET</span>
                        <span class="route">/api/boutiques/{id}/produits</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Clôture & Trésorerie -->
            <div class="glass-card">
                <div class="card-header">
                    <span class="card-title"><span class="card-icon">📊</span> Gestion & Rapports</span>
                    <span class="badge-active">Temps Réel</span>
                </div>
                <p class="card-desc">Suivi multi-boutiques, calcul automatique des écarts et tableau de bord admin.</p>
                <div class="endpoint-list">
                    <div class="endpoint-item">
                        <span class="method method-get">GET</span>
                        <span class="route">/api/dashboard</span>
                    </div>
                    <div class="endpoint-item">
                        <span class="method method-post">POST</span>
                        <span class="route">/api/boutiques/{id}/clotures</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terminal test -->
        <div class="terminal-card">
            <div class="terminal-header">
                <div class="terminal-dots">
                    <div class="dot dot-red"></div>
                    <div class="dot dot-yellow"></div>
                    <div class="dot dot-green"></div>
                </div>
                <div class="terminal-title">Test de Connexion Terminal (cURL)</div>
            </div>
            <div class="code-box">
                curl -k -X POST https://zgp.digitalforges.org/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"telephone":"0600000000","password":"password"}'
            </div>
        </div>

        <!-- Footer -->
        <footer>
            <p>GESP Cloud Gateway &copy; {{ date('Y') }} &bull; Hébergé sur LWS &bull; Base de données TiDB Cloud Serverless</p>
        </footer>
    </div>
</body>
</html>
