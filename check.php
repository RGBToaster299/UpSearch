<?php
// UpSearch - Site Status Checker (CLI only)

// Ensure this script is only run from command line
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

echo "UpSearch Site Status Checker\n";
echo "============================\n\n";

// Check if savedSites directory exists
if (!is_dir('savedSites')) {
    echo "Error: savedSites directory not found.\n";
    exit(1);
}

// Get all site files
$files = glob('savedSites/*.json');
$totalSites = 0;
$checkedSites = 0;
$removedSites = 0;
$errorSites = 0;

echo "Found " . count($files) . " site files to check.\n\n";

foreach ($files as $file) {
    // Skip cooldown files
    if (strpos($file, '.cooldown_') !== false) {
        continue;
    }
    
    $totalSites++;
    
    // Load site data
    $content = file_get_contents($file);
    $site = json_decode($content, true);
    
    if (!$site || !isset($site['url'])) {
        echo "⚠️  Invalid site data in {$file}, skipping.\n";
        continue;
    }
    
    $url = $site['url'];
    $title = $site['title'] ?? 'Unknown';
    
    echo "Checking: {$title} ({$url})... ";
    
    // Check site status
    $statusCode = checkSiteStatus($url);
    $checkedSites++;
    
    if ($statusCode === false) {
        echo "❌ Connection failed - REMOVED\n";
        unlink($file);
        $removedSites++;
    } elseif ($statusCode >= 400) {
        echo "❌ HTTP {$statusCode} - REMOVED\n";
        unlink($file);
        $removedSites++;
    } elseif ($statusCode >= 300) {
        echo "⚠️  HTTP {$statusCode} - KEPT (redirect)\n";
    } elseif ($statusCode >= 200 && $statusCode < 300) {
        echo "✅ HTTP {$statusCode} - OK\n";
    } else {
        echo "⚠️  HTTP {$statusCode} - KEPT (unknown status)\n";
        $errorSites++;
    }
    
    // Small delay to be respectful to servers
    usleep(500000); // 0.5 seconds
}

echo "\n============================\n";
echo "Check completed!\n";
echo "Total sites: {$totalSites}\n";
echo "Checked: {$checkedSites}\n";
echo "Removed: {$removedSites}\n";
echo "Errors: {$errorSites}\n";
echo "Remaining: " . ($totalSites - $removedSites) . "\n";

/**
 * Check the HTTP status code of a URL
 * @param string $url The URL to check
 * @return int|false The HTTP status code or false on failure
 */
function checkSiteStatus($url) {
    // Set up cURL
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false, // Don't follow redirects automatically
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'UpSearch-Bot/1.0 (Site Status Checker)',
        CURLOPT_NOBODY => true, // HEAD request only
        CURLOPT_SSL_VERIFYPEER => false, // Allow self-signed certificates
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_MAXREDIRS => 0,
    ]);
    
    curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // If cURL failed, try with get_headers as fallback
    if ($statusCode === 0 || !empty($error)) {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 10,
                'user_agent' => 'UpSearch-Bot/1.0 (Site Status Checker)',
                'ignore_errors' => true,
            ]
        ]);
        
        $headers = @get_headers($url, 1, $context);
        
        if ($headers === false) {
            return false;
        }
        
        // Extract status code from first header
        if (isset($headers[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $matches);
            return isset($matches[1]) ? (int)$matches[1] : false;
        }
        
        return false;
    }
    
    return $statusCode;
}
?>
