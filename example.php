<?php
$password = "telecare@123";
$hashpassword = password_hash($password, PASSWORD_DEFAULT);
echo $hashpassword;
















?>