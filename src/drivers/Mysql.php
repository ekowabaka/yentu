<?php

/* 
 * The MIT License
 *
 * Copyright 2014 ekow.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace yentu\drivers;

class Mysql extends Pdo
{
    private $autoIncrementPending;
    private $placeholders = array();
    
    private function buildTableName($name, $schema)
    {
        return ($schema === false || $schema == '' ? '' : "`{$schema}`.") . "`$name`";
    }
    
    public function connect($params)
    {
        if($params['dbname'] != '')
        {
            $this->defaultSchema = $params['dbname'];
        }
        parent::connect($params);
    }
    
    protected function _addAutoPrimaryKey($details)
    {
        $description = $this->getDescription();
        $table = $description['tables'][$details['table']];
        $column = ($description['tables'][$details['table']]['columns'][$details['column']]);
        
        if(count($table['primary_key']) > 0)
        {
            $this->query(
                sprintf('ALTER TABLE %s MODIFY `%s` %s %s AUTO_INCREMENT',
                   $this->buildTableName($details['table'], $details['schema']),
                    $details['column'], 
                    \yentu\descriptors\Mysql::convertTypes(
                        $column['type'], 
                        \yentu\descriptors\Mysql::TO_MYSQL,
                        $column['length'] == '' ? 255 : $column['length']
                    ),
                    $column['nulls'] === false ? 'NOT NULL' : ''
                )
            );       
        }
        else
        {
            $this->autoIncrementPending = $details;
        }
    }

    protected function _addColumn($details)
    {
        $tableName = $this->buildTableName($details['table'], $details['schema']);
        
        $this->query(
            sprintf('ALTER TABLE %s ADD COLUMN `%s` %s %s', 
                $tableName,
                $details['name'], 
                \yentu\descriptors\Mysql::convertTypes(
                    $details['type'], 
                    \yentu\descriptors\Mysql::TO_MYSQL,
                    $details['length'] == '' ? 255 : $details['length']
                ),
                $details['nulls'] === false ? 'NOT NULL' : ''
            )
        ); 
        
        if(isset($this->placeholders[$tableName]))
        {
            $this->query(sprintf('ALTER TABLE %s DROP COLUMN `__yentu_placeholder_col`', $tableName));
            unset($this->placeholders[$tableName]);
        }        
    }

    protected function _addForeignKey($details)
    {
        $this->query(
            sprintf(
                'ALTER TABLE %s ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES %s (`%s`) MATCH FULL ON DELETE %s ON UPDATE %s',
                $this->buildTableName($details['table'], $details['schema']),
                $details['name'], 
                implode('`,`', $details['columns']), 
                $this->buildTableName($details['foreign_table'], $details['foreign_schema']),
                implode('","', $details['foreign_columns']),
                $details['on_delete'] == '' ? 'NO ACTION' : $details['on_delete'],
                $details['on_update'] == '' ? 'NO ACTION' : $details['on_update']
            )
        );        
    }

    protected function _addIndex($details)
    {
        $this->query(
            sprintf(
                'CREATE %s INDEX `%s` ON %s (`%s`)',
                $details['unique'] ? 'UNIQUE' : '',
                $details['name'],
                $this->buildTableName($details['table'], $details['schema']),
                implode('", "', $details['columns'])
            )
        );        
    }

    protected function _addPrimaryKey($details)
    {
        $this->query(
            sprintf('ALTER TABLE %s ADD PRIMARY KEY (`%s`)',
                $this->buildTableName($details['table'], $details['schema']),
                implode('`,`', $details['columns'])
            )
        );        
        if(is_array($this->autoIncrementPending))
        {
            $this->_addAutoPrimaryKey($this->autoIncrementPending);
            $this->autoIncrementPending = null;
        }
    }

    protected function _addSchema($name)
    {
        $this->query(sprintf('CREATE SCHEMA `%s`', $name));        
    }

    protected function _addTable($details)
    {
        $this->query(sprintf('CREATE TABLE %s (__yentu_placeholder_col INT)',  $this->buildTableName($details['name'], $details['schema'])));    
        $this->placeholders[$this->buildTableName($details['name'], $details['schema'])] = true;

        if(isset($details['columns']))
        {
            foreach($details['columns'] as $column)
            {
                $column['table'] = $details['name'];
                $column['schema'] = $details['schema'];
                $this->_addColumn($column);
            }
        }        
        
        if(isset($details['primary_key']))
        {
            $primaryKey = array(
                'columns' => reset($details['primary_key']),
                'name' => key($details['primary_key']),
                'table' => $details['name'],
                'schema' => $details['schema']
            );
            $this->_addPrimaryKey($primaryKey);
        }
        
        if(isset($details['unique_keys']))
        {
            foreach($details['unique_keys'] as $name => $columns)
            {
                $uniqueKey = array(
                    'name' => $name,
                    'columns' => $columns,
                    'table' => $details['name'],
                    'schema' => $details['schema']
                );
                $this->_addUniqueKey($uniqueKey);
            }
        }
        
        if(isset($details['foreign_keys']))
        {
            foreach($details['foreign_keys'] as $name => $foreignKey)
            {
                $foreignKey['name'] = $name;
                $foreignKey['table'] = $details['name'];
                $foreignKey['schema'] = $details['schema'];
                $this->_addForeignKey($foreignKey);
            }
        }
        
        if(isset($details['indices']))
        {
            foreach($details['indices'] as $name => $index)
            {
                $index = array(
                    'name' => $name,
                    'columns' => $index,
                    'table' => $details['name'],
                    'schema' => $details['schema']
                );
            }
        }
    }

    protected function _addUniqueKey($details)
    {
        $this->query(
            sprintf(
                'ALTER TABLE %s ADD CONSTRAINT `%s` UNIQUE (`%s`)',
                $this->buildTableName($details['table'], $details['schema']),
                $details['name'],
                implode('`,`', $details['columns'])
            )
        );        
    }

    protected function _addView($details)
    {
        if($details['schema'] != null)
        {
            $this->query(sprintf('USE `%s`', $details['schema']));
        }        
        $this->query(sprintf('CREATE VIEW %s AS %s', $this->buildTableName($details['name'], $details['schema']), $details['definition']));        
        if($details['schema'] != null)
        {
            $this->query(sprintf('USE `%s`', $this->getDefaultSchema()));
        }          
    }

    protected function _changeColumnName($details)
    {
        $this->query(
            sprintf('ALTER TABLE %s CHANGE `%s` `%s` %s %s',
               $this->buildTableName($details['to']['table'], $details['to']['schema']),
                $details['from']['name'],                 
                $details['to']['name'], 
                \yentu\descriptors\Mysql::convertTypes(
                    $details['to']['type'], 
                    \yentu\descriptors\Mysql::TO_MYSQL,
                    $details['to']['length'] == '' ? 255 : $details['to']['length']
                ),
                $details['to']['nulls'] === false ? 'NOT NULL' : ''
            )
        );   
    }

    protected function _changeColumnNulls($details)
    {
        $details = $details['to'];
        $this->query(
            sprintf('ALTER TABLE %s MODIFY `%s` %s %s',
               $this->buildTableName($details['table'], $details['schema']),
                $details['name'], 
                \yentu\descriptors\Mysql::convertTypes(
                    $details['type'], 
                    \yentu\descriptors\Mysql::TO_MYSQL,
                    $details['length'] == '' ? 255 : $details['length']
                ),
                $details['nulls'] === false ? 'NOT NULL' : ''
            )
        );     
    }

    protected function _changeViewDefinition($details)
    {
        $this->_dropView($details['from']);
        $this->_addView($details['to']);        
    }

    protected function _dropAutoPrimaryKey($details)
    {
        $description = $this->getDescription();
        $column = ($description['tables'][$details['table']]['columns'][$details['column']]);
        
        $this->query(
            sprintf('ALTER TABLE %s MODIFY `%s` %s %s',
               $this->buildTableName($details['table'], $details['schema']),
                $details['column'], 
                \yentu\descriptors\Mysql::convertTypes(
                    $column['type'], 
                    \yentu\descriptors\Mysql::TO_MYSQL,
                    $column['length'] == '' ? 255 : $column['length']
                ),
                $column['nulls'] === false ? 'NOT NULL' : ''
            )
        );         
    }

    protected function _dropColumn($details)
    {
        $description = $this->getDescription();
        if(count($description['tables'][$details['table']]['columns']) === 0)
        {
            $this->_addColumn(
                array(
                    'table' => $details['table'],
                    'name' => '__yentu_placeholder_col',
                    'type' => 'integer'
                )
            );
        }
        $this->query(
            sprintf(
                'ALTER TABLE %s DROP COLUMN `%s`', 
                $this->buildTableName($details['table'], $details['schema']),
                $details['name']
            )
        );        
    }

    protected function _dropForeignKey($details)
    {
        $this->query(
            sprintf(
                'ALTER TABLE %s DROP FOREIGN KEY `%s`', 
                $this->buildTableName($details['table'], $details['schema']),
                $details['name']
            )
        );        
    }

    protected function _dropIndex($details)
    {
        $this->query(
            sprintf(
                'ALTER TABLE %s DROP INDEX `%s`', 
                $this->buildTableName($details['table'], $details['schema']),
                $details['name']
            )
        );          
    }

    protected function _dropPrimaryKey($details)
    {
        try{
            $this->query(
                sprintf(
                    'ALTER TABLE %s DROP PRIMARY KEY', 
                    $this->buildTableName($details['table'], $details['schema']),
                    $details['name']
                )
            );  
        }
        catch(\yentu\DatabaseDriverException $e)
        {
            $this->_dropAutoPrimaryKey(array(
                'column' => $details['columns'][0],
                'schema' => $details['schema'],
                'table' => $details['table']
            ));
            $this->_dropPrimaryKey($details);
        }
    }

    protected function _dropSchema($name)
    {
        $this->query(sprintf('DROP SCHEMA `%s`', $name));                
    }

    protected function _dropTable($details)
    {
        $this->query(sprintf('DROP TABLE %s', $this->buildTableName($details['name'], $details['schema'])));                
    }

    protected function _dropUniqueKey($details)
    {
        $this->query(
            sprintf(
                'ALTER TABLE %s DROP KEY `%s`', 
                $this->buildTableName($details['table'], $details['schema']),
                $details['name']
            )
        );           
    }

    protected function _dropView($details)
    {
        $this->query(sprintf('DROP VIEW %s', $this->buildTableName($details['name'], $details['schema'])));        
    }

    protected function describe()
    {
        $descriptor = new \yentu\descriptors\Mysql($this);
        return $descriptor->describe();        
    }

    protected function getDriverName()
    {
        return 'mysql';
    }
}
