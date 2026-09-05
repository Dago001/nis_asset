<?php
/**
 * Database Backup Script
 * Run via cron: 0 2 * * * php /var/www/nis-ams/scripts/backup.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script may only be run from the command line.');
}

require_once __DIR__ . '/../config/init.php';

class DatabaseBackup {

    private $backupDir;
    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPass;
    private $retentionDays;
    private $enabled;

    public function __construct() {
        $this->backupDir = Config::get('upload_path') . '../backups/';
        $this->dbHost = Config::get('db_host');
        $this->dbName = Config::get('db_name');
        $this->dbUser = Config::get('db_user');
        $this->dbPass = Config::get('db_pass');
        // "Backup → Retention (days)" / "Enable automatic database backups".
        // Frequency (daily/weekly/monthly) isn't something this script can
        // enforce on itself — it governs how often the OS scheduler (cron /
        // Windows Task Scheduler) should invoke this file; this script just
        // honors enabled/retention each time it's run.
        $this->retentionDays = (int) Config::get('backup_retention', 30);
        if ($this->retentionDays < 1) $this->retentionDays = 30;
        $this->enabled = (bool) Config::get('backup_enabled', true);

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function run() {
        if (!$this->enabled) {
            $this->log("Skipped: automatic backups are disabled (Settings → Backup → Enable automatic database backups).");
            return;
        }
        $this->log("Starting database backup...");
        
        $filename = $this->dbName . '_' . date('Y-m-d_H-i-s') . '.sql.gz';
        $filepath = $this->backupDir . $filename;
        
        // Create backup
        $command = sprintf(
            'mysqldump -h %s -u %s %s | gzip > %s',
            escapeshellarg($this->dbHost),
            escapeshellarg($this->dbUser),
            escapeshellarg($this->dbName),
            escapeshellarg($filepath)
        );
        
        if (!empty($this->dbPass)) {
            $command = 'MYSQL_PWD=' . escapeshellarg($this->dbPass) . ' ' . $command;
        }
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->log("Backup created successfully: $filename");
            $this->cleanOldBackups();
        } else {
            $this->log("Backup failed: " . implode("\n", $output), 'ERROR');
        }
    }
    
    private function cleanOldBackups() {
        $files = glob($this->backupDir . $this->dbName . '_*.sql.gz');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = filemtime($file);
                $ageDays = ($now - $fileTime) / (60 * 60 * 24);
                
                if ($ageDays > $this->retentionDays) {
                    unlink($file);
                    $this->log("Deleted old backup: " . basename($file));
                }
            }
        }
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        
        echo $logMessage;
        
        $logFile = BASE_PATH . '/logs/backup.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// Run backup
$backup = new DatabaseBackup();
$backup->run();