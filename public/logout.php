<?php

require_once __DIR__ . '/../app/auth.php';

logoutUser();
flash_message('success', 'Anda berhasil logout.', 'success');
redirect('login.php');