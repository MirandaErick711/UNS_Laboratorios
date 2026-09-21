<?php

$hash = '$2y$12$j5cl.msarp2EIL8flu.wOOCmTvZHMnQZ0VaUYOGqUkyGj4N31pqha';

if (password_verify('Docente123', $hash)) {
    echo 'CONTRASENA CORRECTA';
} else {
    echo 'CONTRASENA INCORRECTA';
}