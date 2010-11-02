<?php

require_once( dirname(__FILE__) . "/../website/website.upgrade.php");
require_once( dirname(__FILE__) . "/mixi.library.php");
require_once( dirname(__FILE__) . "/../email/pop3.php");

\mysql_connect(\db\constants\host(), \db\constants\user(), \db\constants\pass());
\mysql_select_db(\db\constants\database());
\mysql_query("SET NAMES UTF8;");

/*
$website = new \Website();
$website->cookies();
\mixi\library\connect($website);

$object = new \mixi\messages\obj();
$object->to = 13465281;
$object->subject= "Yumiko?";
$object->details = "Can I take Yumiko out on a date??"; // The answer is "fuck you."
$a = \mixi\library\send($website, $object)



$b = new \mixi\bbs\threads\obj();
$b->community = 13575;

$website->get($a->url(), \mixi\bbs\threads\community::get_vars($b));
$website->encode('EUC-JP','UTF-8');
$a->parse($website->html());
$a = new \mixi\bbs\threads\community();

$website = new Website();
$website->cookies();
\mixi\library\connect($website);

$o = new \mixi\bbs\messages\obj();
$o->thread_id = 2129705;
$o->community = 13575;
$o->page = 'all';
$website->get(\mixi\bbs\messages\get_list::url(), \mixi\bbs\messages\get_list::get_vars($o));
$website->encode('EUC-JP','UTF-8');
print_r(\mixi\bbs\messages\get_list::parse($website->html));



#\mixi\library\connect($website);
#print_r(all_ashiato($website, 1));

*/
#$website = new Website();
#$website->cookies();
#\mixi\library\connect($website);

print_r(\mixi\library\thread_posts(2497010));

/*
$b='';
$website->get(\mixi\bbs\threads\related::url(), \mixi\bbs\threads\related::get_vars($b));
$website->encode('EUC-JP','UTF-8');
$threads = \mixi\bbs\threads\related::parse($website->html());
print_r($threads);

*/

?>