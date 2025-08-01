<?php
// UpSearch - Browse All Sites
session_start();

// Load all sites
$sites = [];
if (is_dir('savedSites')) {
    $files = glob('savedSites/*.json');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $site = json_decode($content, true);
        if ($site && !str_contains($file, '.cooldown_')) {
            $sites[] = $site;
        }
    }
}

// Sort by submission date (newest first)
usort($sites, function($a, $b) {
    return strtotime($b['submitted_at']) - strtotime($a['submitted_at']);
});

// Get filter parameters
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Filter by category
if ($category && $category !== 'all') {
    $sites = array_filter($sites, function($site) use ($category) {
        return $site['category'] === $category;
    });
}

// Sort sites
switch ($sort) {
    case 'oldest':
        usort($sites, function($a, $b) {
            return strtotime($a['submitted_at']) - strtotime($b['submitted_at']);
        });
        break;
    case 'title':
        usort($sites, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });
        break;
    case 'newest':
    default:
        // Already sorted by newest
        break;
}

// Get unique categories
$categories = array_unique(array_column($sites, 'category'));
sort($categories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Websites - UpSearch</title>
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
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
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

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
        }

        .filters {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: #333;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .stats {
            color: #666;
            font-size: 0.9rem;
            margin-left: auto;
        }

        .sites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .site-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #f0f0f0;
        }

        .site-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .site-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .site-title a {
            color: #667eea;
            text-decoration: none;
        }

        .site-title a:hover {
            text-decoration: underline;
        }

        .site-url {
            color: #28a745;
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            word-break: break-all;
        }

        .site-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .site-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #999;
        }

        .site-category {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .site-date {
            font-size: 0.8rem;
        }

        .no-sites {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-sites h2 {
            margin-bottom: 1rem;
            color: #333;
        }

        .add-site-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 1rem;
            transition: transform 0.2s ease;
        }

        .add-site-btn:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .main-content {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .stats {
                margin-left: 0;
                text-align: center;
            }

            .sites-grid {
                grid-template-columns: 1fr;
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
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="submit.php">Submit Site</a>
                <a href="report.php">Report</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Browse Websites</h1>
            <p class="page-subtitle">Discover all the websites in our search index</p>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Category:</label>
                <select class="filter-select" onchange="updateFilters()" id="categoryFilter">
                    <option value="all" <?php echo $category === 'all' || $category === '' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Sort by:</label>
                <select class="filter-select" onchange="updateFilters()" id="sortFilter">
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Alphabetical</option>
                </select>
            </div>

            <div class="stats">
                Showing <?php echo count($sites); ?> websites
            </div>
        </div>

        <div class="sites-grid">
            <?php if (empty($sites)): ?>
                <div class="no-sites" style="grid-column: 1 / -1;">
                    <h2>No websites found</h2>
                    <p>Be the first to add a website to our index!</p>
                    <a href="submit.php" class="add-site-btn">Submit Website</a>
                </div>
            <?php else: ?>
                <?php foreach ($sites as $site): ?>
                    <div class="site-card">
                        <h3 class="site-title">
                            <a href="<?php echo htmlspecialchars($site['url']); ?>" target="_blank" rel="noopener">
                                <?php echo htmlspecialchars($site['title']); ?>
                            </a>
                        </h3>
                        <div class="site-url"><?php echo htmlspecialchars($site['url']); ?></div>
                        <p class="site-description"><?php echo htmlspecialchars($site['description']); ?></p>
                        <div class="site-meta">
                            <span class="site-category"><?php echo htmlspecialchars($site['category']); ?></span>
                            <span class="site-date">Added <?php echo date('M j, Y', strtotime($site['submitted_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function updateFilters() {
            const category = document.getElementById('categoryFilter').value;
            const  {
            const category = document.getElementById('categoryFilter').value;
            const sort = document.getElementById('sortFilter').value;
            
            const url = new URL(window.location);
            url.searchParams.set('category', category);
            url.searchParams.set('sort', sort);
            
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
