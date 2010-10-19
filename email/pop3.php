<?php

/*
JPOP3 1.0
© Copyright 2010 Jawaad Mahmood

    JPOP3 is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JPOP3 is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with JPOP3.  If not, see <http://www.gnu.org/licenses/>.

    http://www.gnu.org/licenses/gpl.txt


This is based on some comments I saw on the PHP5 help site.  I've gone and converted it into 
a PHP class, but I'm not done with it.

It requires the PEAR Mail class to function properly.

*/



require_once "Mail.php";
require_once "Mail/mime.php";

require_once(dirname(__FILE__) . '/../constants.php');


class pop3{
  var $host, $port, $ssl, $folder, $user, $pass;
  var $connection;
  var $stats;
  var $overview, $results;

  function __construct(&$array=false){
    $this->default_settings();
    if ($array) $this->init($array);
  }
  
  function purge(){
    imap_expunge($this->connection);
  }

  private function default_settings(){
    $this->folder = "INBOX";
    $this->ssl = false;
    $this->host = mail\constants\host();
    $this->port = mail\constants\port();
    $this->user = mail\constants\login();
    $this->pass = mail\constants\pass();
  }

  function init($array){
    $values = array('folder','ssl','host','port','user','pass');
    foreach($values as $value) if (isset($array[$value])) $this->$value = $array[$value];
  }


  function login(){ 
    $imap_open_format = '{%s:%d/pop3%s}';
    $iop = sprintf($imap_open_format, $this->host, $this->port,
	$this->ssl==false?"/novalidate-cert":"", $this->folder);
    $this->connection = imap_open($iop,$this->user, $this->pass);
  }

  function stats(){ 
    $this->stats = imap_mailboxmsginfo($this->connection); 
  } 

  function overview($message=false){ 
    if ($message){ 
        $range=$message; 
    }
    else {
      if (!$this->stats) $this->stats();
      $range = "1:" . $this->stats->Nmsgs;
    } 
    $this->overview = imap_fetch_overview($this->connection,$range);
  } 

  function retrieve_header($message) 
  {
    if (!is_object($message)){
      throw new Exception("Must pass the message object obtained through overview to retrieve the message header");
    }
    return(imap_fetchheader($this->connection,$message->uid,FT_UID)); 
  } 

  function retrieve_body($message) 
  {
    if (!is_object($message)){
      throw new Exception("Must pass the message object obtained through overview to retrieve the message header");
    }
    $a = imap_fetchbody($this->connection,$message->uid, 2);
    if (!empty($a)) return $a;
    return imap_fetchbody($this->connection,$message->uid, 1);

  } 
  function delete($message) 
  { 
      return(imap_delete($this->connection,$message->uid)); 
  } 

  private function mail_parse_headers($headers) 
  { 
      $headers=preg_replace('/\r\n\s+/m', '',$headers); 
      preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches); 
      foreach ($matches[1] as $key =>$value) $result[$value]=$matches[2][$key]; 
      return($result); 
  } 

  function mail_mime_to_array($imap,$mid,$parse_headers=false) 
  { 
      $mail = imap_fetchstructure($imap,$mid); 
      $mail = mail_get_parts($imap,$mid,$mail,0); 
      if ($parse_headers) $mail[0]["parsed"]=mail_parse_headers($mail[0]["data"]); 
      return($mail); 
  } 

  function mail_get_parts($imap,$mid,$part,$prefix) 
  {    
      $attachments=array(); 
      $attachments[$prefix]=mail_decode_part($imap,$mid,$part,$prefix); 
      if (isset($part->parts)) // multipart 
      { 
	  $prefix = ($prefix == "0")?"":"$prefix."; 
	  foreach ($part->parts as $number=>$subpart) 
	      $attachments=array_merge($attachments, mail_get_parts($imap,$mid,$subpart,$prefix.($number+1))); 
      } 
      return $attachments; 
  } 
  function mail_decode_part($connection,$message_number,$part,$prefix) 
  { 
      $attachment = array(); 
  
      if($part->ifdparameters) { 
	  foreach($part->dparameters as $object) { 
	      $attachment[strtolower($object->attribute)]=$object->value; 
	      if(strtolower($object->attribute) == 'filename') { 
		  $attachment['is_attachment'] = true; 
		  $attachment['filename'] = $object->value; 
	      } 
	  } 
      } 
  
      if($part->ifparameters) { 
	  foreach($part->parameters as $object) { 
	      $attachment[strtolower($object->attribute)]=$object->value; 
	      if(strtolower($object->attribute) == 'name') { 
		  $attachment['is_attachment'] = true; 
		  $attachment['name'] = $object->value; 
	      } 
	  } 
      } 
  
      $attachment['data'] = imap_fetchbody($connection, $message_number, $prefix); 
      if($part->encoding == 3) { // 3 = BASE64 
	  $attachment['data'] = base64_decode($attachment['data']); 
      } 
      elseif($part->encoding == 4) { // 4 = QUOTED-PRINTABLE 
	  $attachment['data'] = quoted_printable_decode($attachment['data']); 
      } 
      return($attachment); 
  } 


  static function send_mail($to, $subject, $body){
	
	$message = new Mail_mime("\n");
	$message->setTXTBody(strip_tags($body));
	$message->setHTMLBody($body);
	$body = $message->get(array('text_charset' => 'utf-8', 'html_charset'=>'utf-8'));

        $headers = $message->headers(array('From' => \mail\constants\from(), 'Subject' => $subject));
        $smtp = Mail::factory('smtp', array ('host' => \mail\constants\host(), 'port'=>\mail\constants\sendport(), 'auth' => true, 'username' => \mail\constants\login(), 'password' => \mail\constants\pass()));
        $mail = $smtp->send($to, $headers, $body);

        if (PEAR::isError($mail)) {
		throw new Exception("<p>" . $mail->getMessage() . "</p>");
         } else {
          echo("<p>Message successfully sent!</p>");
         }
	
   
  }
}
