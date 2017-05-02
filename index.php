<?php

/*************************************************************************************************************************/
// Turn on output buffering
ob_start();

// Folder in which content is stored
$content = "content/";

// If token is passed through GET protocol
if ( isset($_GET['token']) )
    // Read the value of token passed from the client computer
    $token = $_GET['token'];

// If no token passed
else
    // Exit with error message
    exit("Unspecified token");

// If folder with token name does not exist or it can not be open 
if ( !is_dir('php/' . $token) || !opendir('php/' . $token) )
    // Exit with error message
    exit( "Not recognized token: " . $token ); 
    
//check_url();

// Include PHP Mailer class library
require_once('php/PHPMailerAutoload.php');
// Include constants file
require('php/' . $token . '/constants.php');
// Include file with database settings
require('php/' . $token . '/db_info.php');
// Include file with mail server settings
require('php/' . $token . '/mail_info.php');

// Establish connection to database
$db_link = @mysqli_connect( $host, $user, $password, $dbname );

// If can not connect to database
if ( !$db_link )
{
    // Display error message about connection to database
    echo "Can not connect to database: " . mysqli_connect_error();
    return;
}

// If a website visitor is blacklisted - exit
if ( !check_visitor($db_link) )
    return;

// Array for cookies
$cookie = [];

// Cookie for client name
$cookie['clt_name']='';
// Cookie for client email
$cookie['clt_email']='';
// Cookie for appointment time
$cookie['apt_time']='';
// Cookie for note
$cookie['note']='';

// If the user hit button Submit
if ( isset( $_POST['btn_submit'] ) )
{
    // Book an appointment
    book_appointment( $db_link );
    
    // For each cookie from cookie array
    foreach ($cookie as $cookie_name => $cookie_value)
        // Set cookie equal to value of the form field with the same name
        setcookie($cookie_name, $_POST[$cookie_name], time() + (43200 * 30), "/" ); // cookie for 15 days   
}

// For each cookie from cookie array
foreach ($cookie as $cookie_name => $cookie_value)
    // If cookie value is set up
    if ( isset($_COOKIE[$cookie_name]) )
        // Write cookie value to cookie array
        $cookie[$cookie_name] = $_COOKIE[$cookie_name];

?>
    
<!DOCTYPE html>
<html>    
<head>
    <meta charset="utf-8" >
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/css/bootstrap.min.css">  
    <script src="/js/jquery.min.js"></script> 
    <script src="/js/bootstrap.min.js"></script>  
</head>     
    
<body>
    
<?php

/*************************************************************************************************************************/

/** Converts string to format usable for #href
 ** @param $str : string to convert
 ** @return     : string converted to format usable for #href
 */
function str_to_href( $str )
{
    // Remove white space from left and right
    $str = trim($str);
    // Replace space symbol with underscore
    $str = str_replace( " ", "_", $str );
    // Remove left brackets
    $str = str_replace( "[", "", $str );
    // Remove right brackets
    $str = str_replace( "]", "", $str );
    // Remove left braces
    $str = str_replace( "{", "", $str );
    // Remove right braces
    $str = str_replace( "}", "", $str );

    // Convert to lower case
    $href = strtolower( $str );
    
    // Reurn string converted to format usable for #href
    return $href;
    
} // end str_to_href

/** Displays folder content
 ** @param $dir     : current folder
 ** @param $section : section in which to display
 ** @param $lvl     : level
 */
