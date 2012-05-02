<?php


/**
 * Sends data to trailer data store
 * REQUIRES: PHP 5.2.0 or greater and cURL
 */
class Trailer
{

    public $apiKey;

    /**
     * Get config if exists
     */
    public static function __construct($apiKey='')
    {

        if(empty($apiKey)){
            $iniFile = rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR . 'trailerboard.ini';
            if(file_exists($iniFile)){
                //set api key
                $config = parse_ini_file($iniFile);
                if(!empty($config['API_KEY'])){
                    $apiKey = $config['API_KEY'];
                }

            } 
        }

        if(empty($apiKey)){
            //no api key set
            trigger_error('Invalid TrailerBoard API Key', E_USER_ERROR);
            return false;
        }
    }

    /**
     * Start one or more timers
     */
    public static function timerStart($tag,$id)
    {
        //suggestion for id is php session id
        try {
            file_put_contents(rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR . 'timers' . DIRECTORY_SEPARATOR . md5($tag.$id)),microtime(true));
        }
        catch (Exception $e) {
            trigger_error('Unable to write to the TrailerBoard data directory: '.$e->getMessage(), E_USER_ERROR);
            return false;
        }

        return true;
    }
  
    /**
     * End a timer and log
     */
    public static function timerEnd($tag,$id)
    {
        $timerFile = rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR . 'timers' . DIRECTORY_SEPARATOR . md5($tag.$id));
        if(file_exists($timerFile)){
            self::send($tag, microtime(true)-file_get_contents($timerFile), 1);
        }
    
    }
    /**
     * Log a timer
     */
    public static function timer($tag,$value)
    {
        self::send($tag, $value, 1);
        
        return true;
    }

    /**
     * Increments counter.
     **/
    public static function inc($tag)
    {
    self::send($tag, 1);
      
    return true;
    }

    /**
     * Decrements counter.
     **/
    public static function dec($tag)
    {
    self::send($tag, -1);
      
    return true;
    }
  
    /**
     * Change by arbitrary amount.
     **/
    public static function delta($tag, $delta)
    {

    self::send($tag, $delta);
      
    return true;
    }

    /**
     * Log average number.
     */
    public static function number($tag, $value)
    {
        self::send($tag, $value, 1);
        
        return true;
    }

    /**
     * Override counter with arbitrary number.
     */
    public static function value($tag, $value)
    {
        self::send($tag, $value, 3);
        
        return true;
    }

    /**
     * Log text event.
     */
    public static function text($tag, $value)
    {
        self::send($tag, $value, 2);
        
        return true;
    }

    /*
     * Send values over UDP to server
     */
    public static function send($tag, $value, $type=0)  //type 0 is aggregate, 1 is average aggregate, 2 is event, 3 is an override of the value for aggregate
    {


         switch ($type) {
             case 1:
                 $data = array('type'=>'number','tag'=>$tag,'value'=>$value);
                 break;
             case 2:
                 $data = array('type'=>'text','tag'=>$tag,'value'=>$value);
                 break;
             case 3:
                 $data = array('type'=>'value','tag'=>$tag,'value'=>$value);
                 break;
             default:
                 $data = array('type'=>'counter','tag'=>$tag,'value'=>$value);
                 break;
         }

        // POST THE DATA USING cURL
        $c = curl_init();
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($c, CURLOPT_CAINFO, rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR  ."cacert.cert");
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_POST, true);
        curl_setopt($c, CURLOPT_URL, 'https://trailerboard.com/api/');
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($data));
        $output = curl_exec($ch);

    }
}
?>