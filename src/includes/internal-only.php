<?php
// Trust the actual peer address, never client-supplied forwarding headers.
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) {
    http_response_code(403);
    exit("Internal service: loopback access only.\n");
}