function display_folder_content( $dir, $section="", $lvl=1 )
{
    // If such folder exists and it is readable
    if ( is_dir($dir) && $_dh = opendir($dir) )
    {
        // Array for folder content files
        $file = [];
        // Array for folder content folders
        $folder = [];
        // Array for style sheet files
        $style = [];

        // Loop while we can read the folder
        while ( $_f = readdir($_dh) )
            // If it's not reserved filenames or .htaccess file
            if ( $_f !== '.' && $_f !== '..' && $_f !=='.htaccess' )
                // If it's a filename
                if ( !is_dir( $dir . $_f ) )
                {
                    // If it's a stylesheet file
                    if ( preg_match("/style.php$/", $_f) OR preg_match("/.css$/", $_f) )
                        // Add the filename to stylesheet array
                        $style[] = $_f;
                    // If any other file
                    else
                        // Add to files array
                        $file[] = $_f;  
            
                } // end if it's a filename
                
                // If it's a folder
                else
                    // Add the folder to folder array
                    $folder[] = $_f;
            
        // Close directory handler
        closedir($_dh);
        
        // Sort files array
        sort( $file );
        // Sort folders array
        sort( $folder );
        
        // New sections array
        $new_section = [];
        
        // For each folder on folders array
        foreach ( $folder as $folder_name )
            // If it does not contain special symbols 
            if ( !preg_match("/[(\{\})]/", $folder_name) )
                // Extract just name and add to new sections array
                $new_section[$folder_name] = extract_name( $folder_name );
            
            // If no special symbols
            else
                // Remove order symbols and add to new sections array
                $new_section[$folder_name] = remove_order( $folder_name );
            
        // If section is specified
        if ($section)
        {
            // Convert section name to format usable for #href
            $id = str_to_href( $section );
            
            // Display section tag
            echo "<section id='$id' class='container'><!-- $section -->\n";
        }
        
        // If section is not specified
        else
        {
            // Empty ID for section
            $id = '';
            
            // Display section tag
            echo "<section class='container'><!-- $section -->\n";   
        }
                    
        // Display div for container with bootstrap class
        echo "	     <div class='col-md-12'>";
        
        // If ID is specified and it's not HOME
        if ( $id !== '' && $id !== 'home' )
            // Display spacer div
            echo "<div class='spacer'></div>";
        
        // Array for menu
        $menu = [];
        
        // If array of new sections is not empty
        if ( sizeof($new_section)>0 )
            // For each new section from the array
            foreach ( $new_section as $menu_item )
                // If it does not contain any special symbols
                if ( !preg_match("/[(\{\})]/", $menu_item) )
                    // Extract name and add to menu array
                    $menu[] = extract_name( $menu_item );
            
        // If menu array is not empty
        if ( sizeof($menu)>0 )
        {
            // Add nav tag for navigation section
            $nav = "<nav id='$id-menu' class='navbar navbar-fixed-top container' role='navigation'>
                        <div class='navbar-inner'>
                            <ul class='nav navbar-nav'>";  

            // If files array is not empty
            if ( sizeof( $file )>0 )
            {
                // Convert section name to format usable for #href
                $href = str_to_href( $section );
                
                // If it's HOME #href
                if ($href === 'home')
                    // Remove it
                    $href = "";
                
                // Extract name from section and assign to menu item
                $menu_item = extract_name( $section );
                // If menu item is empty, assign it to HOME
                $menu_item = ( $menu_item == "" ? "HOME" : $menu_item );
                // Add li tag for menu item
                $nav .= "<li id='$menu_item-item' class='active'><a href='#$href'>$menu_item</a></li>";
                // No additional class
                $class = "";
            }
            
            // If files array is empty
            else
                // Add additional class 'active'
                $class = " class='active'";

            // For each menu item from menu array
            foreach ( $menu as $menu_item )
            {
                // Convert menu item name to #href usable format
                $href = str_to_href( $menu_item );
                
                // Add li tag for menu item
                $nav .= "<li id='$menu_item-item' $class><a href='#$href'>$menu_item</a></li>";
                // No additional class
                $class = "";
            
            } // end For each menu item

            // Add closing tags for navigation section
            $nav .= "       </ul>
                        </div>
                    </nav>";

            // Display navigation section
            echo $nav;
        
        } // end If menu array is not empty

        // If section ID is specified and no special symbols in section name
        if ( $id !== '' && !preg_match("/[(\[\])]/", $section) && !preg_match("/[(\{\})]/", $section))
        {
            // Uppercase the 1st letter of section name and assign it to section title
            $title = ucwords( $section );
            // Display section title
            echo "	     <h$lvl>$title</h$lvl>";
        }
        
        // If in folder content there are stylesheet files
        if ( sizeof( $style )>0 )
        {
            // Sort stylesheets array
            sort( $style );
            // Add stylesheet files to the beginning of files array
            $file = array_merge( $style, $file );    
        }
                
        // For each file from files array
        foreach ( $file as $file_name )
        {
            // If it's php file
            if ( preg_match("/.php$/", $file_name) )
                // Include this php file into section content
                require_once( $dir . $file_name );
            
            // If it's a stylesheet file
            elseif ( preg_match("/.css$/", $file_name) )
                // Add style tag with styling content
                echo "<style>" . file_get_contents( $dir . $file_name ) . "</style>";
            
            // If it's HTML file
            elseif ( preg_match("/.html$/", $file_name) )
                // Display the content of that file
                echo file_get_contents( $dir . $file_name );    
            
            // Otherwise
            else
                continue;
                
        } // end foreach $file
        
        // For each folder from new section array
        foreach ( $new_section as $folder_name => $section_name )
        {
            // If section name does not contain special symbols
            if ( !preg_match("/[(\{\})]/", $section_name) )
                // Call this function again to display the content of the folder inside the currrent section
                display_folder_content( $dir . $folder_name . "/", $section_name, $lvl+1 );
            
            // If section name contains special symbols
            else
                // Call this function again to display the content of the folder inside the currrent section
                display_folder_content( $dir . $folder_name . "/", "", $lvl+1 );
        
        } // end For each folder from new section array
                
        // If section was specified
        if ($section)
        {
            // Display closing tags for the section
            echo "	 </div><!-- /.col-md-12 -->\n";
            echo "          </section><!-- /#$id -->\n";
        }
        
    } // end if is_dir

} // end display_folder_content

