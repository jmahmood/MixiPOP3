<?php

/*
MixiLibrary 1.0
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


I wrote something like this a long time ago and wanted to see how it would look 
like if I was to use PHP 5.3 namespaces.

The base mixi namespace contains a series of low level functions that are used throughout the
subnamespaces, a collection of interfaces for common classes and a set of DB methods in a Factory
class. (Poor use of the term)

*/



namespace mixi;

require_once( dirname(__FILE__) . "/../website/website.upgrade.php");
require_once( dirname(__FILE__) . "/../constants.php");
require_once( dirname(__FILE__) . '/../simplehtmldom/simple_html_dom.php');


define("BASE_URL", "http://mixi.jp/");
define("HOME_URL", "http://mixi.jp/home.pl");

// Used for namespace obj classes.  This "extends" the basic class defined below.

function url_encode_array(&$array){
	$postvars = array();
	foreach($array as $key=>$value){
		if ($key != 'save') $postvars[] = \urlencode($key) . '=' . \urlencode($value);
	}
	$string = \implode('&', $postvars);
	return $string;
}


function decode_date($japanesedatetime){
	$japanesedatetime = mb_convert_kana($japanesedatetime, "n");
	
	$y = "(20[0-9]+)年";
	$m = "(0?[1-9]|1[012])月";
	$d = "(0?[1-9]|[12][0-9]|3[01])日";
	$t = "(\d{2}):(\d{2})";
	$ignore = '.*?';
	
	$japanese_datetime_regex = '#' .  $y . $m . $d . $ignore . $t . '#uims';
	$japanese_date_regex = '#' .  $m . $d . '#uims';
	
	$year=$month=$day=$hour=$minute = 0;
	$hour = '00';
	$minute = '00';
	
	if (preg_match_all($japanese_datetime_regex, $japanesedatetime, $footprints) > 0 ){
	    $year = $footprints[1][0];
	    $month = $footprints[2][0];
	    $day = $footprints[3][0];
	    $hour = $footprints[4][0];
	    $minute = $footprints[5][0];
	}
	
	elseif (preg_match_all($japanese_date_regex, $japanesedatetime, $footprints) > 0 ){
	    $year = date('Y');
	    $month = $footprints[1][0];
	    $day = $footprints[2][0];
	}
	
	else{
	    return $japanesedatetime;
	}
	
	return $year .  '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute;
}




interface ns_class{
	function ns(); // Returns the namespace, used to save / load the object values.
	function id(); // Returns the ID of the object
	function set_id($id); // Sets the ID of the object
	function __construct($id=false); // Objects without an ID are ignored, etc..
}

interface ns_spider_list{
	// A list generally doesn't have an object associated with it.
	// This is more likely to serve values that are constants.
	function url(); 
	function post_vars($page); 
	function get_vars($page); 
	function parse($html); // Converts $html to array[$obj,$obj...].
	
}

interface ns_spider{
	function url($obj); // Returns the underlying rl needed to spider the website.
	function post_vars($obj); // Return the post vars needed to execute the spider.
	function get_vars($obj); // Return the get vars needed to execute the spider.
	function parse($html); // Converts $html to $obj.
}


interface ns_output{
	function __tostring();
	function __construct($obj);
}

class Factory{
	
	static function escape($value){
		$format = "'%s'";
		return \sprintf($format, \mysql_real_escape_string($value));
	}
	
	static function quotes($str){
		return "`$str`";
	}
	
	static function extract_table_name($obj){
		return array_pop(explode("\\", $obj->ns()));
	}
	
	static function prep_keys($array){
		$keys = \array_keys($array);
		$keys = \array_map('mysql_real_escape_string', $keys);
		$keys = \array_map('\mixi\Factory::quotes', $keys);
		return \implode($keys, ',');
	}
	
	static function prep_vals($array){
		$vals = \array_values($array);
		$vals = \array_map('\mixi\Factory::escape', $vals);
		return \implode($vals, ',');
	}

	static function save($object){
		if (!($object instanceof ns_class)){
			throw new \Exception("Factory Save only works on classes that extend ns_class");
		}
		$vars = \get_object_vars($object);
		$table = self::extract_table_name($object);
		$keys = self::prep_keys($vars);
		$vals = self::prep_vals($vars);
		$query = "INSERT IGNORE INTO $table ($keys) VALUES ($vals)";
		
		mysql_query($query) or die(mysql_error());
	}
	
