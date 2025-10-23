<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/Ticket.php';
require_once __DIR__ . '/../classes/PDFGenerator.php';
SessionManager::startSession();
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$currentUser = SessionManager::getCurrentUser();
$ticketId = $_GET['ticket'] ?? null;
if (!$ticketId) {
    header('Location: profile.php?error=invalid_ticket');
    exit;
}
$ticket = new Ticket();
$pdfGenerator = new PDFGenerator();
$userId = SessionManager::getCurrentUserId();
if ($userId == '78') {
    $userId = '78fdd259-df09-49bc-9575-117ba6f206e8';
}
$ticketData = $ticket->getTicketById($ticketId, $userId);
if (!$ticketData) {
    $ticketData = $ticket->getTicketById($ticketId);
    if (!$ticketData) {
        header('Location: profile.php?error=ticket_not_found');
        exit;
    }
    if ($ticketData['user_id'] != $userId) {
        header('Location: profile.php?error=not_your_ticket');
        exit;
    }
}
$pdfResult = $pdfGenerator->generateTicketPDF($ticketData);
if (!$pdfResult['success']) {
    header('Location: profile.php?error=pdf_generation_failed');
    exit;
}
$filepath = $pdfResult['filepath'];
$filename = 'bilet_' . substr($ticketId, -8) . '.html';
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
readfile($filepath);
$pdfGenerator->cleanupTempFiles(24);
exit;