/** Performs SQL query
 ** @param $query   : SQL query 
 ** @param $link    : link to database
 ** @return         : results of query
 */
function do_query( $query, $link )
{
    // Get results of SQL query
    $result = mysqli_query( $link, $query );

    // If no results
    if (!$result) 
        return FALSE;
    
    // Otherwise
    else
        return $result;
    
} // end do_query

/** Checks if a website visitor is allowed to use website
 ** @param $link    : link to database
 ** @return         : TRUE, if allowed
 */
function check_visitor( $link )
{
    // Get visitor's IP-address
    $ip         = $_SERVER['REMOTE_ADDR'];
    
    // If visitor entered email address, get email address
    $email      = ( isset($_POST['clt_email']) ? $_POST['clt_email'] : "" );

    // Select all records from `blacklist` table
    $query      = "SELECT * FROM `blacklist` ;";

    // Get results of SQL query
    $result = do_query( $query, $link );

    // If there are records in the results
    if (mysqli_num_rows($result) != 0)
    {
        // Loop while we can get a row of an associative array frm the results
        while ( $row = mysqli_fetch_assoc($result) )
        {
            // Decrypt blacklisted IP-address from the results
            $blkd_ip    =  decrypt_string( $row['ip'], $row['hash1'], $row['hash2'] );
            // Decrypt blacklisted email address from the results
            $blkd_email =  decrypt_string( $row['email'], $row['hash1'], $row['hash2'] );
            
            // If current IP-address matches blacklisted IP-address or current email matches blacklisted
            if ( $ip == $blkd_ip || ( ($email == $blkd_email) && ($email != "") ) )
                return FALSE;
            
        } // end Loop while we can get a row of an associative array
        
        // If we checked every record in blacklist and they did not match
        return TRUE; // OK to use
    
    } // end If there are records in the results
    
    // If no blacklisted records
    else
        return TRUE;  //OK

} // end check_visitor

/** Sends email through email server
 ** @param $email_from  : sender's email
 ** @param $name_from   : sender's name
 ** @param $email_to    : receiver's email
 ** @param $name_to     : receiver's name
 ** @param $subject     : email subject
 ** @param $message     : email message
 ** @param $attachment  : attahcment file
 */
function php_emailer( $email_from, $name_from, $email_to, $name_to, $subject, $message, $attachment )
{
    // Making accessible global variables for:
    // system email address, host computer of mail server, port on mail server, username and password on mail server
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

} // end php_emailer

/** Sends email about booked appointment to visitor and specialist
 ** @param $hash1           : hash key 1 for crypting
 ** @param $hash2           : hash key 2 for crypting
 ** @param $code            : booking code
 ** @param $specialist_name : specialist's name
 ** @param $specialist_email: specialist's email
 */
