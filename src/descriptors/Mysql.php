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

namespace yentu\descriptors;

class Mysql extends InformationSchema
{
    /*protected function getColumns(&$table)
    {
        return $this->driver->query(
            sprintf(
                "select column_name as name, data_type as type, is_nullable as nulls, column_default as `default`, character_maximum_length as length
                from information_schema.columns
                where table_name = '%s' and table_schema='%s'", 
                $table['name'], $table['schema']
            )
        );
    }*/

    protected function getForeignKeys(&$table)
    {
        return $this->driver->query(
            sprintf("SELECT
                        kcu.constraint_name as name,
                        kcu.table_schema as `schema`,
                        kcu.table_name as `table`, 
                        kcu.column_name as `column`, 
                        kcu.referenced_table_name AS foreign_table,
                        kcu.referenced_table_schema AS foreign_schema,
                        kcu.referenced_column_name AS foreign_column,
                        rc.update_rule as on_update,
                        rc.delete_rule as on_delete
                    FROM 
                        information_schema.table_constraints AS tc 
                        JOIN information_schema.key_column_usage AS kcu
                          ON tc.constraint_name = kcu.constraint_name and tc.table_schema = kcu.table_schema
                        JOIN information_schema.referential_constraints AS rc
                          ON rc.constraint_name = tc.constraint_name and rc.constraint_schema = tc.table_schema
                    WHERE constraint_type = 'FOREIGN KEY' 
                        AND tc.table_name='%s' AND tc.table_schema='%s'",
                $table['name'], $table['schema']
            )
        );  
    }

    protected function getIndices(&$table)
    {
        return $this->driver->query(
            sprintf("SELECT table_name, column_name as `column`,index_name FROM information_schema.STATISTICS 
                WHERE INDEX_NAME not in (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE)
                AND table_name = '%s' and table_schema = '%s'", 
            $table['name'], $table['schema'])
        );
    }

    protected function getSchemata()
    {
        $defaultSchema = $this->driver->getDefaultSchema();
        if($defaultSchema == '')
        {
            $schemata = $this->driver->query(
                "select schema_name as name from information_schema.schemata 
                where schema_name <> 'information_schema'"
            );
        }
        else
        {
            $schemata = array(
                array(
                    'name' => $defaultSchema
                )
            );
        }
        return $schemata;
    }

    protected function getTables($schema)
    {
        return $this->driver->query(
            "select table_schema as `schema`, table_name as `name`
            from information_schema.tables
            where table_schema = '$schema' and table_type = 'BASE TABLE' order by table_name"
        );
    }

    protected function getViews(&$schema)
    {
        return $this->driver->query(
            "select table_schema as `schema`, table_name as name, view_definition as definition
            from information_schema.views
            where table_schema = '$schema' order by table_name"
        );
    }

    protected function hasAutoIncrementingKey(&$table)
    {
        $auto = false;
        $found = $this->driver->query(
            sprintf(
                "select column_name as name
                from information_schema.columns
                where table_name = '%s' and table_schema='%s' and extra = 'auto_increment'", 
                $table['name'], $table['schema']
            )
        );
        
        if(count($found) > 0)
        {
            $auto = true;
        }
        
        return $auto;
    }
    
    protected function getPrimaryKey(&$table)
    {
        return $this->getConstraint($table, 'PRIMARY KEY');
    }
    
    protected function getUniqueKeys(&$table)
    {
        return $this->getConstraint($table, 'UNIQUE');
    }

    private function getConstraint($table, $type)
    {
        return $this->driver->query(
            sprintf("select column_name as `column`, pk.constraint_name as name from 
                information_schema.table_constraints pk 
                join information_schema.key_column_usage c on 
                   c.table_name = pk.table_name and 
                   c.constraint_name = pk.constraint_name and
                   c.constraint_schema = pk.table_schema
                where pk.table_name = '%s' and pk.table_schema='%s'
                and constraint_type = '%s' order by column_name",
                $table['name'], $table['schema'], $type
            )
        );
    }    
    
    public static function convertTypes($type, $direction, $length = null)
    {
        $types = array(
            'integer' => 'integer',
            'int' => 'integer',
            'decimal' => 'integer',
            'bigint' => 'bigint',
            'varchar' => 'string',
            'double' => 'double',
            'timestamp' => 'timestamp',
            'text' => 'text',
            'tinytext' => 'text',
            'boolean' => 'boolean',
            'tinyint' => 'boolean',
            'date' => 'date',
            'blob' => 'blob'
        );
        
        switch($direction)
        {
            case self::CONVERT_TO_YENTU: 
                $destinationType = $types[$type];
                break;
            
            case self::CONVERT_TO_DRIVER: 
                $destinationType = array_search($type, $types);
                
                break;
        }
        
        if($destinationType == '')
        {
            throw new \yentu\DatabaseDriverException("Invalid data type {$type} requested"); 
        }
        else if($destinationType == 'varchar')
        {
            $destinationType .= $length === null ? '' : "($length)";
        }
        return $destinationType;        
    } 
}