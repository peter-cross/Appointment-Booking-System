<?php

// Variable for error messages
$msg = "";

// if start_date is passed from the client
if ( isset($_REQUEST['start_date']) )
    // Set up $php_start_date for php
    $php_start_date = $_REQUEST['start_date'];

// If start_date for some reason was not passed
else 
    // Error message about start date
    $msg .= "<br>Start date not specified";

// If booking_period is passed from the client
if ( isset($_REQUEST['booking_period']) )
    // Set up variable for booking period in php
    $php_booking_period = $_REQUEST['booking_period'];

// If booking_period was not passed from the client
else
    // Error message about booking period
    $msg .= "<br>Booking period not specified";

// If there is token passed from the client
if ( isset($_REQUEST['token']) )
    // Set up php variable for token
    $token = $_REQUEST['token'];

// If there is no token passed from the client
else
    // Error message about token
    $msg .= "<br>Token not specified";

// If specialist is passed from the client
if ( isset($_REQUEST['specialist']) )
    // Set up php variable for specialist
    $specialist = $_REQUEST['specialist'];

// If specialist was not passed from the client
else
    // Error message about specialist
    $msg .= "<br>Specialist not specified";

// If there are any error messages
if ( $msg )
{
    // Pass error messages to client
    echo json_encode($msg);
    
    // exit
    return;
}

// Include php file with database connect information
require( $token . '/db_info.php' );
// Include php file with constants
require( $token . '/constants.php' );

//ob_start();

// Connect to database
$db_link = @mysqli_connect( $host, $user, $password, $dbname );

// If link is not established
if ( !$db_link )
{
    // Pass to client error message regarding connection to database
    echo json_encode("Can not connect to database: " . mysqli_connect_error());
    
    // Exit
    return;
}

// If a current visitor is not allowed to use website
if ( !check_visitor($db_link) )
    // Exit
    return;
    
// Set up default time zone to value in $timezone constant
date_default_timezone_set($timezone);

// Pass to the client computer HTML content for the current booking period
echo json_encode( create_content( $php_start_date, $day_start, $day_length, $php_booking_period, $time_inc, $max_slots, $db_link, $specialist ) );

/** Creates HTML content for appointment schedule
 ** @param $start           : starting date for the booking period
 ** @param $day_start       : time when the working day starts
 ** @param $day_length      : the length of the working day
 ** @param $booking_period  : booking period on one screen, in days
 ** @param $time_inc        : one time slot
 ** @param $max_slots       : maximum number of time slots to book at once
 ** @param $link            : link to database connection
 ** @param $specialist      : specialist
 ** @return                 : HTML content for the client computer
 */
