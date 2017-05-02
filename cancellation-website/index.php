<?php

require_once('assets/php/PHPMailerAutoload.php');
require_once('assets/php/db_info.php');


$system_email = "";
$mail_host = "";
$mail_port = ;
$mail_username = "";
$mail_password = "";


function php_emailer( $email_from, $name_from, $email_to, $name_to, $subject, $message, $attachment )
{
    global $system_email, $mail_host, $mail_port, $mail_username, $mail_password;
    
    //Create a new PHPMailer instance
    $mail = new PHPMailer;
    //Tell PHPMailer to use SMTP
    $mail->isSMTP();
    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //Ask for HTML-friendly debug output
    $mail->Debugoutput = 'html';
    //Set the hostname of the mail server
    $mail->Host = $mail_host;
    //Set the SMTP port number - likely to be 25, 465 or 587
    $mail->Port = $mail_port;
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //Username to use for SMTP authentication
    $mail->Username = $mail_username;
    //Password to use for SMTP authentication
    $mail->Password = $mail_password;
    //Set who the message is to be sent from
    $mail->setFrom($system_email, "Appointment Booking System");
    //Set an alternative reply-to address
    $mail->addReplyTo($email_from, $name_from);
    //Set who the message is to be sent to
    $mail->addAddress($email_to, $name_to);
    //Set the subject line
    $mail->Subject = $subject;

    //Read an HTML message body from an external file, convert referenced images to embedded,
    //convert HTML into a basic plain-text alternative body
    $mail->msgHTML($message);
    //Replace the plain text body with one created manually
    $mail->AltBody = $message;

    //Attach a file
    $mail->addAttachment( $attachment );

    //send the message, check for errors
    if (!$mail->send()) 
        echo "Mailer Error: " . $mail->ErrorInfo; 
}

function do_query( $query, $link )
{
    $result = mysqli_query( $link, $query );

    if (!$result) 
    {
        $message  = '<br>Invalid query: ' . mysqli_error($link) . "\n";
        exit($message);
    }

    return $result;
}

function crypt_string( $str, $hash1, $hash2 )
{
    $key = substr($hash1, 0, 16) . substr($hash2, 0, 16);
    
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
    
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    
    $res =  mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_CBC, $iv);
    
    return $res . $iv ;
}

function decrypt_string( $str, $hash1, $hash2 )
{
    $key = substr($hash1, 0, 16) . substr($hash2, 0, 16);
    
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
    
    $iv = substr($str, strlen($str)-$iv_size, $iv_size);
    
    $str = substr($str, 0, strlen($str)-$iv_size);
    
    $res = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_CBC, $iv);
    
    return $res;
}


function cancel_appointment( $hash )
{
    global $host, $user, $password, $dbname, $system_email;

    $db_link = mysqli_connect( $host, $user, $password, $dbname );

    if ( !$db_link )
        exit ('Can not connect to database ' . $dbname . ": " . mysqli_connect_error());

    $query  = "SELECT   booking.name as bk_name, 
                        booking.email as bk_email, 
                        booking.timestamp as bk_timestamp, 
                        booking.hash1 as bk_hash1,
                        booking.hash2 as bk_hash2,
                        specialists.name as sp_name, 
                        specialists.title as sp_title,
                        specialists.email as sp_email
               FROM     `booking`, `specialists` 
               WHERE    (`booking`.hash1 = '$hash' OR `booking`.hash2 = '$hash') AND (`booking`.id = `specialists`.id)
               LIMIT    1;";

    $result = do_query( $query, $db_link ); 

    if (mysqli_num_rows($result) != 0)
    {
        $row = mysqli_fetch_assoc($result);
        $name = decrypt_string( $row['bk_name'], $row['bk_hash1'], $row['bk_hash2'] );
        $email = decrypt_string( $row['bk_email'], $row['bk_hash1'], $row['bk_hash2'] );
        $tmsp = $row['bk_timestamp'];
        $specialist = $row['sp_name'] . ", " . $row['sp_title'];
        $specialist_email = $row['sp_email'];
        $date = date( "D", $tmsp ) . " " . date( "M", $tmsp ) . " " .  date( "j", $tmsp );
    }

    echo "<br> Name: " . $name;
    echo "<br> Email: " . $email;
    echo "<hr>";
    
    $query  = "DELETE FROM `booking` 
               WHERE hash1 = '$hash' OR hash2 = '$hash'
               LIMIT 1;";

    $result = do_query( $query, $db_link ); 

    if ( mysqli_affected_rows($db_link) == 1 )
    {
        $message_to_specialist  = "<br>";
        $message_to_specialist .= "<p>Name: " . $name . "</p>";
        $message_to_specialist .= "<p>Email: " . $email . "</p>";
        $message_to_specialist .= "<hr>";
        $message_to_specialist .= "<p>Appointment with " . $name . " on " . $date . " has been canceled</p>";

        $headers = "From: " . $system_email . "\r\n" .
                   "Reply-To: " . $system_email. "\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        php_emailer( $system_email, "Appointment Booking System", $specialist_email, $specialist, 
                    "Booking System: Canceled appointment with " . $name, $message_to_specialist, "" ); 

        $message_to_client   = "<br>";
        $message_to_client  .= "<hr>";
        $message_to_client  .= "<p>Appointment with " . $specialist . " on " . $date . " has been canceled</p>";

        $headers = "From: " . $system_email . "\r\n" .
                   "Reply-To: " . $system_email . "\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        php_emailer( $system_email, "Appointment Booking System", $email, $name, 
                    "Booking System: Canceled appointment with " . $specialist, $message_to_client, "" ); 

        exit("<br>Appointment has been successfully canceled"); 
    }

}

function check_url()
{
    $url = $_SERVER['REQUEST_URI'];
    $str = parse_url( $url , PHP_URL_PATH );
    
    if ( $str )
    {
        $pos = strrpos( $str, '/' );
            
        if ( $pos !== FALSE )
        {
            $hash = trim(substr( $str, $pos+1 ));
            
            if ( $hash )
            {
                if ( function_exists( 'cancel_appointment' ) )
                    cancel_appointment( $hash ); 
                
                exit();
            }
                
        }
        
    }
}

check_url();

?>