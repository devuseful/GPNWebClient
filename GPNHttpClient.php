<?php
/**
 * GPNHttpClient - класс для отправки запросов на сервер
 *
 * It contains the following functions:
 * 
 *  - isSuccess()
 *  - getResponse()
 *  - getHttpCode()
 *  - getCurlInfo()
 *  - sendPost($url, $post)
 *  - sendGet($url, $options = null)
 *  - showError()
 *
 */

class GPNHttpClient 
{
	private $response;
	private $httpCode;
    private $curlInfo;
    private $cookes;
    private $proxy;
    private $setting;
    private $debug;
    
    
    // конструктор
    function __construct() 
    {
		$path = dirname(__FILE__).'/data/';
		$this->cookes = $path."cookes.txt";
		$this->debug = $path."debug.txt";
		$this->setting = include('config/setting.php');
    }
    
    
    // функция возвращает результат успешный запрос или нет
    function isSuccess()
    {
		return $this->httpCode === 200;
	}
    
    
    // функция возвращает текст ответа сервера
    function getResponse()
	{
		return $this->response;
	}
    
    
    // функция возвращает код ответа сервера
    function getHttpCode()
    {
		return $this->httpCode;
	}
    
    
    // функция возвращает информацию запроса
    function getCurlInfo()
    {
		return $this->curlInfo;
	}
    
    
    // функция отправляет Post запрос на сервер
    function sendPost($url, $post)
    {	
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookes);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookes);
        
        
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, fopen($this->debug, 'w'));
        
        if ( $this->setting['proxy'] != '')
			curl_setopt($ch, CURLOPT_PROXY,  $this->setting['proxy']); 
        
        curl_setopt($ch, CURLOPT_USERAGENT, $this->setting['useragent']);
        curl_setopt($ch, CURLOPT_URL, $this->setting['url'].$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);      
        
        $this->response = curl_exec($ch);
        $this->curlInfo = curl_getinfo($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        $this->showError();
    }


    // функция отправляет Get запрос на сервер
    function sendGet($url, $options = null)
    {
        $ch = curl_init($this->setting['url'].$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, fopen($this->debug, 'w'));
       
        if ( $this->setting['proxy'] != '')
			curl_setopt($ch, CURLOPT_PROXY,  $this->setting['proxy']); 
        
        curl_setopt($ch, CURLOPT_USERAGENT, $this->setting['useragent']);
        
        if (isset($options))
			curl_setopt_array($ch, $options);
        
        $this->response = curl_exec($ch);
        $this->curlInfo = curl_getinfo($ch);
        $this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        $this->showError();
    }
    
    
    // функция выводит код ошибки
    function showError()
    {
		if (!$this->isSuccess())
			echo __CLASS__.' error code - '.$this->httpCode.chr(10);
    }
    
}

?>
