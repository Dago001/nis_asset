<?php
/**
 * Core Notification Manager
 */
class Notification {
    
    /**
     * Send in-app notification to a specific user.
     * Gated by "Notification → Enable system notifications" — sendToRole()/
     * sendToRoleInCommand() route through here too, so this is the single
     * choke point for all in-app notification creation.
     */
    public static function send($userId, $message, $link = null) {
        if (class_exists('Config') && !Config::get('enable_notifications', true)) {
            return false;
        }
        return Database::insert('notifications', [
            'user_id' => $userId,
            'message' => $message,
            'link' => $link,
            'is_read' => 0
        ]);
    }
    
    /**
     * Send in-app notification to all users holding a specific role
     */
    public static function sendToRole($roleName, $message, $link = null) {
        $users = Database::fetchAll("
            SELECT DISTINCT u.id 
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE (r.role_name = ? OR u.user_role_type = ?) AND u.is_active = 1
        ", [$roleName, $roleName]);
        
        if ($users) {
            foreach ($users as $user) {
                self::send($user['id'], $message, $link);
            }
        }
    }

    /**
     * Send in-app notification to all users of a specific role inside a specific command
     */
    public static function sendToRoleInCommand($roleName, $commandId, $message, $link = null) {
        $users = Database::fetchAll("
            SELECT DISTINCT u.id 
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE (r.role_name = ? OR u.user_role_type = ?) AND u.command_id = ? AND u.is_active = 1
        ", [$roleName, $roleName, $commandId]);
        
        if ($users) {
            foreach ($users as $user) {
                self::send($user['id'], $message, $link);
            }
        }
    }
    
    /**
     * Get all unread notifications for a user
     */
    public static function getUnread($userId) {
        return Database::fetchAll("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC
        ", [$userId]) ?: [];
    }
}
