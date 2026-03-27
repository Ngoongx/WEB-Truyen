<?php
declare(strict_types=1);

require_once __DIR__ . '/app/includes/bootstrap.php';

logout_user();
set_flash('success', 'Bạn đã đăng xuất.');
redirect('index.php');