	static function load($object, $id=false){
		if (!$id) return self::load_all($object);
		

		$table = self::extract_table_name($object);
		$query = "SELECT * FROM $table WHERE id=%d";
		$query = sprintf($query, (int) $id);
		$results = mysql_query($query) or die(mysql_error());
		$return = array();
		while ($result = mysql_fetch_assoc($results)){
			$return[] = $result;
		}
		return $return;
	}
	
	static function load_all($object){
		$table = self::extract_table_name($object);
		$query = "SELECT * FROM $table";
		$results = mysql_query($query) or die(mysql_error());
		$return = array();
		while ($result = mysql_fetch_assoc($results)){
			$return[] = $result;
		}
		return $return;
	}
	
	static function init(&$object, $array){
		if (!($object instanceof ns_class)){
			throw new \Exception("Factory Save only works on classes that extend ns_class");
		}
		$vars = \get_object_vars($object);
		$keys = array_keys($vars);
		foreach($keys as $key){
			if (isset($array[$key])){
				$object->$key = $array[$key];
			}
		}
	}
}


// This class stores everything related to extracting someone's profile.
namespace mixi\profile;

class obj implements \mixi\ns_class{
	public $mixi_id, $name, $sex, $location, $age, $birthday, $bloodtype, $hometown, $hobby, $introduction, $profile_picture1, $profile_picture2, $profile_picture3, $foods, $job, $belong_to;

	function __construct($id=false){ if ($id){ $this->set_id($id);} }
	function set_id($id){ $this->mixi_id = $id; }
	function id(){ return $this->mixi_id; }
	function ns(){ return __NAMESPACE__; }
	function image(){ return $profile_picture1; }
}


class get_profile implements \mixi\ns_spider{
	// This retreives the full profile, given an id.
	function url($obj){ return 'show_friend.pl'; }
	function post_vars($obj){ return false; }
	function get_vars($obj){
		$array = array( 'id'=>$obj->id() );
		return \mixi\url_encode_array($array);
	}
	
	private function convert_mixi_name_to_variable_name($key){
		$key = trim(strip_tags($key));
		$array = array(
		'名前'=>'name',	
		'性別'=>'sex',	
		'現住所'=>'location',	
		'年齢'=>'age',	
		'誕生日'=>'birthday',
		'血液型'=>'bloodtype',
		'出身地'=>'hometown',
		'趣味'=>'hobby',
		'自己紹介'=>'introduction',
		'好きな食べ物・飲み物'=>'foods',
		'職業'=>'job',
		'所属'=>'belong_to',
		'好きな映画'=>'movies',
		'好きなテレビ番組'=>'tv',
		'好きな音楽'=>'music',
		'好きな本・マンガ'=>'bookmanga',
		'好きな休日の過ごし方'=>'holiday',
		'好きな有名人'=>'celebrity',
		'好きなスポーツ'=>'sports',
		'好きなペット'=>'pet',
		'好きなアート'=>'art',
		'mixiキーワード'=>'mixikeyword',
		'好きな言葉'=>'quote',
		'好きな観光地'=>'landmark', // favourite place to visit
		'好きなゲーム'=>'game',
		'好きなギャンブル'=>'gambling',
		'好きなアウトドア'=>'outdoors',
		'好きな習いごと'=>'learn'
		
		
		);
		if (isset($array[$key]))
		    return $array[$key];
		throw new \Exception("Unexpected key name: $key");
	}

	function parse($html){
		$parsed_html = \str_get_html($html);
		
		$profile_array = array();
		
		foreach ($parsed_html->find('div[id=profile] tr') as $ret){
			$key = self::convert_mixi_name_to_variable_name($ret->children(0)->innertext);
			$val = $ret->children(1)->innertext;
			$profile_array[$key] = $val;
		}

		// Extract photo,.
		$ret = $parsed_html->find('p[class=photo] img');
		if (\count($ret) < 1) throw new \Exception("It appears that the html wasn't prepared as expected. \n Can't find photo <p> tag.", 1093);
		$photo_url = $ret[0]->src;
		
		$profile_array['profile_picture1']=$photo_url;

		return $profile_array;
	}
	function harmonize(&$obj, $html){
		$array = self::parse($html);
		\mixi\Factory::init($obj, $array);
	}
}


// This namespace stores everything related to messages, including
// getting messages, posting messages, and making a list of messages.
namespace mixi\messages;

define("MESSAGES_URL", "http://mixi.jp/list_message.pl");
define("SEND_MESSAGE_URL", "http://mixi.jp/send_message.pl");



class obj implements \mixi\ns_class{
	public $miximessage_id, $page, $datetime, $to, $from, $subject, $details, $url, $box;
	// $box represents in/outbox, which is needed to access the message.
	// We are only going to be using the inbox, for now.

