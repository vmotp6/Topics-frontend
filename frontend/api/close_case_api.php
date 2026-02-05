<?php
/**
 * 本功能已移除：不再支援「結案 / 意願追蹤」。
 */
session_name('KANGNING_SESSION');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

http_response_code(410);
echo json_encode(['success' => false, 'message' => '此功能已移除（不再支援結案）']);
