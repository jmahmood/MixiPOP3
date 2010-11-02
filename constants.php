<?php

namespace db\constants;
define("HOST", "localhost");
define("USER", "dbusername");
define("PASS", "dbpassword");
define("DATABASE", "databasename");
function host(){
    return HOST;
}
function user(){
    return USER;
} 
function pass(){
    return PASS;
}
function database(){
    return DATABASE;
}


namespace mixi\constants;

define("EMAIL", "lol.email@example.com");
define("PASSWORD", "password");

function email(){
    return EMAIL;
}
function password(){
    return PASSWORD;
}

namespace mail\constants;

define("GMT_OFFSET",''); // Timezone offset.  Will be used in the pop3 class eventually.
define("M_HOST",'mail.example.com'); // The pop host that you will be using.  (Doubles as an SMTP host in the code right now, needs to be patched)
define("M_FROM", 'NAVI (Mixi Pop3) < navi@example.com >'); // The address from which you receive all responses.
define("M_LOGIN",'mail_login'); // The login for the email.
define("M_PASS",'password'); // The EMail password
define("M_PORT",''); // POP3 port
define("M_SEND_PORT", '587'); // SMTP port

define("LIST_EMAIL", ''); // The address from which you want "Mixi message list"emails to be sent from.

function sendport(){
    return M_SEND_PORT;
}

function host(){
    return M_HOST;
}
function port(){
    return M_PORT;
}

function pass(){
    return M_PASS;
}

function login(){
    return M_LOGIN;
}

function from(){
    return M_FROM;
}

function list_email_address(){
    return LIST_EMAIL;
}

function valid_from_addresses(){
    return array('My name <validemail@example.com>','validemail@example.com', 'anothervalidemail@example.com');
}

?>