function send_email( $hash1, $hash2, $code, $specialist_name, $specialist_email )
{   
    // Making accessible global variables for:
    // cancellation website address, system email, usre's timezone
    global $cancel_website, $system_email, $timezone;

    // Get user's email from the client computer
    $email = $_POST['clt_email'];
    // Get user's name from the client computer
    $name  = $_POST['clt_name'];
    // Get booked appointment timestamp from the client computer
    $tmsp  = $_POST['appointment'];
    // Get time required for the appointment from the client computer
    $aptm  = $_POST['apt_time'];
    // Get user's note from the client computer
    $note  = $_POST['note'];

    // Set up default timezone to user's timezone
    date_default_timezone_set($timezone);
    //$system_email = "vudeem@gmail.com";

    // Get from timestamp a string containing date, month and a year
    $date = date( "D", $tmsp ) . " " . date( "M", $tmsp ) . " " .  date( "j", $tmsp );
    // Get from timestamp a string containing hours, mins and am/pm
    $time = date( "g", $tmsp ) . ":" . date( "i", $tmsp ) . " " .  date( "a", $tmsp );

    // If the required time is less than 2 hours
    if ( $aptm<2 )
        // Convert the required time to minutes and present as a string
        $hlong = "" . ($aptm * 60) . " min";
    
    // If the required time is 2 hours or more
    else
        // String containing hours required for the appointment
        $hlong = "" . $aptm . " hrs";

    // Variable for error message
    $error_message = "";

    // Regular expression for valid email format
    $email_exp = '/^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/';

    // If client's email does not match the right email format
    if ( !preg_match( $email_exp, $email) ) 
        // Add error message about invalid email format
        $error_message .= '<br>The Email Address you entered does not appear to be valid.';

    // Regular expression for a valid name
    $string_exp = "/^[A-Za-z .'-]+$/";

    // If client's name does not match the valid name format
    if ( !preg_match( $string_exp, $name ) ) 
        // Add error message about invalid email
        $error_message .= '<br>The Name you entered does not appear to be valid.';

    // If there are error messages
    if ( $error_message )
        // Exit with diplaying error messages
        exit( $error_message );

    // Head for email message to specialist
    $message_to_specialist  = "<br>";
    $message_to_specialist .= "<p>Name: " . $name . "</p>";
    $message_to_specialist .= "<p>Email: " . $email . "</p>";
    $message_to_specialist .= "<p>Confirmation code: " . $code . "</p>";
    $message_to_specialist .= "<h2>Scheduled Appointment</h2>";
    $message_to_specialist .= "<hr>";
    $message_to_specialist .= "<p>Appointment with " . $name . " on " . $date . " at " . $time . " for " . $hlong . "</p>";

    // If there is something entered in note field
    if ($note)
        // Add to message to specialist client's note
        $message_to_specialist .= "<p>Note: " . $note . " </p>";

    // Add link to cancel the appointment, if necessary
    $message_to_specialist .= "<br><p>In case you need to cancel the appointment click on the link : 
                                  <a href='$cancel_website/$hash2'>$cancel_website/$hash2</a>
                               </p>";

    // Email header
    $headers = "From: " . $system_email . "\r\n" .
               "Reply-To: " . $system_email . "\r\n" .
               "X-Mailer: PHP/" . phpversion();

    // Send email about booked appointment to specialist
    php_emailer( $system_email, "Appointment Booking System", $specialist_email, $specialist_name, 
                 "Booking System: New appointment", $message_to_specialist, "" ); 

    // Head of email message to the client
    $message_to_client  = "<br>";
    $message_to_client .= "<h2>Appointment confirmation</h2>";
    $message_to_client .= "<hr>";
    $message_to_client .= "<p>Dear " . $name . ", </p><br>";
    $message_to_client .= "<p>You booked an appointment with " . $specialist_name . " on " . $date . " at " . $time . " for " . $hlong . "</p>";
    $message_to_client .= "<p>Confirmation code: " . $code . "</p>";

    // If there is something entered in note field
    if ($note)
        // Add to message to client client's note
        $message_to_client .= "<p>Note: " . $note . " </p>";

    // Add link to cancel the appointment, if necessary
    $message_to_client .= "<br><p>In case you need to cancel the appointment click on the link : 
                              <a href='$cancel_website/$hash1'>$cancel_website/$hash1</a>
                           </p>";

    // Email signature for client's email message
    $message_to_client .= "<br><br>
                            Regards, <br>
                            Appointment Booking System";

    // Client's email header
    $headers = "From: " . $system_email . "\r\n" .
               "Reply-To: " . $system_email . "\r\n" .
               "X-Mailer: PHP/" . phpversion();

    // Send email about booked appointment to the client
    php_emailer( $system_email, "Appointment Booking System", $email, $name, 
                 "Appointment with " . $specialist_name, $message_to_client, "" ); 

} // end send_email

