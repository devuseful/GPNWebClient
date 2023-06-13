<?php

require_once('GPNClient.php');

$action = filter_input(INPUT_GET, 'action');

if ($action == 'downloadfiles')
{
	$client = new GPNClient();
	$client->setServerFolder('download');
	$client->downloadFiles();
}

if ($action == 'uploadfiles')
{
	$client = new GPNClient();
	$client->setServerFolder('upload');
	$client->uploadFile('file.txt');
}

?>
