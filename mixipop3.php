<?php

/*
MixiPop3 1.100
© Copyright 2010 Jawaad Mahmood

    MixiLibrary is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MixiLibrary is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with MixiLibrary.  If not, see <http://www.gnu.org/licenses/>.

    http://www.gnu.org/licenses/gpl.txt


This is a set of functions needed to drive the Mixi POP3 server. 

It's not complete but implements all of the important messaging functions.  Yay.

*/

$jbug = true;

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once( dirname(__FILE__) . "/website/website.upgrade.php");
require_once( dirname(__FILE__) . "/Mixi/mixi.library.php");
require_once( dirname(__FILE__) . "/email/pop3.php");

\mysql_connect(\db\constants\host(), \db\constants\user(), \db\constants\pass());
\mysql_select_db(\db\constants\database());
\mysql_query("SET NAMES UTF8;");


// ____________________MAIN___________________


function cycle(){
  $mail_connection = new POP3();
  
  $mail_connection->login();
  $mail_connection->overview();
  if (count($mail_connection->overview) > 0){
    foreach($mail_connection->overview as $message){
	try{
		action($mail_connection,$message);
	}
	catch(Exception $e){
		error("Could not perform the action: " . print_r($message, true) . $e->getMessage() );
	}
      $mail_connection->delete($message);
    }
    
    $mail_connection->purge();
  }	
}

// Check the subject of your email message and
// run the appropriate command based on it.
function action($mail_connection, $message){
	$subject = strtolower(trim($message->subject));


	if ($title = is_action($subject, 'post')){
		mixi_post($mail_connection, $message);
		return;
	}

	if ($request = is_action($subject, 'list')){
		mixi_messages($mail_connection, $message);
		return;
	}

	if ($title = is_action($subject, 'ashiato')){
		mixi_ashiato($mail_connection, $message);
		return;
	}

	if ($request = is_action($subject, 'refresh')){
		refresh_mixi_messages($mail_connection, $message);
		return;
	}

	if ($request = is_action($subject, 'bbsthreads')){
		mixi_bbsthreads($mail_connection, $message);
		return;
	}
	
	if ($request = is_action($subject, 'bbsposts')){
		mixi_bbsposts($mail_connection, $message);
		return;
	}
	
	throw new Exception("Unknown request.");
}


function clean_line_endings(&$message){
  $total = explode("\r", $message);
  foreach($total as &$t){
    $t = trim($t);
  }
  $message = implode("\r\n", $total);
}

// Sends a collection of messages to your pop3 email.
function send($from, $messages){

	global $jbug;
	$mail_message = "";

	foreach($messages as $message){
		if (!is_a($message, "mixi\messages\obj")){
			throw new \Exception("Trying to use 'Send' for a non message object.");
		}

		$reply_to = $message->from . '@dsmob.com' . "<br>\r\n";
		$subject = 'Subject: ' . $message->subject . "<br>\r\n";
		$original_url = 'Original: http://www.mixi.jp/' . $message->url . "<br>\r\n";
		
		$details = $message->details;
		clean_line_endings($details);

		
		$body = 'Body: ' . "\r\n" . $total . "<br>\r\n";
		
		$mail_message .= $reply_to . $subject . $body . $original_url . "<hr>\r\n";
	}
	if ($jbug) echo $mail_message;
	POP3::send_mail($from, 'List Information', $mail_message);

}


function send_ashiato($from, $ashiato){
  $mail_message = "";
  foreach($ashiato as $aa){
    $u = new \mixi\profile\obj($aa->from);
    $u->load();
    
    $message = "<p><a href='http://www.mixi.jp/show_friend.pl?id=%d&from=navi'>%s</a> visited your page at %s<br><img src='%s' alt='%s'></p>";
    $message = sprintf($message, $u->id(), $u->nickname, $aa->datetime, $u->profile_picture1, $u->nickname . "'s picture");
    $mail_message .= $message . "\n<hr>";
  }

  POP3::send_mail($from, 'Ashiato List', $mail_message);
}


function send_bbsposts($from, $bbsposts){
  $mail_message = "";
  clean_line_endings($bbsposts);
  POP3::send_mail($from, 'BBS Post List', $bbsposts);
}

// _____________PARSING__________

// We use the format user_id@host to convert the email "To" field into
// a mixi id.  All bets are off if you can use the @ within the address
// itself, lol.  (If you think it's imposible, take a look at what
// docomo considers valid email..)
function get_user_id($string){
	$str = reset(explode('@', $string));
	if (is_numeric($str)){
		return (int) $str;
	}

	// In some cases, there are quotation marks around the email address that
	// screw everything up.  This is removed underneath.  
	$str = str_replace(array('"',"'"),array('',''),$str);
	if (is_numeric($str))
		return (int) $str;

	throw new Exception("I received a User ID which was not a number.");
}


