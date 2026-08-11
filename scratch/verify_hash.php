<?php
declare(strict_types=1);

$hash = '$2y$10$3fUV64sP8vODgnOa1jwvjeDYwzstXw62yzffu5kP4JntnwzEQDpPu';

if (password_verify('coordinador', $hash)) {
    echo "The current hash in DB matches the password 'coordinador'.\n";
} else {
    echo "The current hash in DB does NOT match the password 'coordinador'.\n";
}
