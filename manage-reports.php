<?php
// UpSearch - Interactive Report Management (CLI only)

// Ensure this script is only run from command line
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be run from the command line.');
}

// ANSI color codes for better CLI output
class Colors {
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const BOLD = "\033[1m";
    const RESET = "\033[0m";
}

class ReportManager {
    private $reportsDir = 'reports';
    private $sitesDir = 'savedSites';
    
    public function __construct() {
        if (!is_dir($this->reportsDir)) {
            mkdir($this->reportsDir, 0755, true);
        }
        if (!is_dir($this->sitesDir)) {
            mkdir($this->sitesDir, 0755, true);
        }
    }
    
    public function run() {
        $this->showHeader();
        
        while (true) {
            $this->showMainMenu();
            $choice = $this->getInput("Enter your choice: ");
            
            switch ($choice) {
                case '1':
                    $this->reviewReports();
                    break;
                case '2':
                    $this->showReportStats();
                    break;
                case '3':
                    $this->searchReports();
                    break;
                case '4':
                    $this->bulkActions();
                    break;
                case '5':
                    $this->cleanupProcessedReports();
                    break;
                case '0':
                case 'q':
                case 'quit':
                case 'exit':
                    $this->output(Colors::GREEN . "Goodbye!" . Colors::RESET);
                    exit(0);
                default:
                    $this->output(Colors::RED . "Invalid choice. Please try again." . Colors::RESET);
            }
            
            $this->output("\n" . str_repeat("-", 60) . "\n");
        }
    }
    
    private function showHeader() {
        $this->output(Colors::BOLD . Colors::CYAN . "
╔══════════════════════════════════════════════════════════╗
║                    UpSearch Report Manager               ║
║                  Interactive CLI Tool                    ║
╚══════════════════════════════════════════════════════════╝
" . Colors::RESET);
    }
    
    private function showMainMenu() {
        $pendingCount = count($this->getPendingReports());
        $processedCount = count($this->getProcessedReports());
        
        $this->output(Colors::BOLD . "Main Menu:" . Colors::RESET);
        $this->output("1. Review pending reports (" . Colors::YELLOW . $pendingCount . Colors::RESET . " pending)");
        $this->output("2. Show report statistics");
        $this->output("3. Search reports");
        $this->output("4. Bulk actions");
        $this->output("5. Cleanup processed reports (" . Colors::GREEN . $processedCount . Colors::RESET . " processed)");
        $this->output("0. Exit");
        $this->output("");
    }
    
    private function reviewReports() {
        $reports = $this->getPendingReports();
        
        if (empty($reports)) {
            $this->output(Colors::GREEN . "✅ No pending reports to review!" . Colors::RESET);
            return;
        }
        
        $this->output(Colors::BOLD . "Reviewing " . count($reports) . " pending reports..." . Colors::RESET . "\n");
        
        foreach ($reports as $index => $report) {
            $this->output(Colors::CYAN . "Report " . ($index + 1) . " of " . count($reports) . Colors::RESET);
            $this->showReportDetails($report);
            
            $action = $this->getReportAction();
            
            switch ($action) {
                case 'r':
                case 'remove':
                    $this->removeSiteFromIndex($report);
                    $this->markReportAsProcessed($report, 'approved');
                    break;
                case 'k':
                case 'keep':
                    $this->markReportAsProcessed($report, 'rejected');
                    break;
                case 's':
                case 'skip':
                    continue 2;
                case 'q':
                case 'quit':
                    return;
                default:
                    $this->output(Colors::RED . "Invalid action. Skipping report." . Colors::RESET);
                    continue 2;
            }
            
            $this->output("");
        }
        
        $this->output(Colors::GREEN . "✅ Finished reviewing all pending reports!" . Colors::RESET);
    }
    
    private function showReportDetails($report) {
        $reportData = json_decode(file_get_contents($report), true);
        $siteExists = $this->findSiteInIndex($reportData['url']);
        
        $this->output(Colors::BOLD . "📋 Report Details:" . Colors::RESET);
        $this->output("   URL: " . Colors::BLUE . $reportData['url'] . Colors::RESET);
        $this->output("   Reason: " . Colors::YELLOW . $reportData['reason'] . Colors::RESET);
        $this->output("   Details: " . ($reportData['details'] ?: 'No additional details'));
        $this->output("   Reported: " . Colors::MAGENTA . $reportData['reported_at'] . Colors::RESET);
        $this->output("   Status: " . ($siteExists ? Colors::RED . "Site exists in index" : Colors::GREEN . "Site not found in index") . Colors::RESET);
        
        if ($siteExists) {
            $siteData = json_decode(file_get_contents($siteExists), true);
            $this->output(Colors::BOLD . "\n🌐 Site Information:" . Colors::RESET);
            $this->output("   Title: " . $siteData['title']);
            $this->output("   Description: " . substr($siteData['description'], 0, 100) . (strlen($siteData['description']) > 100 ? '...' : ''));
            $this->output("   Added: " . Colors::MAGENTA . $siteData['submitted_at'] . Colors::RESET);
        }
        
        $this->output("");
    }
    
    private function getReportAction() {
        $this->output(Colors::BOLD . "Actions:" . Colors::RESET);
        $this->output("  " . Colors::RED . "r" . Colors::RESET . ") Remove site from index");
        $this->output("  " . Colors::GREEN . "k" . Colors::RESET . ") Keep site (reject report)");
        $this->output("  " . Colors::YELLOW . "s" . Colors::RESET . ") Skip this report");
        $this->output("  " . Colors::CYAN . "q" . Colors::RESET . ") Quit to main menu");
        
        return strtolower(trim($this->getInput("Choose action [r/k/s/q]: ")));
    }
    
    private function removeSiteFromIndex($reportFile) {
        $reportData = json_decode(file_get_contents($reportFile), true);
        $siteFile = $this->findSiteInIndex($reportData['url']);
        
        if ($siteFile && file_exists($siteFile)) {
            $siteData = json_decode(file_get_contents($siteFile), true);
            
            if (unlink($siteFile)) {
                $this->output(Colors::GREEN . "✅ Removed site: " . $siteData['title'] . Colors::RESET);
                
                // Log the removal
                $this->logAction('site_removed', [
                    'url' => $reportData['url'],
                    'title' => $siteData['title'],
                    'reason' => $reportData['reason'],
                    'report_id' => $reportData['id']
                ]);
            } else {
                $this->output(Colors::RED . "❌ Failed to remove site file" . Colors::RESET);
            }
        } else {
            $this->output(Colors::YELLOW . "⚠️  Site not found in index (may have been already removed)" . Colors::RESET);
        }
    }
    
    private function markReportAsProcessed($reportFile, $action) {
        $reportData = json_decode(file_get_contents($reportFile), true);
        $reportData['status'] = 'processed';
        $reportData['action_taken'] = $action;
        $reportData['processed_at'] = date('Y-m-d H:i:s');
        
        file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));
        