function create_content( $start, $day_start, $day_length, $booking_period, $time_inc, $max_slots, $link, $specialist )
{
    // Create HTML table to put inside
    $_content = "<table border='1' class='content-table'><tr><th></th>";

    // Loop for each day in one booking period
    for ($i=0; $i<$booking_period; $i++)
    {
        // Create timestamp for each day in the booking period
        $crdt = mktime( 0, 0, 0, date("m", $start), date("d", $start)+$i, date("Y", $start) );
        
        // Convert timestamp to astring
        $dt = timestamp_to_date($crdt);

        // Add HTML table row header
        $_content .= "<th class='content-columns-header'>&nbsp;$dt&nbsp;</th>";  
    
    } // end for $i

    // Add closing tag for a table row
    $_content .= "</tr>";

    // Get an array of timestamps for each time slot
    $timestamp = get_timestamps( $link, $time_inc, $specialist );

    // Loop for each time slot 
    for ($j=$day_start; $j<=$day_start+$day_length; $j += $time_inc)
    {
        // Convert 24H format hours AM/PM hours 
        $hr = floor(($j>=13 ? $j-12 : $j));
        
        // Add white spaces if hour number is less than 10
        $hr = "" . ( $hr<10 ? "&nbsp;&nbsp;" : "" ) . $hr;
        
        // Get a string with minutes
        $mn = time_minutes ($j);
        
        // Add am/pm to hours
        $am = ( $j<12 ? "am" : "pm");

        // Add a cell with hours, min, am/pm for current time slot
        $_content .= "<tr><th class='content-rows-header'>$hr:$mn $am</th>";

        // Calculate min available time for today
        $tm = date("H") + 1 + $time_inc; 

        // Loop for each day in the booking period
        for ($i=0; $i<$booking_period; $i++)
        {
            // Get a day name for current date
            $wkday = date( "D" , mktime( 8, 0, 0, date("m", $start), date("d", $start)+$i, date("Y", $start) ));

            // Hour when the current time slot starts
            $h = floor($j);
            // Minutes when the current time slot starts
            $m = ($j-$h)*60;

            // Create a timestamp for the time when the current time slot starts
            $crdt = mktime( $h, $m, 0, date("m", $start), date("d", $start)+$i, date("Y", $start) );

            // If it's today and time is less than min available OR it's a weekend day
            if ( ( $i==0 && $j<$tm && date( "d", $start) == date("d") ) || ( $wkday === "Sat") || ( $wkday === "Sun") )
                // Add cell marked as unavailable slot
                $_content .= "<td class='unavaiable-slot'><div></div></td>";

            // If current time slot is in the array of booked time
            elseif ( isset( $timestamp[$crdt] ) )
            {
                // If timestamp array contains timestamp, assign timestamp to variable, otherwise - BKD
                $bkd = ( $timestamp[$crdt] != "" ? $timestamp[$crdt] : "BKD" );
                // Add a cell to table marked as a booked slot
                $_content .= "<td class='booked-slot'><div></div></td>";
            }
            
            // If timeslot is not booked 
            else
            {
                // Convert timestamp to string with date
                $hint  = timestamp_to_date($crdt, ' ');
                // Add hours, mins, am/pm for current time slot
                $hint .= ',' . $hr . ':' . $mn . ' ' . $am;
                    
                // Add a table cell for available time slot
                $_content .= "
                    <td class='available-slot'>
                        <div class='available-slot-div'>
                            <input type='radio' name='appointment' value='$crdt' title='$hint' class='available-slot-input' required>
                        </div>
                    </td>
                "; 
                
            } // end else
                
        } // End for Loop for each day in the booking period

        // Add closing tag for a table row
        $_content .= "</tr>";
        
    } // end for Loop for each time slot
    
    // Add closing tag for the table
    $_content .= "</table>";

    // Return HTML content
    return $_content;

} // end create_content

/** Performs SQL query
 ** @param $query   : SQL query statement
 ** @param $link    : link to database connection
 ** @return         : results of SQL query
 */
function do_query( $query, $link )
{
    // Get results of SQL query
    $result = mysqli_query( $link, $query );

    // If there are no results
    if (!$result) 
    {
        // Error message about invalid SQL query
        $message  = '<br>Invalid query: ' . mysqli_error($link) . "\n";
        
        // Exit with error message
        exit($message);
    }

    return $result;

} // end do_query

/** Checks if a user is allowed to use the website 
 ** @param $link    : link to the database
 ** @return         : TRUE if user is allowed to use website
 */
function check_visitor( $link )
{
    // Get IP-address for current website visitor
    $ip         = $_SERVER['REMOTE_ADDR'];
    
    // If the visitor specified email address, assign it to variable 
    $email      = ( isset($_POST['clt_email']) ? $_POST['clt_email'] : "" );

    // Query to select all records from `blacklist` table for current IP-address
    $query      = "SELECT * FROM `blacklist` 
                   WHERE ip = '$ip' ";

    // If the visitor specified his/her email, select database records for that email too
    if ( $email )
        $query .=  " OR email = '$email' ";
    
    // End of query statement sign
    $query .=  ";";

    // Perform SQL query and assign results to $result
    $result = do_query( $query, $link );

    // If there rows in the results
    if (mysqli_num_rows($result) != 0)
        return FALSE; // Blacklisted
    
    // If there are no records for current IP or email address
    else
        return TRUE;  //OK to use
    
} // end check_visitor

/** Converts string to a string usable for #href
 ** @param $str : string to convert
 ** @return     : string usable for #href
 */
function str_to_href( $str )
{
    // Remove left and right white spaces
    $str = trim($str);
    // Replace space symbols with underscore
    $str = str_replace( " ", "_", $str );
    // Remove left brackets
    $str = str_replace( "[", "", $str );
    // Remove right brackets
    $str = str_replace( "]", "", $str );
    // Remove left braces
    $str = str_replace( "{", "", $str );
    // Remove right braces
    $str = str_replace( "}", "", $str );
    
    // Convert to lower case and assign to variable to return
    $href = strtolower( $str );

    // Return string usable for #href
    return $href;

} // end str_to_href

