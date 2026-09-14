<?php
final class AdminAudit {
    public static function log(Database $database, int $adminId, string $action, string $targetType, ?int $targetId, string $details = ''): void {
        $database->execute('INSERT INTO phpretro_admin_action_log (admin_id, action_type, target_type, target_id, details, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [$adminId, $action, $targetType, $targetId, $details, $_SERVER['REMOTE_ADDR'] ?? '', time()]);
    }
}