        $actionColor = $action === 'approved' ? Colors::RED : Colors::GREEN;
        $actionText = $action === 'approved' ? 'APPROVED (site removed)' : 'REJECTED (site kept)';
        $this->output($actionColor . "📝 Report marked as: " . $actionText . Colors::RESET);
    }
    
    private function showReportStats() {
        $allReports = glob($this->reportsDir . '/report_*.json');
        $pending = $this->getPendingReports();
        $processed = $this->getProcessedReports();
        
        $approved = 0;
        $rejected = 0;
        $reasonStats = [];
        
        foreach ($processed as $reportFile) {
            $data = json_decode(file_get_contents($reportFile), true);
            if ($data['action_taken'] === 'approved') {
                $approved++;
            } else {
                $rejected++;
            }
            
            $reason = $data['reason'];
            $reasonStats[$reason] = ($reasonStats[$reason] ?? 0) + 1;
        }
        
        $this->output(Colors::BOLD . Colors::CYAN . "📊 Report Statistics" . Colors::RESET);
        $this->output(str_repeat("=", 40));
        $this->output("Total Reports: " . Colors::BOLD . count($allReports) . Colors::RESET);
        $this->output("Pending: " . Colors::YELLOW . count($pending) . Colors::RESET);
        $this->output("Processed: " . Colors::GREEN . count($processed) . Colors::RESET);
        $this->output("  ├─ Approved (removed): " . Colors::RED . $approved . Colors::RESET);
        $this->output("  └─ Rejected (kept): " . Colors::GREEN . $rejected . Colors::RESET);
        
        if (!empty($reasonStats)) {
            $this->output("\n" . Colors::BOLD . "Reports by Reason:" . Colors::RESET);
            arsort($reasonStats);
            foreach ($reasonStats as $reason => $count) {
                $this->output("  • $reason: $count");
            }
        }
    }
    
    private function searchReports() {
        $query = $this->getInput("Enter search term (URL, reason, or details): ");
        if (empty($query)) return;
        
        $allReports = glob($this->reportsDir . '/report_*.json');
        $matches = [];
        
        foreach ($allReports as $reportFile) {
            $data = json_decode(file_get_contents($reportFile), true);
            
            if (stripos($data['url'], $query) !== false ||
                stripos($data['reason'], $query) !== false ||
                stripos($data['details'], $query) !== false) {
                $matches[] = $reportFile;
            }
        }
        
        if (empty($matches)) {
            $this->output(Colors::YELLOW . "No reports found matching: " . $query . Colors::RESET);
            return;
        }
        
        $this->output(Colors::GREEN . "Found " . count($matches) . " matching reports:" . Colors::RESET . "\n");
        
        foreach ($matches as $index => $reportFile) {
            $data = json_decode(file_get_contents($reportFile), true);
            $status = $data['status'] ?? 'pending';
            $statusColor = $status === 'pending' ? Colors::YELLOW : Colors::GREEN;
            
            $this->output(($index + 1) . ". " . Colors::BLUE . $data['url'] . Colors::RESET);
            $this->output("   Reason: " . $data['reason']);
            $this->output("   Status: " . $statusColor . ucfirst($status) . Colors::RESET);
            $this->output("   Date: " . $data['reported_at']);
            $this->output("");
        }
    }
    
    private function bulkActions() {
        $this->output(Colors::BOLD . "Bulk Actions:" . Colors::RESET);
        $this->output("1. Remove all sites reported for specific reason");
        $this->output("2. Reject all reports older than X days");
        $this->output("3. Remove all sites with multiple reports");
        $this->output("0. Back to main menu");
        
        $choice = $this->getInput("Choose bulk action: ");
        
        switch ($choice) {
            case '1':
                $this->bulkRemoveByReason();
                break;
            case '2':
                $this->bulkRejectOldReports();
                break;
            case '3':
                $this->bulkRemoveMultipleReports();
                break;
            case '0':
                return;
            default:
                $this->output(Colors::RED . "Invalid choice." . Colors::RESET);
        }
    }
    
    private function bulkRemoveByReason() {
        $reason = $this->getInput("Enter reason to target (e.g., 'Spam', 'Malware'): ");
        if (empty($reason)) return;
        
        $pending = $this->getPendingReports();
        $matches = [];
        
        foreach ($pending as $reportFile) {
            $data = json_decode(file_get_contents($reportFile), true);
            if (stripos($data['reason'], $reason) !== false) {
                $matches[] = $reportFile;
            }
        }
        
        if (empty($matches)) {
            $this->output(Colors::YELLOW . "No pending reports found for reason: " . $reason . Colors::RESET);
            return;
        }
        
        $this->output(Colors::YELLOW . "Found " . count($matches) . " reports for reason: " . $reason . Colors::RESET);
        $confirm = $this->getInput("Remove all these sites? [y/N]: ");
        
        if (strtolower($confirm) === 'y') {
            foreach ($matches as $reportFile) {
                $this->removeSiteFromIndex($reportFile);
                $this->markReportAsProcessed($reportFile, 'approved');
            }
            $this->output(Colors::GREEN . "✅ Processed " . count($matches) . " reports" . Colors::RESET);
        }
    }
    
    private function bulkRejectOldReports() {
        $days = (int)$this->getInput("Reject reports older than how many days? ");
        if ($days <= 0) return;
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $pending = $this->getPendingReports();
        $matches = [];
        
        foreach ($pending as $reportFile) {
            $data = json_decode(file_get_contents($reportFile), true);
            if ($data['reported_at'] < $cutoffDate) {
                $matches[] = $reportFile;
            }
        }
        
        if (empty($matches)) {
            $this->output(Colors::YELLOW . "No reports older than {$days} days found." . Colors::RESET);
            return;
        }
        
        $this->output(Colors::YELLOW . "Found " . count($matches) . " reports older than {$days} days" . Colors::RESET);
        $confirm = $this->getInput("Reject all these reports? [y/N]: ");
        
        if (strtolower($confirm) === 'y') {
            foreach ($matches as $reportFile) {
                $this->markReportAsProcessed($reportFile, 'rejected');
            }
            $this->output(Colors::GREEN . "✅ Rejected " . count($matches) . " old reports" . Colors::RESET);
        }
    }
    
    private function bulkRemoveMultipleReports() {
        $urlCounts = [];
        $pending = $this->getPendingReports();
        
        foreach ($pending as $reportFile) {
            $data = json_decode(file_get_contents($reportFile), true);
            $url = $data['url'];
            $urlCounts[$url] = ($urlCounts[$url] ?? 0) + 1;
        }
        
        $multipleReports = array_filter($urlCounts, function($count) { return $count > 1; });
        
        if (empty($multipleReports)) {
            $this->output(Colors::YELLOW . "No sites with multiple reports found." . Colors::RESET);
            return;
        }
        
        $this->output(Colors::YELLOW . "Sites with multiple reports:" . Colors::RESET);
        foreach ($multipleReports as $url => $count) {
            $this->output("  • $url ($count reports)");
        }
        
        $confirm = $this->getInput("Remove all sites with multiple reports? [y/N]: ");
        
        if (strtolower($confirm) === 'y') {
            $removed = 0;
            foreach ($pending as $reportFile) {
                $data = json_decode(file_get_contents($reportFile), true);
                if (isset($multipleReports[$data['url']])) {
                    $this->removeSiteFromIndex($reportFile);
                    $this->markReportAsProcessed($reportFile, 'approved');
                    $removed++;
                }
            }
            $this->output(Colors::GREEN . "✅ Processed {$removed} reports for multiply-reported sites" . Colors::RESET);
        }
    }
    
    private function cleanupProcessedReports() {
        $processed = $this->getProcessedReports();
        
        if (empty($processed)) {
            $this->output(Colors::YELLOW . "No processed reports to clean up." . Colors::RESET);
            return;
        }
        
        $this->output(Colors::YELLOW . "Found " . count($processed) . " processed reports" . Colors::RESET);
        $confirm = $this->getInput("Delete all processed reports? [y/N]: ");
        
        if (strtolower($confirm) === 'y') {
            $deleted = 0;
            foreach ($processed as $reportFile) {
                if (unlink($reportFile)) {
                    $deleted++;
                }
            }
            $this->output(Colors::GREEN . "✅ Deleted {$deleted} processed reports" . Colors::RESET);
        }
    }
    
    private function getPendingReports() {
        $allReports = glob($this->reportsDir . '/report_*.json');
        $pending = [];
        
        foreach ($allReports as $reportFile) {
            $data = json_decode(file_get_contents($reportFile), true);
            if (!isset($data['status']) || $data['status'] === 'pending') {
                $pending[] = $reportFile;
            }
        }
        
        // Sort by date (oldest first)
        usort($pending, function($a, $b) {
            $dataA = json_decode(file_get_contents($a), true);
            $dataB = json_decode(file_get_contents($b), true);
            return strtotime($dataA['reported_at']) - strtotime($dataB['reported_at']);
        });
        
        return $pending;
    }
    
    private function getProcessedReports() {
        $allReports = glob($this->reportsDir . '/report_*.json');
        $processed = [];
        
        foreach ($allReports as $reportFile) {
            $data = json_decode(file_get_contents($reportFile), true);
            if (isset($data['status']) && $data['status'] === 'processed') {
                $processed[] = $reportFile;
            }
        }
        
        return $processed;
    }
    
    private function findSiteInIndex($url) {
        $urlHash = md5($url);
        $siteFile = $this->sitesDir . '/' . $urlHash . '.json';
        
        if (file_exists($siteFile)) {
            return $siteFile;
        }
        
        // Fallback: search through all site files
        $allSites = glob($this->sitesDir . '/*.json');
        foreach ($allSites as $siteFile) {
            if (strpos($siteFile, '.cooldown_') !== false) continue;
            
            $data = json_decode(file_get_contents($siteFile), true);
            if ($data && $data['url'] === $url) {
                return $siteFile;
            }
        }
        
        return false;
    }
    
    private function logAction($action, $data) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'data' => $data
        ];
        
        $logFile = 'reports/management.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    private function getInput($prompt) {
        echo Colors::WHITE . $prompt . Colors::RESET;
        return trim(fgets(STDIN));
    }
    
    private function output($message) {
        echo $message . "\n";
    }
}

// Run the report manager
$manager = new ReportManager();
$manager->run();
?>