/** Removes order coded in a string
 ** @param $str : string to remove order
 ** @return     : string cleared of order symbols
 */
function remove_order( $str )
{
    // Find the position of right bracket
    $brkt = strpos($str, "]");
    
    // Calculate the offset for the rest of the string
    $ofst = ( (!$brkt) ? 0 : $brkt+2 );

    // Return the string cleared of order symbols
    return substr( $str,  $ofst );

} // end remove_order

/** Extracts name from a string removing additional characters
 ** @param $str : String to extract a name
 ** @return     : String cleared of special characters
 */
function extract_name( $str )
{
    // Remove special characters for order
    $str = remove_order( $str );

    // Calculate the number of symbols before (.) period
    $point = strrpos($str, ".");
    
    // If there is (.) period, the length is the number of symbols before (.) period, otherwise - the string length
    $length = ( $point>0 ? $point :  strlen($str) );

    // Get from the string a substring with size $length
    $str = substr( $str,  0, $length );

    // Remove left braces
    $str = str_replace( "{", "", $str );
    // Remove right braces
    $str = str_replace( "}", "", $str );
    // Remove periods
    $str = str_replace( ".", " ", $str );

    // Return the string cleared of special symbols
    return $str;
    
} // end extract_name

/** Get timestamps for booked time for a specified specialist
 ** @param $link        : link to database
 ** @param $time_inc    : time slot value
 ** @param $specialist  : specialist
 ** @return             : array of timestamps for booked time
 */
function get_timestamps( $link, $time_inc, $specialist )
{
    // Variable for storing array of timestamps
    $timestamp = [];
    
    // SQL query to select all fields from table `booking`
    // for specialist passed as a parameter
    // and order results by timestamp in ascending order
    $query  = "
        SELECT * FROM `booking` 
        WHERE id = $specialist 
        ORDER BY `timestamp` ASC;
    ";
    
    // Variable for messages
    $message = "";

    // Do SQL query and assign results to variable $result
    $result = do_query( $query, $link );

    // If in results there is at least one row
    if (mysqli_num_rows($result) != 0)
        // Get from the results associative array for a row and assign it to variable $row
        while ($row = mysqli_fetch_assoc($result))
        {
            // Get a field with confirmation code and assign it to $timestamp array element for current row timestamp
            $timestamp[$row['timestamp']] = $row['code'];

            // Loop for each time slot within booked time
            for ( $i = $time_inc; $i < $row['time']; $i += $time_inc )
            {
                // Extract minutes from timestamp and increase it by number of minutes time slot contain (without hours)
                $mn = date("i", $row['timestamp']) + ($i - floor($i)) * 60;
                
                // Extract the number of hours from timestamp and increase it by the number of hours in time slot
                $hr = date("G", $row['timestamp']) + floor($i);
                
                // Create timestamp for calculated hours and minutes and month, date and year which timestamp contains
                $new_timestamp = mktime( $hr, $mn, 0, 
                                         date("m", $row['timestamp']), date("d", $row['timestamp']), date("Y", $row['timestamp']) );

                // Write into timestamp array for new timestamp booking code in the results row
                $timestamp[$new_timestamp] = $row['code']; 
            
            } // end for $i

        } // end while $row

    // Return the array of timestamps for booked time
    return $timestamp;

} // end get_timestamps

/** Converts UNIX timestamp to date
 ** @param $tmsp    : UNIX timestamp
 ** @param $sep     : separator between date, month and year
 ** @return         : a string representing a date coded in timestamp
 */
function timestamp_to_date( $tmsp, $sep="<br>" )
{
    // Extract date, month and year from timestamp and join them with separator
    return date( "D", $tmsp ) . $sep . date( "M", $tmsp ) . " " .  date( "j", $tmsp );
    
} // end timestamp_to_date

/** Calculates minutes for time represented as number
 ** @param $tm  : time in hours
 ** @return     : String containing the number of minutes for time represented as a number
 */
function time_minutes ($tm)
{
    // Calculate the number of minutes in the time
    $j = ($tm-floor($tm))*60;
    
    // return a string representing the number of minutes
    return ( $j==0 ? "00" : "" . $j );
    
} // end time_minutes

?>