<?php
// UpSearch - Search Results
session_start();

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

if (empty($query)) {
    header('Location: index.php');
    exit;
}

// Load all sites
$sites = [];
if (is_dir('savedSites')) {
    $files = glob('savedSites/*.json');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $site = json_decode($content, true);
        if ($site) {
            $sites[] = $site;
        }
    }
}

// Simple search function
function searchSites($sites, $query) {
    $results = [];
    $queryLower = strtolower($query);
    
    foreach ($sites as $site) {
        $score = 0;
        
        // Search in title
        if (stripos($site['title'], $query) !== false) {
            $score += 10;
        }
        
        // Search in description
        if (stripos($site['description'], $query) !== false) {
            $score += 5;
        }
        
        // Search in URL
        if (stripos($site['url'], $query) !== false) {
            $score += 3;
        }
        
        // Search in category
        if (stripos($site['category'], $query) !== false) {
            $score += 2;
        }
        
        if ($score > 0) {
            $site['score'] = $score;
            $results[] = $site;
        }
    }
    
    // Sort by score
    usort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    return $results;
}

$results = searchSites($sites, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?php echo $query; ?> - UpSearch</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: white;
            border-bottom: 1px solid #e1e5e9;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }

        .search-form {
            flex: 1;
            max-width: 600px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            border-color: #667eea;
        }

        .search-button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: #666;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            transition: background 0.3s ease;
        }

        .nav-links a:hover {
            background: #f0f0f0;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .search-info {
            margin-bottom: 2rem;
            color: #666;
        }

        .results-container {
            display: grid;
            gap: 1.5rem;
        }

        .result-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .result-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .result-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .result-title a {
            color: #667eea;
            text-decoration: none;
        }

        .result-title a:hover {
            text-decoration: underline;
        }

        .result-url {
            color: #28a745;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            word-break: break-all;
        }

        .result-description {
            color: #666;
            margin-bottom: 1rem;
        }

        .result-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #999;
        }

        .result-category {
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-results h2 {
            margin-bottom: 1rem;
            color: #333;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .search-form {
                order: -1;
            }

            .main-content {
                padding: 1rem;
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
        <div class="header-content">
            <a href="index.php" class="logo">UpSearch</a>
            
            <form class="search-form" action="search.php" method="GET">
                <input 
                    type="text" 
                    name="q" 
                    class="search-input" 
                    value="<?php echo $query; ?>"
                    placeholder="Search for websites..."
                    required
                >
                <button type="submit" class="search-button">Search</button>
            </form>

            <nav class="nav-links">
                <a href="list.php">Browse</a>
                <a href="submit.php">Submit</a>
                <a href="report.php">Report</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="search-info">
            <p>Found <?php echo count($results); ?> results for "<strong><?php echo $query; ?></strong>"</p>
        </div>

        <div class="results-container">
            <?php if (empty($results)): ?>
                <div class="no-results">
                    <h2>No results found</h2>
                    <p>Try different keywords or <a href="submit.php">submit a new website</a>.</p>
                </div>
            <?php else: ?>
                <?php foreach ($results as $result): ?>
                    <div class="result-item">
                        <h3 class="result-title">
                            <a href="<?php echo htmlspecialchars($result['url']); ?>" target="_blank" rel="noopener">
                                <?php echo htmlspecialchars($result['title']); ?>
                            </a>
                        </h3>
                        <div class="result-url"><?php echo htmlspecialchars($result['url']); ?></div>
                        <p class="result-description"><?php echo htmlspecialchars($result['description']); ?></p>
                        <div class="result-meta">
                            <span class="result-category"><?php echo htmlspecialchars($result['category']); ?></span>
                            <span>Added: <?php echo date('M j, Y', strtotime($result['submitted_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
