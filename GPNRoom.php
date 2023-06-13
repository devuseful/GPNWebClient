<?php
/**
 * GPNRoom - класса работы с комнатами сервера
 *
 * It contains the following functions:
 * 
 *  - getRooms()
 *  - getGNPSession()
 *  - getRoomId()
 *  - getRoomExpires()
 *  - getRoomName()
 *  - getRoomType()
 *  - getRoomSize()
 *  - getRoomSizeUsed()
 *  - getRoomNumFiles()
 *  - createRoomInfo()
 *  - getRoomData()
 *  - isRoomExpires()
 *  - downloadRoomInfo()
 *  - updateRoomData($data)
 *  - updateRoomValues($array)
 *
 */
 
 
 require_once('GPNSession.php');
 

class GPNRoom 
{
    private $file;
    private $session;
    private $sessionId;
     
    private $id;
	private $name;
	private $type;
	private $size;
	private $sizeUsed;
	private $numFiles;
	private $expires;
	private $folderId;
    private $inputRoom;
    
    
    // конструктор
    function __construct($login, $pass, $inputRoom) 
    {
        $path = dirname(__FILE__).'/data/';
        $this->file = $path."roomdata.json";
        
		$this->session = new GPNSession($login, $pass);
		$this->sessionId = $this->session->getSessionId();
		$this->inputRoom = $inputRoom;
		
		$this->createRoomInfo();
    }
    
    
    // функция возвращает класс работы с сессией
    function getGNPSession()
    {
		return $this->session;
	}
    
    
    // функция возвращает идентификатор комнаты
    function getRoomId()
    {      
		return $this->id;
    }

    
    // функция возвращает дату истечения комнаты
    function getRoomExpires()
    {        
        return $this->expires;
    }
    
    
    // функция возвращает имя комнаты
    function getRoomName()
    {  
		return $this->name; 
	}
	
	 
	 // функция возвращает тип комнаты
    function getRoomType()
    {  
		return $this->type; 
	}
	
	
	// функция возвращает размер комнаты
    function getRoomSize()
    {  
		return $this->size;
	}
		 
	
	// функция возвращает используемый размер комнаты
    function getRoomSizeUsed()
    {  	 
		return $this->sizeUsed; 
	}
	
	
	// функция возвращает дату количества файлов комнаты
    function getRoomNumFiles()
    {  
		return $this->numFiles;
	}
	 
	
    // функция возвращает идентификатор папки комнаты
    function getRoomFolderId()
    {  
		return $this->folderId;	
	}
    
    
    // функция создает информацию о комнате
    private function createRoomInfo()
    {
		if (!file_exists($this->file))
            $this->downloadRoomInfo();
		else 
		{
			$array = $this->getRoomData();                              
			if (is_array($array))
				$this->updateRoomValues($array);
			
			if ($this->isRoomExpires())
				$this->downloadRoomInfo();
		}
	}
	
	
	// функция возвращает данные комнаты из файла
    private function getRoomData()
    {                  
        if (file_exists($this->file))
			return json_decode(file_get_contents($this->file, true), true);
		else 
			return NULL;
    }
    
    
    // функция возвращает результат истекла комната или нет
    private function isRoomExpires()
    {		
        $expTimeString = $this->getRoomExpires();
            
        if (!isset($expTimeString))
            return true;
        
        $expTime = new DateTimeImmutable($expTimeString);
        $curTime = new DateTime("now", new DateTimeZone('Europe/Moscow'));       
        return $curTime > $expTime;
    }
    
    
    // функция возвращает доступные комнаты для акаунта
    function downloadRoomInfo()
    {			
        if (isset($this->sessionId)) 
        {
            $url  = 'datarooms?sessionID='.$this->sessionId;
            
            $httpClient = new GPNHttpClient();        
            $httpClient->sendGet($url);
            
            if ($httpClient->isSuccess()) 
			{
				$this->updateRoomData($httpClient->getResponse());
			}
			
			var_dump($httpClient->getResponse());
        }
    }
 
    
    // функция обновляет данные комнаты
    private function updateRoomData($data)
    {   
        $array = json_decode($data, true);
        if (is_array($array))
        {
            file_put_contents($this->file, $data); 
            $this->updateRoomValues($array);         
        }
    }
    
    
    // функция обновляет переменные из массива
    private function updateRoomValues($array) 
    {
		foreach ($array as $childArray) 
		{
			if (is_array($childArray))
			{
				if ($childArray['roomName'] == $this->inputRoom)
				{
					$this->id = $childArray['roomID'];
					$this->name = $childArray['roomName']; 
					$this->type = $childArray['roomType']; 
					$this->size = $childArray['roomSize']; 
					$this->sizeUsed = $childArray['roomSizeUsed']; 
					$this->numFiles = $childArray['roomNumFiles']; 
					$this->expires = $childArray['roomExpires']; 
					$this->folderId = $childArray['rootFolderID'];
					
				}
			}
		}		 
	}
  
}

?>