	function __construct($id=false){ if ($id){ $this->set_id($id);} }
	function set_id($id){ $this->miximessage_id = $id; }
	function id(){ return $this->miximessage_id; }
	function ns(){ return __NAMESPACE__; }
}

class all {
	var $messages;
	function __construct(){
		$message_array = \mixi\Factory::load($this);
		$this->messages = array();
		foreach($message_array as $m_a){
			$a = new obj();
			\mixi\Factory::init($a, $m_a);
			$this->messages[] = $a;
		}
	}
	
	function ns(){ return __NAMESPACE__; }
	function get(){
		return $this->messages;
	}
}

class last_ten{
	var $messages;
	function __construct(){
		$message_array = \mixi\Factory::load($this);
		$message_array = array_reverse($message_array);
		$message_array = array_chunk($message_array, 10);
		$message_array = reset($message_array);

		$this->messages = array();
		foreach($message_array as $m_a){
			$a = new obj();
			\mixi\Factory::init($a, $m_a);
			$this->messages[] = $a;
		}
	}
	
	function ns(){ return __NAMESPACE__; }
	function get(){
		return $this->messages;
	}
	
}

class get_list implements \mixi\ns_spider_list{
	// Extracts a list of all messages in your inbox.
	function url(){ return "http://mixi.jp/list_message.pl"; }
	function get_vars($page){ return array('page'=>$page,'box'=>'inbox'); }
	function post_vars($page){ return array(); }
	
	function parse($html){
		$status_regex  = '<tr class=[^>]+>[^<]+<td class="status"><input[^>]+><img[^>]+></td>[^<]+';
		$sender_regex  = '<td class="sender">(.*?)</td>[^<]+';
		$subject_regex = '<td class="subject"><a href="(.*?)">([^<]+)</a></td>[^<]+';
		$date_regex    = '<td class="date">([^<]+)<';
		$messages_regex = '#' . $status_regex . $sender_regex . $subject_regex . $date_regex . '#ims';
		\preg_match_all($messages_regex, $html, $messages);
		$messages[1] = \array_map("trim", $messages[1]);
		$senders = $messages[1];
		$urls = $messages[2];
		$subjects = $messages[3];
		$dates = $messages[4];
		
		$total = \count($senders);
		$array = array();
		for($i=0; $i<$total; $i++){
			$init_array = array('type'=>'pm');
			$init_array['from'] = $senders[$i];
			$init_array['url'] = $urls[$i];
			$url = $urls[$i];
			\parse_str(\parse_url($urls[$i], \PHP_URL_QUERY), $parts);
			$init_array['miximessage_id'] = $parts['id'];
			$init_array['box'] = $parts['box'];
			$init_array['page'] = $parts['page'];
			$init_array['subject'] = $subjects[$i];
			$init_array['datetime'] = \mixi\decode_date($dates[$i]);
			$obj = new obj();
			\mixi\Factory::init($obj, $init_array);
			\array_push($array, $obj);
		}
		return $array;
	}
}

class get_message implements \mixi\ns_spider{
	// This retreives the full message, given basic information from the mixi class.
	function url($obj){ return 'view_message.pl'; }
	function post_vars($obj){ return false; }
	function get_vars($obj){
		$array = array( 'id'=>$obj->miximessage_id, 'box'=> $obj->box );
		return \mixi\url_encode_array($array);
	}
	function parse($html){
		$parsed_html = \str_get_html($html);
		$ret = $parsed_html->find('div[id=message_body]');
		if (\count($ret) < 1) throw new \Exception("It appears that the html wasn't prepared as expected. \n $html", 893);
		$body = $ret[0]->innertext;
		$parsed_html = \str_get_html($html);
		$mmi = $parsed_html->find('form[action^=reply_message.pl?reply_message_id]');
		$mixi_message_id = $mmi[0]->action;
		\parse_str(\parse_url($mixi_message_id, \PHP_URL_QUERY), $array);

		$mmi = $parsed_html->find('a[href^=show_friend.pl?]');
		\parse_str(\parse_url($mmi[1]->href, \PHP_URL_QUERY), $array2);

		$mmi = $parsed_html->find('a[href^=http://mixi.jp/show_profile.pl?]');
		\parse_str(\parse_url($mmi[0]->href, \PHP_URL_QUERY), $array3);

		return array('details'=>$body, 'mixi_message_id'=>$array['reply_message_id'], 'from'=>$array2['id'], 'to'=>$array3['id']);
	}
	function harmonize(&$obj, $html){
		$array = self::parse($html);
		\mixi\Factory::init($obj, $array);
	}
}

