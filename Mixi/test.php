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

*/


$website = new \Website();
$website->cookies();
$a = new \mixi\bbs\get_community_threads();

$website->get($a->url(), \mixi\bbs\get_community_threads::get_vars($a));
$website->encode('EUC-JP','UTF-8');
$a->parse($website->html());

#\mixi\library\connect($website);
#print_r(all_ashiato($website, 1));


?>