/** Generates a 36 symbol random string 
 ** @return : random string
 */
function random_string()
{
    // Valid characters for a random string
    $char = "0123456789abcdefghijklmnopqrstuvwxyz";
    // Random string length
    $length = strlen($char);

    // Variable for a result string
    $str = "";
    
    // Loop for each character of random string
    for ( $i=0; $i<$length; $i++ )
    {
        // Get a random number in a range from 0 to string length - 1
        $num = rand( 0, $length-1 ); 
        // Get the corresponding character and add it to the result string
        $str .= $char[$num];
    
    } // end Loop for each character

    // Return the result string
    return $str;
    
} // end random_string

/** Generates 3-symbol appointment code
 ** @param $name    : client's name
 ** @return         : 3-symbol appointment code
 */
function appointment_code ( $name )
{
    // Valid numbers
    $num  = "0123456789";
    // Valid characters
    $char = "ABCDEFGHIJKLMNOPQRSTUVXYZ";

    // 1st symbol - random character
    $c1 = $char[rand(0, strlen($char)-1)];
    // 2nd symvol - random number from 0 to 9
    $c2 = $num[rand(0, strlen($num)-1)];
    // 3rd symbol - 1st letter of the client's name
    $c3 = strtoupper(substr( $name, 0, 1));

    // Return concatenated 3 symbols for appointment code
    return $c1 . $c2 . $c3;
    
} // end appointment_code

function check_input()
{
    global $time_inc;
    
    $msg = "";
    
    if ( !isset($_POST['appointment']) )
        $msg .= "<br> Appointment time is not selected";
    if ( !isset($_POST['apt_time']) )
        $msg .= "<br> Required time is not selected";
    if ( !isset($_POST['clt_name']) )
        $msg .= "<br> Name is not entered";
    if ( !isset($_POST['clt_email']) )
        $msg .= "<br> Email addres is not entered";
    if ( !isset($_POST['appointment']) )
        $msg .= "<br> Appointment time is not selected";
    else if ( $_POST['appointment'] < (date("H") + 1 + $time_inc) 
             || ( date( "D", $_POST['appointment']) == 'Sat' )
             || ( date( "D", $_POST['appointment']) == 'Sun' ) )
        $msg .= "<br> Time is not available";
    
    if ( $msg )
    {
        exit( $msg );
        return FALSE;
    }
    else    
        return TRUE;  
}

function crypt_string( $str, $hash1, $hash2 )
{
    $key = substr($hash1, 0, 16) . substr($hash2, 0, 16);
    
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
    
    do
    {
        do
        {
            $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);    
        } while ( strpos($iv , "\"") != FALSE || strpos($iv , "'") != FALSE );    
        
        $res =  mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_CBC, $iv);
        
    } while ( strpos($res , "\"") != FALSE || strpos($res , "'") != FALSE );
    
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

