<?php 
    $hashed_password = password_hash(password: 'admin', algo: PASSWORD_DEFAULT);
    echo $hashed_password;
    
?>