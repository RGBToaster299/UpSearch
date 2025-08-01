<?php
// UpSearch - Report Website
session_start();

$config = [
    'discord_webhook' => '', // Add your Discord webhook URL here
];

$message = '';
$error = '';

// Create directories if they don't exist
if (!is_dir('reports')) {
    mkdir('reports', 0755, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot spam protection
    if (!empty($_POST['website'])) {
        $error = 'Spam detected.';
    } else {
        // Validate input
        $url = filter_var(trim($_POST['url']), FILTER_VALIDATE_URL);
        $reason = trim($_POST['reason']);
        $details = trim($_POST['details']);
        
        if (!$url) {
            $error = 'Please enter a valid URL.';
        } elseif (empty($reason)) {
            $error = 'Please select a reason for reporting.';
        } elseif (strlen($details) > 1000) {
            $error = 'Details must be under 1000 characters.';
        } else {
            // Create report data
            $reportData = [
                'id' => uniqid(),
                'url' => $url,
                'reason' => $reason,
                'details' => $details,
                'reported_at' => date('Y-m-d H:i:s'),
                'reported_by_ip' => $_SERVER['REMOTE_ADDR'],
                'status' => 'pending'
            ];
            
            // Save to file
            $reportFile = 'reports/report_' . $reportData['id'] . '.json';
            if (file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT))) {
                // Send to Discord webhook if configured
                if (!empty($config['discord_webhook'])) {
                    $discordData = [
                        'embeds' => [[
                            'title' => '🚨 Website Report',
                            'color' => 0xff6b6b,
                            'fields' => [
                                ['name' => 'URL', 'value' => $url, 'inline' => false],
                                ['name' => 'Reason', 'value' => $reason, 'inline' => true],
                                ['name' => 'Details', 'value' => $details ?: 'No additional details provided', 'inline' => false],
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
                
                $message = 'Report submitted successfully. Thank you for helping keep our index clean!';
                
                // Clear form
                $_POST = [];
            } else {
                $error = 'Failed to save report. Please try again.';
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
    <title>Report Website - UpSearch</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
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
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            color: #856404;
        }

        .warning-box h3 {
            margin-bottom: 0.5rem;
            color: #856404;
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
                <a href="submit.php">Submit</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="form-container">
            <h1 class="form-title">Report Website</h1>
            <p class="form-subtitle">Help us maintain a clean and safe search index</p>

            <div class="warning-box">
                <h3>⚠️ Important</h3>
                <p>Only report websites that violate our guidelines or contain inappropriate content. False reports may result in restrictions.</p>
            </div>

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
                    <div class="form-help">The URL of the website you want to report</div>
                </div>

                <div class="form-group">
                    <label for="reason" class="form-label">Reason for Report *</label>
                    <select id="reason" name="reason" class="form-select" required>
                        <option value="">Select a reason</option>
                        <option value="Spam" <?php echo ($_POST['reason'] ?? '') === 'Spam' ? 'selected' : ''; ?>>Spam</option>
                        <option value="Malware" <?php echo ($_POST['reason'] ?? '') === 'Malware' ? 'selected' : ''; ?>>Malware/Virus</option>
                        <option value="Phishing" <?php echo ($_POST['reason'] ?? '') === 'Phishing' ? 'selected' : ''; ?>>Phishing</option>
                        <option value="Adult Content" <?php echo ($_POST['reason'] ?? '') === 'Adult Content' ? 'selected' : ''; ?>>Adult Content</option>
                        <option value="Hate Speech" <?php echo ($_POST['reason'] ?? '') === 'Hate Speech' ? 'selected' : ''; ?>>Hate Speech</option>
                        <option value="Copyright" <?php echo ($_POST['reason'] ?? '') === 'Copyright' ? 'selected' : ''; ?>>Copyright Violation</option>
                        <option value="Broken Link" <?php echo ($_POST['reason'] ?? '') === 'Broken Link' ? 'selected' : ''; ?>>Broken Link</option>
                        <option value="Misleading" <?php echo ($_POST['reason'] ?? '') === 'Misleading' ? 'selected' : ''; ?>>Misleading Content</option>
                        <option value="Other" <?php echo ($_POST['reason'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="details" class="form-label">Additional Details (Optional)</label>
                    <textarea 
                        id="details" 
                        name="details" 
                        class="form-textarea" 
                        placeholder="Please provide any additional information about why you're reporting this website..."
                        maxlength="1000"
                    ><?php echo htmlspecialchars($_POST['details'] ?? ''); ?></textarea>
                    <div class="form-help">Optional: Provide more context about the issue (max 1000 characters)</div>
                </div>

                <button type="submit" class="submit-button">Submit Report</button>
            </form>
        </div>
    </main>

    <script>
        // Character counter for details
        const details = document.getElementById('details');
        const maxLength = 1000;
        
        details.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            const helpText = this.nextElementSibling;
            helpText.textContent = `Optional: Provide more context about the issue (${remaining} characters remaining)`;
            
            if (remaining < 100) {
                helpText.style.color = remaining < 0 ? '#dc3545' : '#ffc107';
            } else {
                helpText.style.color = '#666';
            }
        });
    </script>
</body>
</html>
