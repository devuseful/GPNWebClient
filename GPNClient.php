<?php
/**
 * GPNClient -  класс работы с сервером
 *
 * It contains the following functions:
 * 
 *  - downloadFiles()
 *  - uploadFile($fileName)
 *  - deleteFile($fileName)
 *  - downloadFile($url, $fileName)
 *
 */
 
 
 require_once('GPNFiles.php');
 
 
class GPNClient 
{
	private $inputRoom;
	private $inputFolder;
	private $login;
	private $password;
	private $files;
	
    
    // конструктор
    function __construct() 
    {
		$setting = include('config/credentials.php');	
		$this->login = $setting['login'];
		$this->password = $setting['password'];
		$this->inputRoom = $setting['roomName'];
    }
    
    
    // функция устанавливает папку сервера
    function setServerFolder($folderName)
    {
		$this->inputFolder = $folderName;
	}
    

	// функция скачивания файлов с сервера
	function downloadFiles()
	{
		$this->files = new GPNFiles($this->login, $this->password, $this->inputRoom, $this->inputFolder);
		$this->files->downloadFiles();
	}
	
	
	// функция скачивания файлов с сервера
	function downloadFile($flieName)
	{
		$this->files = new GPNFiles($this->login, $this->password, $this->inputRoom, $this->inputFolder);
		$this->files->downloadFile($flieName);
	}
	
	
	// функция загружает файлы на сервер
	function uploadFiles()
	{
		
	}
	
	
	// функция загружает файл на сервер
	function uploadFile($flieName)
	{
		$this->files = new GPNFiles($this->login, $this->password, $this->inputRoom, $this->inputFolder);
		$this->files->uploadFile($flieName);
	}
	
}


?>
