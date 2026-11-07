<?php
// includes/groups_lib.php: join / leave a study group (used by groups.php and group_view.php).
require_once __DIR__ . '/flash.php';

function group_join(PDO $pdo, int $userId, int $groupId): void
{
    $pdo->prepare('INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)')->execute([$groupId, $userId]);
    set_flash('success', 'You joined the group.');
}

function group_leave(PDO $pdo, int $userId, int $groupId): void
{
    $stmt = $pdo->prepare('SELECT creator_id FROM study_groups WHERE id = ?');
    $stmt->execute([$groupId]);
    $creator = $stmt->fetchColumn();

    if ((int)$creator === $userId) {
        set_flash('danger', 'You created this group, so you cannot leave it. You can delete the group instead.');
        return;
    }
    $pdo->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?')->execute([$groupId, $userId]);
    set_flash('success', 'You left the group.');
}