class post_message implements \mixi\ns_spider{
	// This posts a message.
	function url($obj){ return 'send_message.pl'; }
	function post_vars($obj){
		// Retrieve url: 'send_message.pl'
		// extract values.
		// return post.
		$array['id'] = $obj->to;
		$array['subject'] = $obj->subject;
		$array['body'] = $obj->details;
		return $array;
	}
	function get_vars($obj){ return array( 'ref'=>'list_message'); }
	function parse($message_html){
		$html = str_get_html($message_html);
		//
		$ret = $html->find('form[action=send_message.pl]',0);
		if (count($ret) < 1){
			echo $message_html;
			throw new \Exception("Could not decrypt the send message form in the HTML that was returned.");
		}

		$array = $ret->find('input');
		$return = array();
		foreach($array as $a){
			$return[$a->name] = $a->value;
		}
		return $return;
	}
}


class post_reply implements \mixi\ns_spider{
	// Creates a reply array for a message.
	// (The array body and subject need to be edited seperately)
	function url($obj){ return 'send_message.pl'; }

	function post_vars($obj){
		// Retrieve url: 'send_message.pl?reply_message_id=(id)&id=(from)'
		// extract values.
		// return post.
		return $array;
	}
	function get_vars($obj){ return array( 'message_id'=>$obj->id, 'id'=>$obj->from); }
	function parse($html){ return false; }
}

class output implements \mixi\ns_output{
	var $obj;
	function __toString(){ echo "<div class='subject'>" . $this->obj->subject . "</div><div class='details'>" . $this->obj->details . "</div>"; }
	function __construct($obj){ $this->obj = $obj; echo($this);}
}

// This class stores all information needed to get a list of ashiato.
// It is based on regular expressions, instead of the HTML Parser we
// started using.
namespace mixi\ashiato;

class obj implements \mixi\ns_class{
	public $from, $relationship, $datetime;
	function __construct($id=false){ if ($id){ $this->set_id($id);} }
	function set_id($id){ $this->miximessage_id = $id; }
	function id(){ return $this->miximessage_id; }
	function ns(){ return __NAMESPACE__; }
}

class get_list implements \mixi\ns_spider_list{
	// Extracts a list of all messages in your inbox.
	function url(){ return "http://mixi.jp/show_log.pl"; }
	function get_vars($page){ return array('page'=>$page); }
	function post_vars($page){ return array(); }
	function parse($html){
		$contents = explode('<ul class="logList01">', $message_content, 2);
		$content = $contents[1];
		$contents = explode('</ul>', $content, 2);
		$content = $contents[0];

		$date_regex  = '<span class="date">([^<]+)</span>(.*?)';
		$name_regex  = 'show_friend.pl\?id=(.*?)">([^<]+)</a>';
		$messages_regex = '#' .  $date_regex . $name_regex .'#uims';
		preg_match_all($messages_regex, $content, $footprints);
		
		$footprints[1] = array_map("trim", $footprints[1]);

		$date = $footprints[1];
		$userid = $footprints[3];
		$username = $footprints[4];
	
		$total = count($date);
		$ashiato_array = array();
		
		for($i=0; $i<$total; $i++){
			$init_array = array();
			$init_array['from'] = $userid[$i];
			$init_array['datetime'] = $date[$i];

			$ashiato = new obj($init_array);
			array_push($ashiato_array, $ashiato);
		}
		return $ashiato_array;
	}
}

// This namespace will store all classes related to dealing with blogging on Mixi.
namespace mixi\blog;
define("DIARY_URL", "http://mixi.jp/new_friend_diary.pl");

// This namespace will store all classes related to dealing with message forums on Mixi.
namespace mixi\bbs;
define("MIXI_THREADS_URL", "http://mixi.jp/new_bbs.pl");

// This namespace stores all classes related to dealing with logging into Mixi.
namespace mixi\login;

define("URL", "http://mixi.jp/login.pl");

// Perform Login on the Mixi.
// Probably should be careful about how much we do this.
class exec{
	static function url(){
		return URL;
	}
	static function get_vars(){
		return array();
	}
	static function post_vars(){
		return array('sticky'=>'ON','next_url'=>'\home.pl','email'=>\mixi\constants\email(), 'password'=>\mixi\constants\password(), 'x'=>0, 'y'=>0);
	}
}


// This namespace stores less low-level functions.
namespace mixi\library;

// Connects to the Mixi website (if you change usernames / your session times out)
function connect($website){
	$url = \mixi\login\exec::url();
	$post_vars = \mixi\login\exec::post_vars();
	$post_vars = \mixi\url_encode_array($post_vars); // Post_vars array is converted into a string.
	$website->post($url, $post_vars);
}


