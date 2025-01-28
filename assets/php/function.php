<?php
require_once 'config.php';

$db=new mysqli($host,$username,$password,$db);
if($db->connect_error){
    die("Connection error");
}

//function to show pages
function showPage($page)
{
    $safePage = basename($page); // Prevent directory traversal
    include("./assets/pages/$safePage.php");
}

?>