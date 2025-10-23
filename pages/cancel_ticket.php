<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../classes/Ticket.php';
SessionManager::startSession();
SessionManager::requireLogin();
$ticketId = $_GET['id'] ?? null;
if (!$ticketId) {
    header('Location: my_tickets.php?error=invalid_ticket');
    exit;
}
$ticket = new Ticket();
$userId = SessionManager::getCurrentUserId();
if ($userId == '78') {
    $userId = '78fdd259-df09-49bc-9575-117ba6f206e8';
}
if (!$ticket->canCancelTicket($ticketId)) {
    header('Location: my_tickets.php?error=cannot_cancel&reason=time_limit');
    exit;
}
$result = $ticket->cancelTicket($ticketId, $userId);
if ($result['success']) {
    header('Location: my_tickets.php?success=ticket_cancelled&refund=' . $result['refundAmount']);
} else {
    header('Location: my_tickets.php?error=cancel_failed&message=' . urlencode($result['message']));
}
exit;
