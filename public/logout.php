<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
app_start_session(); $_SESSION=[]; session_destroy(); redirect('guide.php');
