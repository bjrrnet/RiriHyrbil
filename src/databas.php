<?php 
$servername = "sql307.infinityfree.com"; 
$username = "if0_41069409"; 
$password = "H4lxKbPj1O5"; 
$dbname = "f0_41069409_databas"; 
$conn = new mysqli($servername, $username, $password, $dbname); 
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); } ?>