function book_appointment ( $link )
{
    if ( !check_visitor($link) || !check_input() )
        return;

    $tbl = 'booking';

    $timestamp  = $_POST['appointment'];
    $time       = $_POST['apt_time'];    
    $hash1      = random_string();
    $hash2      = random_string();
    $specialist = $_POST['specialist'];

    $query  = "
        SELECT * FROM `specialists` 
        WHERE id = $specialist
        LIMIT 1;
    ";

    $result = do_query( $query, $link );

    if (mysqli_num_rows($result) != 0)
    {
        $row = mysqli_fetch_assoc($result);

        $specialist_name = $row['name'] . ', ' . $row['title'];
        $specialist_email = $row['email'];
    }
    else
    {
        exit("Couldn't find the specialist in the database");
        return NULL;
    }
    
    $n = 0;
    
    do
    {
        $name  = crypt_string( $_POST['clt_name'], $hash1, $hash2 );
        $email = crypt_string( $_POST['clt_email'], $hash1, $hash2 );
        $note  = crypt_string( $_POST['note'], $hash1, $hash2 );
        $ip    = crypt_string( $_SERVER['REMOTE_ADDR'], $hash1, $hash2 );
        $code  = appointment_code( $_POST['clt_name'] );

        $query = "
            INSERT INTO `$tbl`
            (`timestamp`, `code`,  `name`, `email`, `time`, `hash1`, `hash2`, `ip`, `note`, `id`)
            VALUES
            ('$timestamp', '$code', '$name', '$email', '$time', '$hash1', '$hash2', '$ip', '$note', '$specialist');
        ";   
        
        if ( $n++>1000 )
            exit( "Error in SQL query" );
        
    } while ( !$result = do_query( $query, $link ) );
    
    if ( mysqli_affected_rows($link) == 1 )
        send_email( $hash1, $hash2, $code, $specialist_name, $specialist_email );
    else
    {
        exit("<br>Error writing into table `$tbl`");
        return NULL;
    }    

}

function divbox($id, $title, $content)
{
    echo    "<div id='$id' class='schedule-div'>\n
                  <h4>$title</h4>\n";

    echo    "     $content";

    echo    "</div>\n";     
}

/*****************************************************************************************************************************/

require('style.php');    

date_default_timezone_set($timezone);
    
$specialist = [];
    
$query  = "SELECT * FROM `specialists` 
           ORDER BY `id` ASC;";

$result = do_query( $query, $db_link );

if (mysqli_num_rows($result) != 0)
    while ($row = mysqli_fetch_assoc($result))
         $specialist[$row['id']] = $row['name'] . ', ' . $row['title']; 

// Displaying navigation buttons initially
$display_left = "none";
$display_right = "block"; 

$display_specialist = ( sizeof($specialist)>1 ? "block" : "none" );

//$_content = "<section class='container'><div class='col-md-12'>";
 
?>

<input id ='start_date' name='start_date' type='hidden' value='<?php echo time();?>'>
    
<script>
    $(document).ready(function()
    {
        (function( url, method, data_type, func_ref )
        {
            $.ajaxSetup( {cache     : false,
                          async     : true,
                          url       : url,
                          type      : method,
                          dataType  : data_type,
                          success   : func_ref,
                          enctype   : 'multipart/form-data'} );    
        })( "php/appointment_request.php", "POST", "html", function (data)
        {
            (function( cont )
            {
                return function( output )
                {
                    $( output ).html( cont );   
                }
            })( (typeof( data ) !== 'undefined' ? JSON.parse( data ) : "No data") )( '#schedule-table' );
        } );
        
        var evoke_ajax_call = function ( start_date, booking_period, specialist, token )
        {
            $.ajax( {data:{start_date       : start_date, 
                           booking_period   : booking_period, 
                           specialist       : specialist, 
                           token            : token}});
        }

        var current_ajax_call = function ()
        {
            evoke_ajax_call( $('#start_date').val(), 
                             <?php echo $booking_period;?>, 
                             $('#specialist').val(), 
                             '<?php echo $GLOBALS['token'];?>' );    
        }

        var next_ajax_call = function ()
        {
            var start_date = new Date( $('#start_date').val()*1000 );
            var new_date = new Date(start_date);
            new_date.setDate( start_date.getDate() + <?php echo $booking_period;?> );

            var new_timestamp = (new_date.getTime()/1000).toFixed(0);
            $('#start_date').val(new_timestamp);
            $('#prev').css('display', 'block');

            evoke_ajax_call( new_timestamp, 
                             <?php echo $booking_period;?>, 
                             $('#specialist').val(), 
                             '<?php echo $GLOBALS['token'];?>' );
        }

        var prev_ajax_call = function ()
        {
            var start_date = new Date( $('#start_date').val()*1000 );
            var today = new Date();
            var new_date = new Date(start_date);
            new_date.setDate( start_date.getDate() - <?php echo $booking_period;?> );

            if ( new_date < today )
                    new_date = today;    

            var new_timestamp = (new_date.getTime()/1000).toFixed(0);
            $('#start_date').val(new_timestamp);

            evoke_ajax_call( new_timestamp, 
                             <?php echo $booking_period;?>, 
                             $('#specialist').val(), 
                             '<?php echo $GLOBALS['token'];?>' );

            if ( new_date.getDate() == today.getDate() )
                $('#prev').css('display', 'none');    
        }
        
        current_ajax_call(); 

        $('#<?php echo $menu_item;?>-item').click(function()
        {
            current_ajax_call();    
        });

        $('#specialist').click(function()
        {
            current_ajax_call();    
        });

        $('#next').click(function()
        {
            next_ajax_call();    
        });

        $('#prev').click(function()
        {
            prev_ajax_call();    
        });
    });

