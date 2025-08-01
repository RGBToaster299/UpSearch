<?php
// UpSearch - Submit Website
session_start();

$config = [
    'discord_webhook' => '', // Add your Discord webhook URL here
    'cooldown_minutes' => 5, // Cooldown between submissions from same IP
];

$message = '';
$error = '';

// Create directories if they don't exist
if (!is_dir('savedSites')) {
    mkdir('savedSites', 0755, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot spam protection
    if (!empty($_POST['website'])) {
        $error = 'Spam detected.';
    } else {
        // Validate input
        $url = filter_var(trim($_POST['url']), FILTER_VALIDATE_URL);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category = trim($_POST['category']);
        $screenshot = filter_var(trim($_POST['screenshot']), FILTER_VALIDATE_URL);
        
        if (!$url) {
            $error = 'Please enter a valid URL.';
        } elseif (empty($title) || strlen($title) > 100) {
            $error = 'Title is required and must be under 100 characters.';
        } elseif (empty($description) || strlen($description) > 500) {
            $error = 'Description is required and must be under 500 characters.';
        } elseif (empty($category)) {
            $error = 'Please select a category.';
        } else {
            // Check cooldown
            $ip = $_SERVER['REMOTE_ADDR'];
            $cooldownFile = 'savedSites/.cooldown_' . md5($ip);
            
            if (file_exists($cooldownFile)) {
                $lastSubmission = filemtime($cooldownFile);
                $cooldownEnd = $lastSubmission + ($config['cooldown_minutes'] * 60);
                
                if (time() < $cooldownEnd) {
                    $remainingMinutes = ceil(($cooldownEnd - time()) / 60);
                    $error = "Please wait {$remainingMinutes} minutes before submitting again.";
                }
            }
            
            if (empty($error)) {
                // Check if URL already exists
                $urlHash = md5($url);
                $existingFile = "savedSites/{$urlHash}.json";
                
                if (file_exists($existingFile)) {
                    $error = 'This website has already been submitted.';
                } else {
                    // Create site data
                    $siteData = [
                        'id' => $urlHash,
                        'url' => $url,
                        'title' => $title,
                        'description' => $description,
                        'category' => $category,
                        'screenshot' => $screenshot ?: '',
                        'submitted_at' => date('Y-m-d H:i:s'),
                        'submitted_by_ip' => $ip,
                        'status' => 'pending'
                    ];
                    
                    // Save to file
                    if (file_put_contents($existingFile, json_encode($siteData, JSON_PRETTY_PRINT))) {
                        // Update cooldown
                        touch($cooldownFile);
                        
                        // Send to Discord webhook if configured
                        if (!empty($config['discord_webhook'])) {
                            $discordData = [
                                'embeds' => [[
                                    'title' => 'New Website Submission',
                                    'color' => 0x667eea,
                                    'fields' => [
                                        ['name' => 'Title', 'value' => $title, 'inline' => true],
                                        ['name' => 'URL', 'value' => $url, 'inline' => true],
                                        ['name' => 'Category', 'value' => $category, 'inline' => true],
                                        ['name' => 'Description', 'value' => $description, 'inline' => false],
                                    ],
                                    'timestamp' => date('c')
                                ]]
                            ];
                            
                            $ch = curl_init($config['discord_webhook']);
                            curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($discordData));
                            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_exec($ch);
                            curl_close($ch);
                        }
                        
                        $message = 'Website submitted successfully! It will appear in search results shortly.';
                        
                        // Clear form
                        $_POST = [];
                    } else {
                        $error = 'Failed to save submission. Please try again.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Website - UpSearch</title>
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
            color: #333;
        }

        .header {
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
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

        .main-content {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-subtitle {
            color: #666;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-help {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .honeypot {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }

        .submit-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            width: 100%;
        }

        .submit-button:hover {
            transform: translateY(-2px);
        }

        .submit-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .main-content {
                padding: 0 1rem;
            }

            .form-container {
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
        <div class="header-content">
            <a href="index.php" class="logo">UpSearch</a>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="list.php">Browse Sites</a>
                <a href="report.php">Report</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="form-container">
            <h1 class="form-title">Submit Website</h1>
            <p class="form-subtitle">Add your website to our search index</p>

            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Honeypot field -->
                <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">

                <div class="form-group">
                    <label for="url" class="form-label">Website URL *</label>
                    <input 
                        type="url" 
                        id="url" 
                        name="url" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($_POST['url'] ?? ''); ?>"
                        placeholder="https://example.com"
                        required
                    >
                    <div class="form-help">The full URL of your website</div>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">Website Title *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                        placeholder="My Awesome Website"
                        maxlength="100"
                        required
                    >
                    <div class="form-help">A clear, descriptive title (max 100 characters)</div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description *</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-textarea" 
                        placeholder="Describe what your website is about..."
                        maxlength="500"
                        required
                    ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <div class="form-help">A brief description of your website (max 500 characters)</div>
                </div>

                <div class="form-group">
                    <label for="category" class="form-label">Category *</label>
                    <select id="category" name="category" class="form-select" required>
                        <option value="">Select a category</option>
                        <option value="Technology" <?php echo ($_POST['category'] ?? '') === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                        <option value="Business" <?php echo ($_POST['category'] ?? '') === 'Business' ? 'selected' : ''; ?>>Business</option>
                        <option value="Education" <?php echo ($_POST['category'] ?? '') === 'Education' ? 'selected' : ''; ?>>Education</option>
                        <option value="Entertainment" <?php echo ($_POST['category'] ?? '') === 'Entertainment' ? 'selected' : ''; ?>>Entertainment</option>
                        <option value="News" <?php echo ($_POST['category'] ?? '') === 'News' ? 'selected' : ''; ?>>News</option>
                        <option value="Health" <?php echo ($_POST['category'] ?? '') === 'Health' ? 'selected' : ''; ?>>Health</option>
                        <option value="Sports" <?php echo ($_POST['category'] ?? '') === 'Sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="Travel" <?php echo ($_POST['category'] ?? '') === 'Travel' ? 'selected' : ''; ?>>Travel</option>
                        <option value="Food" <?php echo ($_POST['category'] ?? '') === 'Food' ? 'selected' : ''; ?>>Food</option>
                        <option value="Art" <?php echo ($_POST['category'] ?? '') === 'Art' ? 'selected' : ''; ?>>Art</option>
                        <option value="Other" <?php echo ($_POST['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="screenshot" class="form-label">Screenshot URL (Optional)</label>
                    <input 
                        type="url" 
                        id="screenshot" 
                        name="screenshot" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($_POST['screenshot'] ?? ''); ?>"
                        placeholder="https://example.com/screenshot.png"
                    >
                    <div class="form-help">Optional: URL to a screenshot of your website</div>
                </div>

                <button type="submit" class="submit-button">Submit Website</button>
            </form>
        </div>
    </main>

    <script>
        // Character counter for description
        const description = document.getElementById('description');
        const maxLength = 500;
        
        description.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            const helpText = this.nextElementSibling;
            helpText.textContent = `A brief description of your website (${remaining} characters remaining)`;
            
            if (remaining < 50) {
                helpText.style.color = remaining < 0 ? '#dc3545' : '#ffc107';
            } else {
                helpText.style.color = '#666';
            }
        });

        // Title character counter
        const title = document.getElementById('title');
        const titleMaxLength = 100;
        
        title.addEventListener('input', function() {
            const remaining = titleMaxLength - this.value.length;
            const helpText = this.nextElementSibling;
            helpText.textContent = `A clear, descriptive title (${remaining} characters remaining)`;
            
            if (remaining < 10) {
                helpText.style.color = remaining < 0 ? '#dc3545' : '#ffc107';
            } else {
                helpText.style.color = '#666';
            }
        });
    </script>
</body>
</html>
