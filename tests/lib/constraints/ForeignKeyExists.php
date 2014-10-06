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

namespace yentu\tests\constraints;

class ForeignKeyExists extends \yentu\tests\YentuConstraint
{   
    public function matches($table)
    {
        if(!isset($table['schema']))
        {
            $table['schema'] = $GLOBALS['DEFAULT_SCHEMA'];
        }
        
        $response = $this->pdo->query(
            sprintf(
                "SELECT * FROM information_schema.table_constraints WHERE table_name = '%s' and table_schema = '%s' and constraint_name = '%s' and constraint_type='FOREIGN KEY'",
                $table['table'], 
                $table['schema'],
                $table['name']
            )
        );
        return $this->processResult($response->rowCount() === 1);
    }
    
    public function toString()
    {
        return 'is an existing database table';
    }
}