// This retrieves a set of messages.  These are in an incomplete state,
// lacking details.  These details can be retrieved one at a time.
function all_messages($website,$page){
	$url = \mixi\messages\get_list::url();
	$get_vars = \mixi\messages\get_list::get_vars($page);
	$get_vars = \mixi\url_encode_array($get_vars);
	$website->get($url, $get_vars);
	$html = mb_convert_encoding($website->html(), "UTF-8", "EUC-JP");
	$message_array = \mixi\messages\get_list::parse($html); // Parses the HTML
	return $message_array;
}

function message_details($website, &$object){
	$url = 'http://www.mixi.jp/' . \mixi\messages\get_message::url($object);
	$get_vars = \mixi\messages\get_message::get_vars($object);
	$website->get($url, $get_vars);
	$html = $website->html();
	$html = mb_convert_encoding($website->html(), "UTF-8", "EUC-JP");
	\mixi\messages\get_message::harmonize($object, $html); // Parses HTML and inserts it into the object.
}

function send(&$website, $object){
	$url = 'http://mixi.jp/' . \mixi\messages\post_message::url($object);

	$website->get($url);
	$html = $website->html();
	
	$parse_array = \mixi\messages\post_message::parse($html);
	$post_array = \mixi\messages\post_message::post_vars($object);
	
	$array = array_merge($parse_array, $post_array); // The order of these two is important; post_array contains the real values we need to make the post.

	$post_vars = \mixi\url_encode_array($array);
	$website->post($url,$post_vars);
	
	// This is done twice to extract the CONFIRM code.
	$html = $website->html();

	$parse_array = \mixi\messages\post_message::parse($html);
	unset($parse_array['no']);

	$post_vars = \mixi\url_encode_array($parse_array);
	$website->post($url,$post_vars);

}

// Incomplete.
function all_ashiato($website, $page){
	$url = 'http://www.mixi.jp/' . \mixi\ashiato\get_list::url();
	$get_vars = \mixi\ashiato\get_list::get_vars($page);
	$get_vars = \mixi\url_encode_array($get_vars); 
	//$html = $website->get($url, $get_vars);
	//$ashiato_array = \mixi\ashiato\get_list::parse($html);
	//foreach($ashiato_array as $ashiato){
	//	\mixi\Factory::save($ashiato);
	//}
	//
	// Retrives and saves a set of Ashiato.
}

function download_user_profile(&$website, $id){
	// Retrieves the To and From from $obj.
	$mp = new \mixi\profile\obj($id);
	$url = 'http://www.mixi.jp/' . \mixi\profile\get_profile::url($mp);
	$get_vars = \mixi\profile\get_profile::get_vars($mp);
	$website->get($url, $get_vars);
	$html = $website->html();
	$html = mb_convert_encoding($html, "UTF-8", "EUC-JP");
	\mixi\profile\get_profile::harmonize($mp, $html);
	return $mp;
}

function download_user_profile_image(&$website, $object){
	$image_url = $object->image();
	$website->get_file($image_url);
	$array = \parse_url($image_url, \PHP_URL_PATH);
	save_binary_file($website, '/home/jawaad/test.jpg');
}

function save_binary_file(&$website, $filename){
	$fh = fopen("$filename", 'w+');
	fwrite($fh, $website->html());
	fclose($fh);
}

function download_profile_if_new(&$website, $id){
	$q = "SELECT mixi_id FROM profile WHERE mixi_id=%d";
	$q = sprintf($q, $id);
	$r = mysql_query($q) or die(mysql_error());
	if (mysql_num_rows($r) == 0){
		try{
			$profile = \mixi\library\download_user_profile($website, $id);
			\mixi\Factory::save($profile);
		}
		catch(\Exception $e){
			echo "Could not download profile $id.  Does it no longer exist?";
			sleep(2);
		}
	}
}

function download_messages_and_profiles(&$website){
	$messages = all_messages($website, 1);
	foreach($messages as &$message){
		\mixi\library\message_details($website, $message);
		sleep(1);
		download_profile_if_new($website, $message->from);
		\mixi\Factory::save($message);
		sleep(1);
	}
	return $messages;
}


function recent(){
	$prep = new \mixi\messages\last_ten();
	return $prep->get();
}

/*
$website = new \Website();
$website->cookies();
\mixi\library\connect($website);

$object = new \mixi\messages\obj();
$object->to = 13465281;
$object->subject= "Yumiko?";
$object->details = "Can I take Yumiko out on a date??"; // The answer is "fuck you."
$a = \mixi\library\send($website, $object)
*/


?>