// _____________________________VALIDATION_____________________________

// is $str == $action.  I know.
function is_action($str, $action){
	return trim($str) == $action;
}


// Only certain email addresses are permitted to use the system.
// This isn't "secure" (since one can easily spoof a from address), but
// you're welcome to choose a complex address if you want.

function check_valid_from($string){
	if (in_array($string, \mail\constants\valid_from_addresses())){
		return true;
	}
	throw new Exception("Invalid FROM email address.");
}

// This is probably useless.  
// I initially wanted a different "To" address for list emails (ie: list@example.com)
// Keep it or get rid of it if you want.

function check_valid_list_to($to){
	if ($to != \mail\constants\list_email_address()) throw new Exception("Tried to retrieve a message list but mailed the wrong address.");
}



// _______________________MIXI LIBRARY INTERACTIONS_______________________


// Downloads all messages on $page from Mixi.
// Saves them ONLY if they have an ID that is not in the DB.
function download_all_messages($website, $page){
	$messages = \mixi\library\all_messages($website, 1);
	
	foreach($messages as $message){
		\mixi\library\message_details($message, $website);
		sleep(5);
	}
	array_map('\mixi\Factory::save', $messages);
}

// Loads all messages in the database.
function load_message(){
	$a = new \mixi\messages\all();
	return $a->get();
}


// Posts a message to another user.
function mixi_post($mail_connection, $message){
	$to = $message->to;
	$from = $message->from;
	
	
	$header = $mail_connection->retrieve_header($message);
	$body = $mail_connection->retrieve_body($message);
	
	$to = get_user_id($to);
	check_valid_from($from);

	$miximessage = new \mixi\messages\obj();
	$miximessage->to = $to;
	$miximessage->subject = substr(reset(explode("\n", $body)),0,80);
	$miximessage->details = strip_tags($body);
	
	
	$website = new Website();
	$website->cookies();
	\mixi\library\connect($website);
	$a = \mixi\library\send($website, $miximessage);
	return true;

}

// Get a list of all recent emails in the mixi DB.
function mixi_messages($mail_connection, $message){
	
	$from = $message->from;
	$to = $message->to;
	check_valid_from($from);
	check_valid_list_to($to);
	$messages = \mixi\library\recent();
	send($from, $messages);
}



// Get a list of all recent forum threads in the mixi DB.
function mixi_bbsthreads($mail_connection, $message){
	$from = $message->from;
	$to = $message->to;
	check_valid_from($from);
	check_valid_list_to($to);

	$website = new Website();
	$website->cookies();
	\mixi\library\connect($website);

	\mixi\library\refresh_my_threads($website);
	$threads = \mixi\library\threads();
	send_bbsposts($from, $threads);
}

function mixi_bbsposts($mail_connection, $message){
	$from = $message->from;
	$to = $message->to;
	check_valid_from($from);
	$THREAD_ID_SEPERATOR = 't_';
	$MAIL_DOMAIN = '@dsmob.com';
	$to = str_replace($MAIL_DOMAIN, '', $to);
	$thread_id = array_pop(explode($THREAD_ID_SEPERATOR, $to));
	
	if (!is_numeric($thread_id))
	  return send_bbsposts($from, "<p>You did not enclose a valid thread id.  We received: " . $thread_id . "</p>");

	$website = new Website();
	$website->cookies();
	\mixi\library\connect($website);

	$posts = \mixi\library\thread_posts($website, $thread_id);
	send_bbsposts($from, $posts);
}

// Get a list of all recent emails in the mixi DB.
function mixi_ashiato($mail_connection, $message){
	
	$from = $message->from;
	$to = $message->to;
	check_valid_from($from);
	check_valid_list_to($to);
	$ashiato = \mixi\library\recent_ashiato();
	send_ashiato($from, $ashiato);
}


// Order the server to parse your most recent Mixi messages and send you a list of the last 10.
// Also parses the profiles of the users involved, so you can keep a copy in the db.
function refresh_mixi_messages($mail_connection, $message){
	
	$from = $message->from;
	$to = $message->to;
	check_valid_from($from);
	check_valid_list_to($to);

	$website = new Website();
	$website->cookies();
	\mixi\library\connect($website);

	$messages = \mixi\library\download_messages_and_profiles($website);
	$messages = \mixi\library\all_ashiato($website, 1);
	send($from, $messages);
}



// All errors are saved to the file define below.  Probably
// should move it to the CONSTANTS file...
function error($string){
	error_log($string, 3, "/tmp/FAILURE.txt");
}

// This is the cycle of the program.
// Login, Get list of messages,
// retrieve each message and
// then perform an action if necessary.


cycle();


?>
