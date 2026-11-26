<?php
require_once __DIR__.'/reset-lab.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetRequested) {
    account_post();
    $scope = field($_POST, 'scope');
    if (!in_array($scope, ['guestbook', 'labs'], true) || field($_POST, 'confirm') !== 'yes') {
        http_response_code(400);
        $error = 'Choose a reset option and confirm that its data can be deleted.';
    } else {
        try {
            reset_lab_data(db(), $scope);
            $_SESSION['reset_notice'] = $scope === 'guestbook'
                ? 'Stored XSS guestbook messages cleared.'
                : 'Lab data reset. SQL exercise records restored and Stored XSS messages cleared.';
            account_redirect($resetReturnPath);
        } catch (Throwable $exception) {
            error_log('SVWA lab reset failed: '.$exception->getMessage());
            http_response_code(500);
            $error = 'The reset failed. No changes were saved. Check the database connection and server logs.';
        }
    }
}
$notice = $_SESSION['reset_notice'] ?? '';
unset($_SESSION['reset_notice']);
