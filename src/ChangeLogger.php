<?php
namespace yentu;

use clearice\ClearIce;

class ChangeLogger
{
    private $driver;
    private static $version;
    private static $migration;
    private static $session;
    private $changes;
    private $expectedOperations = 1;
    private $operations;
    private static $defaultSchema = '';
    private $skippedItemTypes = array();
    private $allowedItemTypes = array();
    private $dumpQueriesOnly;
    private $dryRun;
    
    public function skip($itemType)
    {
        $this->skippedItemTypes[] = $itemType;
    }
    
    public function setDumpQueriesOnly($dumpQueriesOnly)
    {
        if($dumpQueriesOnly === true)
        {
            ClearIce::setOutputLevel(ClearIce::OUTPUT_LEVEL_0);
        }
        $this->dumpQueriesOnly = $dumpQueriesOnly;
    }
    
    public function setDryRun($dryRun)
    {
        $this->dryRun = $dryRun;
    }
    
    public function allowOnly($itemType)
    {
        $this->allowedItemTypes[] = $itemType;
    }    

    private function __construct(DatabaseManipulator $driver) 
    {
        $this->driver = $driver;
        $this->driver->createHistory();
    }
    
    public static function wrap($item)
    {
        self::$session = sha1(rand() . time());
        return new ChangeLogger($item);
    }
    
    public static function setVersion($version)
    {
        self::$version = $version;
    }
    
    public static function setMigration($migration)
    {
        self::$migration = $migration;
    }
    
    public function __call($method, $arguments) 
    {
        if(preg_match("/^(?<command>add|drop|change|execute|reverse)(?<item_type>[a-zA-Z]+)/", $method, $matches))
        {
            $this->driver->setDumpQuery($this->dumpQueriesOnly);
            $this->driver->setDisableQuery($this->dryRun);
            
            if(
                array_search($matches['item_type'], $this->skippedItemTypes) !== false || 
                (array_search($matches['item_type'], $this->allowedItemTypes) === false && count($this->allowedItemTypes) > 0)
            )
            {
                ClearIce::output("S");
                ClearIce::output("kipping " . preg_replace("/([a-z])([A-Z])/", "$1 $2", $matches['item_type']) . " '" . $arguments[0]['name'] . "'\n", ClearIce::OUTPUT_LEVEL_2);
            }
            else
            {        
                Yentu::announce($matches['command'], $matches['item_type'], $arguments[0]);  
                $return = $this->driver->$method($arguments[0]);
                
                $this->driver->setDumpQuery(false);
                             
                ClearIce::pushOutputLevel(ClearIce::OUTPUT_LEVEL_0);
                $this->driver->query(
                    'INSERT INTO yentu_history(session, version, method, arguments, migration, default_schema) VALUES (?,?,?,?,?,?)',
                    array(
                        self::$session,
                        self::$version,
                        $method,
                        json_encode($arguments),
                        self::$migration,
                        self::$defaultSchema
                    )
                );
                ClearIce::popOutputLevel();
                $this->changes++;
                $this->driver->setDisableQuery(false);
            }
            
            $this->operations++;
            $this->outputProgress();
        }
        else if(preg_match("/^does([A-Za-z]+)/", $method))
        {
            $invokable = new \ReflectionMethod($this->driver->getAssertor(), $method);
            return $invokable->invokeArgs($this->driver->getAssertor(), $arguments);
        }
        else
        {
            $invokable = new \ReflectionMethod($this->driver, $method);
            return $invokable->invokeArgs($this->driver, $arguments);
        }
        
        return $return;
    }
    
    public function outputProgress()
    {
        if($this->expectedOperations > 0)
        {
            if($this->operations % 70 === 0)
            {
                ClearIce::output(sprintf(" %4d%%\n", $this->operations / $this->expectedOperations * 100));
            }
            else
            {
                ClearIce::output(sprintf(" %4d%%\n", $this->operations / $this->expectedOperations * 100), ClearIce::OUTPUT_LEVEL_2);
            }
        }
    }
    
    public function setDefaultSchema($defaultSchema)
    {
        self::$defaultSchema = $defaultSchema;
    }
    
    public function getChanges()
    {
        return $this->changes;
    }
    
    public function getOperations()
    {
        return $this->operations;
    }
    
    public function __clone()
    {
        $this->driver = clone $this->driver;
    }
    
    public function setExpectedOperations($expectedOperations)
    {
        $this->expectedOperations = $expectedOperations;
    }
}

