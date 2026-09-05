<?php
/**
 * System Cleanup Script
 * Run via cron: 0 3 * * * php /var/www/nis-ams/scripts/cleanup.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

require_once __DIR__ . '/../config/init.php';

class SystemCleanup {
    
    private $logFile;
    private $retentionDays = [
        'audit_logs' => 365,
        'user_sessions' => 30,
        'temp_uploads' => 7,
        'request_logs' => 30
    ];
    
    public function __construct() {
        $this->logFile = BASE_PATH . '/logs/cleanup.log';
    }
    
    public function run() {
        $this->log("Starting system cleanup...");
        
        $this->cleanAuditLogs();
        $this->cleanUserSessions();
        $this->cleanRequestLogs();
        $this->cleanTempUploads();
        
        $this->log("System cleanup completed.");
    }
    
    private function cleanAuditLogs() {
        $cutoff = date('Y-m-d', strtotime("-{$this->retentionDays['audit_logs']} days"));
        
        $count = Database::delete(
            'audit_logs',
            'created_at < ?',
            [$cutoff]
        );
        
        $this->log("Cleaned $count audit log records older than {$this->retentionDays['audit_logs']} days");
    }
    
    private function cleanUserSessions() {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays['user_sessions']} days"));
        
        $count = Database::delete(
            'user_sessions',
            'last_activity < ?',
            [$cutoff]
        );
        
        $this->log("Cleaned $count expired user sessions");
    }
    
    private function cleanRequestLogs() {
        if (!Database::tableExists('request_log')) {
            return;
        }
        
        $cutoff = date('Y-m-d', strtotime("-{$this->retentionDays['request_logs']} days"));
        
        $count = Database::delete(
            'request_log',
            'created_at < ?',
            [$cutoff]
        );
        
        $this->log("Cleaned $count request log records");
    }
    
    private function cleanTempUploads() {
        $tempDir = Config::get('upload_path') . 'temp/';
        
        if (!is_dir($tempDir)) {
            return;
        }
        
        $files = glob($tempDir . '*');
        $cutoff = time() - ($this->retentionDays['temp_uploads'] * 24 * 60 * 60);
        $count = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                unlink($file);
                $count++;
            }
        }
        
        $this->log("Cleaned $count temporary files older than {$this->retentionDays['temp_uploads']} days");
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}

// Run cleanup
$cleanup = new SystemCleanup();
$cleanup->run();