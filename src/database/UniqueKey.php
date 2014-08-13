<?php
namespace yentu\database;

class UniqueKey extends DatabaseItem
{
    private $columns;
    private $table;
    private $name;
    
    public function __construct($columns, $table)
    {
        $this->columns = $columns;
        $this->table = $table;
    }
    
    public function commit() 
    {
        $this->getDriver()->addUniqueConstraint(
            array(
                'table' => $this->table->getName(), 
                'schema' => $this->table->getSchema()->getName(), 
                'columns' => $this->columns,
                'name' => $this->name
            )
        );
        return $this;        
    }
    
    public function drop()
    {
        $this->getDriver()->dropUniqueKey(
            array(
                'columns' => $this->columns,
                'table' => $this
            )
        );
        return $this;
    }
    
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }    
}
