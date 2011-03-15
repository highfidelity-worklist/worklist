<?php
include "config.php";
include "classes/User.class.php";
include "sandbox-util-class.php";

if (!mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD)) {
    throw new Exception('Error: ' . mysql_error());
}
if (!mysql_select_db(DB_NAME)) {
    throw new Exception('Error: ' . mysql_error());
}

$name = "alexi !!";

$unixname = User::generateUnixusername($name);

echo "$name -> $unixname \n";

if (SandBoxUtil::inPasswdFile($unixname)) {
    print "* in pw file\n";
}
if (User::unixusernameExists($unixname)) {
    print "* in database\n";
}
?>
