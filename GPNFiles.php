<?php
/**
 * GPNFiles -  класса работы с файлами сервера
 *
 * It contains the following functions:
 * 
 *  - downloadFiles()
 *  - uploadFile($fileName)
 *  - deleteFile($fileName)
 *  - downloadFile($url, $fileName)
 *  - deleteAndSort(&$array)
 *  - usortMultiColumn($a, $b)
 *  - arrayUniqueKey($array, $key) 
 *
 */
 
 
 require_once('GPNFolder.php');
 

class GPNFiles 
{
	const MAX_DAY = 25;													// максимальное количество дней от текущей даты
	const MAX_TIME_OUT = 28800;											// максимальное время ожидания
	
	private $download;
	private $upload;
	private $folder;
	private $inputFolder;
	private $downloadData;
	private $arrayLoaded;
	
    
    // конструктор
    function __construct($login, $pass, $inputRoom, $inputFolder) 
    {
		$setting = include('config/folders.php');

		$path = $setting['absolute'] == true ? '' : dirname(__FILE__);	
        $this->download = $path.$setting['download'];
        $this->upload = $path.$setting['upload'];
        $this->inputFolder = $inputFolder;             
        
        $this->downloadData = dirname(__FILE__).'/data/downloaddata.json';
        $this->arrayLoaded = json_decode(file_get_contents($this->downloadData, true), true);
        $this->deleteAndSort($this->arrayLoaded);
               
        $this->folder = new GPNFolder($login, $pass, $inputRoom, $inputFolder);
    }
    
    
    // функция скачивает все фалы из папки
    // $isForce = true скачивать принудительно все последние файлы без проверок
    function downloadFiles($isForce = false) 
    {
		if ($isForce)
			unset($this->arrayLoaded);
			
		$this->downloadFile('');
    }
    
    
    // функция скачивает файл из папки
    function downloadFile($fileName) 
    {
		$isDownload = false;
        $sessionId = $this->folder->getGNPRoom()->getGNPSession()->getSessionId();  
        
        $url  = 'download?sessionID='.$sessionId;
        
        $folderData =  $this->folder->getFolderContent();
        if (!isset($folderData))
			return;
				       
        if (isset($this->arrayLoaded)) 
            $folderData["contentData"] = array_merge($folderData["contentData"], $this->arrayLoaded);
        
        $this->deleteAndSort($folderData['contentData']);
               
        foreach ($folderData['contentData'] as $data)
        {
            $fileMetaID = $data['fileMetaID'];             
            if ($fileName == $data['fileName'] || $fileName == '' && (!isset($data['isLoaded']) || $data['isLoaded'] !== true)) 
            {     
				if ($this->startDownloadFile($url.'&fileMetaID='.$fileMetaID, $this->download.$data['fileName']))
				{
					$isDownload = true;
					$data['isLoaded'] = true;
					$this->arrayLoaded[] = $data;
				}		
			}      
        }
        
        if ($isDownload)
			file_put_contents($this->downloadData, json_encode($this->arrayLoaded, true));
		else
			echo 'No files available to download.'.chr(10);
    }
    
        
    // функция закачивает файл в папку
    function uploadFile($fileName)    
    {		    
        $fullFileName = $this->upload.$fileName;
        
        $room = $this->folder->getGNPRoom();
        $sessionId = $room->getGNPSession()->getSessionId();   
        $roomId = $room->getRoomId();

        $folderId = $this->inputFolder == '' ? $room->getRoomFolderId() : $this->folder->getFolderId();
                
        $totalSize = filesize($fullFileName);
        $fileId = $folderId.'_'.$fileName.$totalSize; 
        
        if (isset($sessionId) && isset($roomId) && isset($fileId) && isset($fileName) && isset($totalSize) && isset($folderId))
        { 
			$url  = 'upload?sessionID='.$sessionId.'&chunkNumber=0&roomID='.$roomId.'&fileID='.urlencode($fileId)
					.'&fileName='.urlencode($fileName).'&totalChunks=1&totalSize='.$totalSize.'&folderID='.$folderId;
			
			$post = ["upload" => curl_file_create($fullFileName)];
		            
			$httpClient = new GPNHttpClient();        
            $httpClient->sendPost($url, $post);
            
            $this->showMessageInfo('Upload file - '.$fileName, $httpClient->isSuccess());
			echo var_dump($httpClient->getResponse());
		}
    }
    
    
    // функция удаляет файл папки с сервера 
    function deleteFile($fileName) 
    {
		$sessionId = $this->folder->getGNPRoom()->getGNPSession()->getSessionId();        
        $folderData =  $this->folder->getFolderContent();
		
		foreach ($folderData['contentData'] as $data)
        {
            $fileMetaID = $data['fileMetaID'];
            if ($fileName == $data['fileName'])
            {
				$url  = 'delete?sessionID='.$sessionId.'&fileMetaID='.$fileMetaID;
				
				$post = '';
						
				$httpClient = new GPNHttpClient();        
				$httpClient->sendPost($url, $post);
				
				$this->showMessageInfo('Delete file - '.$fileName, $httpClient->isSuccess());
				echo var_dump($httpClient->getResponse());
			}           
        }
	} 
    
    
    // функция запускает скачивание файла 
	private function startDownloadFile($url, $fileName)
    {   
        $options = array(	
						CURLOPT_FILE    => fopen($fileName, 'w'),
						CURLOPT_TIMEOUT => self::MAX_TIME_OUT,
						//CURLOPT_URL     => $url
		);

		$httpClient = new GPNHttpClient();        
		$httpClient->sendGet($url, $options);
		
		$this->showMessageInfo('Download file - '.$fileName, $httpClient->isSuccess());
		echo var_dump($httpClient->getResponse());
		
		return $httpClient->isSuccess();
    }
    
    
	// функция сортирует массив и удаляет дубликаты
	private function deleteAndSort(&$array)
	{
		$curTime = date('Y-m-d\TH:i:s', strtotime('-'.self::MAX_DAY.' day'));
	
		if (!isset($array))
			return;
	
		foreach ($array as $key => &$value) 
		{
			if (!isset($value['isLoaded']))
				$value['isLoaded'] = false;
			
			if ($value['fileCreated'] < $curTime) 
			{
				unset($array[$key]);
			}			
		}
			
		usort($array, array($this, "usortMultiColumn"));
		$array = $this->arrayUniqueKey($array, 'fileName');
	}
	
	
	// функция сортирует по нескольким колонкам массива
	private function usortMultiColumn($a, $b)
	{
		$diffName = strcmp($a['fileName'], $b['fileName']);
		if ($diffName)
			return $diffName;
		else
		{			
			$diffCreate = strtotime($b['fileCreated']) - strtotime($a['fileCreated']);
			if ($diffCreate)
				return $diffCreate;
			else 
				return $a['isLoaded'] < $b['isLoaded'];
		}
	}
	
	
	// функция оставляет только уникальные значения по ключу
	private function arrayUniqueKey($array, $key) 
	{ 
		$tmp = $key_array = array(); 
		$i = 0; 
		foreach($array as $val) 
		{ 
			if (!in_array($val[$key], $key_array)) 
			{ 
				$key_array[$i] = $val[$key]; 
				$tmp[$i] = $val; 
			} 
			$i++; 
		} 
		return $tmp; 
	}
    
    
    // функция оставляет только уникальные значения по ключу
	private function showMessageInfo($message, $isSuccess) 
	{
		$textResult = $isSuccess ? 'successfully' : 'unsuccessful'; 
		echo $message.'.  '.$textResult.chr(10);
	}
    
    
    
    
    
    
    
  
}

?>
