<?php
/**
 * GPNFolder - класса работы с папками сервера
 *
 * It contains the following functions:
 * 
 *  - getRooms()
 *  - getFolderId()
 *  - getFolderName()
 *  - getFolderAuthorId()
 *  - getFolderAuthorName()
 *  - getFolderCreated()
 *  - getFolders()
 *  - createFolder($name)
 *  - deleteFolderName($inputFolder)
 *  - createFolderInfo() 
 *  - getFolderData() 
 *  - downloadFolderInfo()
 *  - updateFolderData($data)
 *  - updateFolderValues($array)
 *
 */
 
 
 require_once('GPNRoom.php');
 

class GPNFolder 
{
    private $file;
     
    private $id;
	private $name;
	private $authorID;
	private $authorName;
	private $folderCreated;
    
    private $room;
    private $inputFolder;
    
    
    // конструктор
    function __construct($login, $pass, $inputRoom, $inputFolder) 
    {
        $path = dirname(__FILE__).'/data/';
        $this->file = $path."foldersdata.json";
        $this->inputFolder = $inputFolder;
        
        $this->room = new GPNRoom($login, $pass, $inputRoom);    
        $this->createFolderInfo();
    }
    
    
    // функция возвращает класс работы с комнатой
    function getGNPRoom()
    {
		return $this->room;
	}
	
    
    // функция возвращает идентификатор папки 
    function getFolderId()
    {      
		return $this->id;
    }
    
    
    // функция возвращает название папки
    function getFolderName()
    {  
		return $this->name; 
	}
	
	 
	 // функция возвращает идентификатор автора
    function getFolderAuthorId()
    {  
		return $this->authorID; 
	}
	
	
	// функция возвращает имя автора
    function getFolderAuthorName()
    {  
		return $this->authorName;
	}
	
	
	// функция возвращает дату создания папки
    function getFolderCreated()
    {        
        return $this->folderCreated;
    }
    

	// функция возвращает данные папки из файла
    function getFolders()
    {                  
        if (file_exists($this->file))
			return file_get_contents($this->file, true);
		else 
			return NULL;
    }
    
    
    // функция создает новую папку на сервере
    function createFolder($name)
    {
		$sessionId = $this->room->getGNPSession()->getSessionId();
		$ownerFolderId = $this->room->getRoomFolderId();
		$roomId = $this->room->getRoomId();
		
        if (isset($sessionId) && isset($ownerFolderId) && isset($roomId)) 
        {
            $url  = 'newfolder?sessionID='.$sessionId.'&roomID='.$roomId.'&folderName='.urlencode($name).'&ownfolderID='.$ownerFolderId;
            $post = '';
            
            $httpClient = new GPNHttpClient();        
            $httpClient->sendPost($url, $post);
            
			if ($httpClient->isSuccess()) 
			{
				$this->downloadFolderInfo();
			}
            
            echo var_dump($httpClient->getResponse());
        }
	}
	
	
	// функция удаляет папку с сервера
    function deleteFolder($inputFolder)
    {
		$this->inputFolder = $inputFolder;
		$this->createFolderInfo();
		
		$sessionId = $this->room->getGNPSession()->getSessionId();
		$roomId = $this->room->getRoomId();
		
        if (isset($sessionId) && isset($roomId) && isset($this->id)) 
        {
            $url  = 'deletefolder?sessionID='.$sessionId.'&roomID='.$roomId.'&folderID='.$this->id;
            $post = '';
            
            $httpClient = new GPNHttpClient();        
            $httpClient->sendPost($url, $post);
            
			if ($httpClient->isSuccess()) 
			{
				$this->downloadFolderInfo();
			}
        }
	}
	
	
	// функция возвращает контент папки
    function getFolderContent() 
    {       
        $sessionId = $this->room->getGNPSession()->getSessionId();
		$roomId = $this->room->getRoomId();
		$folderId = $this->inputFolder == '' ? $this->room->getRoomFolderId() : $this->getFolderId();

        if (isset($sessionId) && isset($roomId) && isset($folderId)) 
        {
            $url  = 'content?sessionID='.$sessionId.'&roomID='.$roomId.'&folderID='.$folderId;
            
            $httpClient = new GPNHttpClient();        
            $httpClient->sendGet($url);
            
            if ($httpClient->isSuccess()) 
			{				
				$array = json_decode($httpClient->getResponse(), true);
				if (is_array($array)) 
				{
					$this->showFolderContent($array);
					return $array;
				}
			}
        }
    }
    
    
    // функция создает информацию о папке
    private function createFolderInfo()
    {
		if (!file_exists($this->file))
            $this->downloadFolderInfo();
		else 
		{		
			$array = $this->getFolderData(); 
			
			if (!$array)
				$this->downloadFolderInfo();
			                             
			if (is_array($array))
				$this->updateFolderValues($array);
		}
	}
	
	
	// функция возвращает данные папки из файла
    private function getFolderData()
    {                  
        if (file_exists($this->file))
			return json_decode(file_get_contents($this->file, true), true);
		else 
			return NULL;
    }
    
    
    // функция возвращает дочерние папки для текущей папки
    private function downloadFolderInfo()
    {	
		$sessionId = $this->room->getGNPSession()->getSessionId();
		$folderId = $this->room->getRoomFolderId();
		
        if (isset($sessionId) && isset($folderId)) 
        {
            $url  = 'childfolder?sessionID='.$sessionId.'&folderID='.$folderId;
            
            $httpClient = new GPNHttpClient();        
            $httpClient->sendGet($url);
            
            if ($httpClient->isSuccess()) 
			{
				$this->updateFolderData($httpClient->getResponse());
			}
        }
    }
 
    
    // функция обновляет данные папки
    private function updateFolderData($data)
    {   
        $array = json_decode($data, true);
        if (is_array($array))
        {
            file_put_contents($this->file, $data); 
            $this->updateFolderValues($array);         
        }
    }
    
    
    // функция обновляет переменные из массива
    private function updateFolderValues($array) 
    {
		foreach ($array as $childArray) 
		{
			if (is_array($childArray))
			{
				if ($childArray['folderName'] == $this->inputFolder)
				{
					$this->id = $childArray['folderID'];
					$this->name = $childArray['folderName']; 		
					$this->authorId = $childArray['authorID']; 
					$this->authorName = $childArray['authorName']; 
					$this->folderCreated = $childArray['folderCreated']; 
				}
			}
		}		 
	}


	// функция выводит ограниченный контент папки
	private function showFolderContent($array)
	{
		echo 'List files in folder "'.$this->inputFolder.'":'.chr(10);
		foreach ($array["contentData"] as $key => $value) 
		{
			echo $key.": " .$value['fileCreated'].'   '.$value['fileName'].chr(10).'   '.$value['fileHash'].chr(10);
		}
		echo chr(10);
	}
  
}

?>
