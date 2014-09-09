<?php

namespace yentu\database;

abstract class DatabaseItem 
{
    private $encapsulated;
    private static $encapsulatedStack = array();
    
    /**
     *
     * @var \yentu\DatabaseDriver
     */
    private static $driver;
    protected $new = false;
    private $changes = array();


    private static $itemTypes = array(
        'table' => 'Table',
        'schema' => 'Schema',
        'column' => 'Column',
        'foreign_key' => 'ForeignKey',
        'primary_key' => 'PrimaryKey',
        'unique_key' => 'UniqueKey',
        'index' => 'Index',
        'view' => 'View'
    );
    
    protected function addChange($attribute, $value)
    {
        $currentDescription = $this->buildDescription();
        $newDescription = $currentDescription;
        $newDescription[$attribute] = $value;
        $class = new \ReflectionClass($this);
        $name = $class->getShortName();
                
        $this->changes[] = array(
            'method' => "change{$name}". str_replace('_', '', $attribute), 
            'args' => array(
                'from' => $currentDescription,
                'to' => $newDescription
            )
        );        
    }
    
    public function isNew()
    {
        return $this->new;
    }
    
    /**
     * 
     * @return \yentu\DatabaseDriver
     */
    protected function getDriver()
    {
        return self::$driver;
    }
    
    public static function setDriver($driver)
    {
        self::$driver = $driver;
    }
    
    public static function push($encapsulated)
    {
        self::$encapsulatedStack[] = $encapsulated;
    }
    
    public function __call($method, $arguments)
    {   
        if(!is_object($this->encapsulated))
        {
            throw new \Exception("Failed to call method {$method}. Could not find an encapsulated object.");
        }
        else if (method_exists($this->encapsulated, $method))
        {
            $method = new \ReflectionMethod($this->encapsulated, $method);
            $this->commit();
            array_pop(self::$encapsulatedStack);
            
            return $method->invokeArgs($this->encapsulated, $arguments);
        }
        else
        {
            $this->commit();
            array_pop(self::$encapsulatedStack);
            
            return $this->encapsulated->__call($method, $arguments);
        }
    }
    
    public function setEncapsulated($item)
    {
        $this->encapsulated = $item;
    }
    
    public static function purge()
    {
        for($i = 0; $i < count(self::$encapsulatedStack); $i++)
        {
            $item = array_pop(self::$encapsulatedStack);
            $item->commit();
        }
    }
    
    public function create()
    {
        $args = func_get_args();
        $type = array_shift($args);
        $class = new \ReflectionClass("\\yentu\\database\\" . self::$itemTypes[$type]);
        $item = $class->newInstanceArgs($args);
        $item->setEncapsulated($this);
        self::push($item);
        return $item;
    }
    
    public function commit()
    {
        if($this->isNew())
        {
            $this->commitNew();
        }

        foreach($this->changes as $change)
        {
            self::$driver->$change['method']($change['args']);
        }

        return $this;
    }
    
    public function rename($newName)
    {
        $this->addChange('name', $newName);
    }    
    
    abstract public function commitNew();
    abstract protected function buildDescription();
}

