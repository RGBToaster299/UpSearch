<?php
// UpSearch - Homepage
session_start();

// Basic configuration
$config = [
    'site_name' => 'UpSearch',
    'site_description' => 'A simple, clean search engine for discovering websites',
    'discord_webhook' => '', // Add your Discord webhook URL here
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #333;
        }

        .header {
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            transition: background 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .main-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
        }

        .search-container {
            background: white;
            border-radius: 25px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            margin-bottom: 2rem;
        }

        .search-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .search-subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .search-form {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            border: 2px solid #e1e5e9;
            border-radius: 50px;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease;
        }

        .search-button:hover {
            transform: translateY(-50%) scale(1.05);
        }

        .stats {
            color: white;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .footer {
            padding: 1rem 2rem;
            text-align: center;
            color: white;
            opacity: 0.7;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }

            .search-title {
                font-size: 2rem;
            }

            .search-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo"><?php echo htmlspecialchars($config['site_name']); ?></a>
        <nav class="nav-links">
            <a href="list.php">Browse Sites</a>
            <a href="submit.php">Submit Site</a>
            <a href="report.php">Report</a>
        </nav>
    </header>

    <main class="main-container">
        <div class="search-container">
            <h1 class="search-title"><?php echo htmlspecialchars($config['site_name']); ?></h1>
            <p class="search-subtitle"><?php echo htmlspecialchars($config['site_description']); ?></p>
            
            <form class="search-form" action="search.php" method="GET">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    placeholder="Search for websites..." 
                    required
                    autocomplete="off"
                >
                <button type="submit" class="search-button">Search</button>
            </form>
        </div>

        <div class="stats">
            <?php
            $siteCount = 0;
            if (is_dir('savedSites')) {
                $siteCount = count(glob('savedSites/*.json'));
            }
            echo $siteCount . ' websites indexed';
            ?>
        </div>
    </main>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($config['site_name']); ?> by Nekari.</p>
    </footer>

    <script>
        // Simple search input enhancement
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });

        // Add some visual feedback
        document.querySelector('.search-input').addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });

        document.querySelector('.search-input').addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    </script>
</body>
</html>
