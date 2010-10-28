<?php
require_once('mixi.library.php');

/*
$website = new \Website();
$website->cookies();
\mixi\library\connect($website);

$object = new \mixi\messages\obj();
$object->to = 13465281;
$object->subject= "Yumiko?";
$object->details = "Can I take Yumiko out on a date??"; // The answer is "fuck you."
$a = \mixi\library\send($website, $object)


$website = new \Website();
$website->cookies();

$b = new \mixi\bbs\threads\obj();
$b->community = 13575;

$website->get($a->url(), \mixi\bbs\threads\community::get_vars($b));
$website->encode('EUC-JP','UTF-8');
$a->parse($website->html());
$a = new \mixi\bbs\threads\community();
*/
$a = new \mixi\bbs\messages\get_list();
$info = file_get_contents('/home/jawaad/Text-1.html');
print_r($a->parse($info));


#\mixi\library\connect($website);
#print_r(all_ashiato($website, 1));


?>