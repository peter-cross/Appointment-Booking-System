<?php

function crypt_string( $str, $hash1, $hash2 )
{
    echo "<br>Crypting...";
        
    echo "<br>Original string: " . $str;
    
    $key = substr($hash1, 0, 16) . substr($hash2, 0, 16);
    echo "<br>Key: " . $key;
    
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
    echo "<br>IV size: " . $iv_size;
    
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    echo "<br>IV: " . $iv;
    
    $res =  mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_CBC, $iv);
    echo "<br>Encrypted string: " . $res;
    
    echo "<br>Output string: " . $res . $iv;
    
    return $res . $iv ;
}

function decrypt_string( $str, $hash1, $hash2 )
{
    echo "<br>Decrypting...";
    
    echo "<br>Original string: " . $str;
    
    $key = substr($hash1, 0, 16) . substr($hash2, 0, 16);
    echo "<br>Key: " . $key;
    
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
    echo "<br>IV size: " . $iv_size;
    
    $iv = substr($str, strlen($str)-$iv_size, $iv_size);
    echo "<br>IV: " . $iv;
    
    $str = substr($str, 0, strlen($str)-$iv_size);
    echo "<br>Encrypted string: " . $str;
    
    $res = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_CBC, $iv);
    echo "<br>Decrypted string: " . $res;
    
    return $res;
}

$str = "The string to crypt";
$hash1 = "7s0h7n6x8zhvzvt5iz130xa4hbc6nosugsbn";
$hash2 = "gikoh2jgxdlgcnkdkuh16u8ui0ozt0m9i7yz";

echo "<br>String: " . $str;
echo "<br>Hash 1 :" . $hash1;
echo "<br>Hash 2 :" . $hash2;

echo "<hr>";
$str_crypted = crypt_string( $str, $hash1, $hash2 );

echo "<hr>";
$str_decrypted = decrypt_string( $str_crypted, $hash1, $hash2 );

?>