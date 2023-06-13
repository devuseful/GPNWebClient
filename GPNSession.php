<?php
/**
 * GPNSession - класс авторизации на сервере
 *
 * It contains the following functions:
 * 
 *  - getSessionId()
 *  - getSessionExpires()
 *  - setSessionLogOut()
 *  - changePasswod($newPassword)
 *  - createSessionInfo()
 *  - getSessionData()
 *  - isSessionExpires()
 *  - updateSessionData($data)
 *  - setAuth()
 *  - updateSessionValues($array) 
 *
 */

require_once('GPNHttpClient.php');


class GPNSession 
{
    private $login; 
    private $pass;
    private $id;
    private $expires;
    private $file;
    
    
    // конструктор
    function __construct($login, $pass) 
    {
        $this->login = urlencode($login);
        $this->pass = urlencode($pass);
        $path = dirname(__FILE__).'/data/';        
        $this->file = $path."session.json";
        
        $this->createSessionInfo();
    }
    
    
    // функция возвращает идентификатор сессии 
    function getSessionId()
    {      
		return $this->id;
    }

    
    // функция возвращает дату истечения сессии
    function getSessionExpires()
    {        
        return $this->expires;
    }
    
    
    // функция завершает сеанс сессии
    function setSessionLogOut()
    {
        if (!$this->id)
            return;

        $url  = 'logout?sessionID='.$this->id;
        $post = '';
        
        $httpClient = new GPNHttpClient();        
        $httpClient->sendPost($url, $post);
        
        if ($httpClient->isSuccess()) 
        {
			unlink($this->file);
		}
    } 
    
    
    // функция меняет пароль
    function changePasswod($newPassword)
    {
        if (!$this->id)
            return;

        $url  = 'changepass?sessionID='.$this->id;
        $post = 'oldPassword='.$this->pass.'&password='.urlencode($newPassword);
        
        $httpClient = new GPNHttpClient();        
        $httpClient->sendPost($url, $post);
        
        if ($httpClient->isSuccess()) 
        {
			$this->updateSessionData($httpClient->getResponse());
		}
    }

    
    // функция создает информацию о сессии
    private function createSessionInfo()
    {
		if (!file_exists($this->file))
            $this->setAuth();
		else 
		{
			$array = $this->getSessionData();                              
			if (is_array($array))
				$this->updateSessionValues($array);
			
			if ($this->isSessionExpires())
				$this->setAuth();
		}
	}
    
    
    // функция возвращает данные сессии из файла
    private function getSessionData()
    {                  
        if (file_exists($this->file))
			return json_decode(file_get_contents($this->file, true), true);
		else 
			return NULL;
    }
    
    
    // функция возвращает результат истекла сессия или нет
    private function isSessionExpires()
    {		
        $expTimeString = $this->getSessionExpires();       
        if (!isset($expTimeString))
            return true;
        
        $expTime = new DateTimeImmutable($expTimeString);
        $curTime = new DateTime("now", new DateTimeZone('Europe/Moscow'));        
        return $curTime > $expTime;
    }
    
    
    // функция обновляет данные сессии аутентификации
    private function updateSessionData($data)
    {   
        $array = json_decode($data, true);
        if (is_array($array))
        {
            file_put_contents($this->file, $data); 
            $this->updateSessionValues($array);         
        }
    }


    // функция выполняет аутентификацию на сервере
    private function setAuth() 
    {
        $url  = 'login';
        $post = 'login='.$this->login.'&password='.$this->pass;
        
        $httpClient = new GPNHttpClient();        
        $httpClient->sendPost($url, $post);
        
        if ($httpClient->isSuccess()) 
        {
			$this->updateSessionData($httpClient->getResponse());
		}
    }
    
    
    // функция обновляет переменные из массива
    private function updateSessionValues($array) 
    {
		$this->id = $array['sessionID'];
		$this->expires = $array['sessionExpires'];  
	}
 
}

?>
