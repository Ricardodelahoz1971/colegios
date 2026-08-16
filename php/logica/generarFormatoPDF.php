<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth.php';
guardia_sesion();
throw new Exception("Motor TCPDF deprecado en favor de generador PDF del lado del cliente.");
