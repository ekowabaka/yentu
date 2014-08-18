<?php
namespace yentu;

class ChangeReverser
{
    private static $driver;

    public static function setDriver($driver)
    {
        self::$driver = $driver;
    }


    public static function call($method, $arguments) 
    {
        $reflection = new \ReflectionMethod(self::$driver, self::reverseMethod($method));
        return $reflection->invokeArgs(self::$driver, self::reverseArguments($arguments));        
    }    
    
    private static function reverseMethod($method)
    {
        return preg_replace_callback(
            "/^(?<action>add|drop)/", 
            function($matches){
                switch($matches['action'])
                {
                    case 'add': return 'drop';
                    case 'drop': return 'add';
                }
            }, $method
        );
    }
    
    private static function reverseArguments($arguments)
    {
        if(isset($arguments['from']) && isset($arguments['to']))
        {
            return array(
                'to' => $arguments['from'],
                'from' => $arguments['to']
            );
        }
        else
        {
            return $arguments;
        }
    }
}