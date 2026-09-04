<?php
$hash = '$2y$12$JScTDxSizVTbXr2rNAzBsOcfGdrXGDqZ9WdR25UZMuf1M2By2Kp9.';
if (password_verify('admin123', $hash)) {
    echo "CORRETO";
} else {
    echo "ERRADO";
}