</script>       

<?php


$_content = "
        <input id='prev' name='prev' type='button' value='<' class='btn-prev'
               style='display: $display_left;'> 

        <input id='next' name='next' type='button' value='>' class='btn-next'
               style='display: $display_right;'>

        <div class='with' style='display: $display_specialist;'>
            With:&nbsp;
            <select id='specialist' name='specialist' class='specialist'>
";

$k = 1;
foreach ($specialist as $specialist_id => $specialist_name)
{
    $sltd = ($k===1 ? "selected" : "");

    $_content .= "<option value='$specialist_id' $sltd>$specialist_name</option>"; 

    $k++;
}

$_content .= "
            </select>
        </div>
        <div id='schedule-table' class='schedule-table'></div>
";

$user_name = $cookie['clt_name'];
$user_email = $cookie['clt_email'];
$time_required = $cookie['apt_time'];
$user_note = $cookie['note'];

$_content .= "
        <table border='0' class='input-table'>
            <tr>
                <td class='input-table-name-cell'>
                    &nbsp;Name*:
                </td>

                <td class='input-table-email-cell'>
                    &nbsp;Email*:
                </td>

                <td class='empty-cell'><div></div></td>
                
                <td class='input-table-time-cell'>
                    <span class='time_reqd'>Time req'd:</span>
                </td>
                
                <td class='empty-cell'><div></div></td>

                <td class='input-table-note-cell'>
                    &nbsp;Note:
                </td>
            </tr>
            
            <tr>
                <td class='input-table-name-cell'>
                    <input name='clt_name' type='text'  class='input-table-name-input' value='$user_name' required>
                </td>

                <td class='input-table-email-cell'>
                    <input name='clt_email' type='email' class='input-table-email-input' value='$user_email' required>
                </td>

                <td class='empty-cell'><div></div></td>
                
                <td class='input-table-time-cell'>
                    <select name='apt_time' class='input-table-time-select'>
";

for ($k=1; $k<=$max_slots; $k++)
{
    $val = $k * $time_inc;
    $mnts = $val * 60;
    
    if ( $time_required == $val )
        $sltd = "selected";
    else
        $sltd = ($k===1 ? "selected" : "");

    $_content .= "<option value='$val' $sltd>$mnts min</option>";     
}

$_content .= "</select>
            </td>
            
            <td class='empty-cell'><div></div></td>

            <td class='input-table-note-cell'>
                <input name='note' type='text' class='input-table-note-input' value='$user_note'>
            </td>

        </tr>
    </table>
";
   
?>
    
<section class='container'>
    <div class='col-md-12'>
        <form method='post' style='text-align: center;'>
            <?php
            
            $_content .= "
                <div style='margin: 5px 0; vertical-align: text-top; display: inline-block; margin-left: 1em; float: left;'>
                    <div  class='legend'>Legend:</div> 
                    <div  class='available'></div>
                    <div  class='available-time '>Available time</div>
                    <div  class='not-available'></div>
                    <div  class='not-availbale-time'>Time is not available</div>
                </div>

                <input name='btn_submit' type='submit' value='Submit' 
                            class='input-table-submit-button'>  
            ";

            divbox('appointnment', 'Book an appointment', $_content);

            ?>
               
        </form>

    </div>
</section>

</body>    
</html>
    