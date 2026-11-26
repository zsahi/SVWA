<?php
// Keep SVWA sessions separate from other PHP apps on the same host.
session_name('SVWASESSID');
session_start(['use_strict_mode'=>true, 'cookie_httponly'=>true, 'cookie_samesite'=>'Lax', 'cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off']);
