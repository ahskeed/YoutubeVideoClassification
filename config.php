<?php
 $dbh = mysql_connect('localhost','root','password') or die('Cannot connect to the database: '. mysql_error());
 $db_selected = mysql_select_db('YoutubeData') or die ('Cannot connect to the database: ' . mysql_error());
$project="ytdSpamDetection";
$prefix="YSD";
$numvideos=50;
$fdirectory="/home/deeksha/web_pages/TubeKit/ytdSpamDetection/flash";
$mdirectory="/home/deeksha/web_pages/TubeKit/ytdSpamDetection/mpeg";
$mpdirectory="tools/magpierss-0.72";
$ytdldirectory="/home/deeksha/web_pages/TubeKit/tools";
?>
