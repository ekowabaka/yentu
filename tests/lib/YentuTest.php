<?php

/* 
 * The MIT License
 *
 * Copyright 2014 Ekow Abaka Ainoson
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

namespace yentu\tests;

use org\bovigo\vfs\vfsStream;
use yentu\Command;

class YentuTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \PDO
     */
    protected $pdo;
    
    public function assertTableExists($table, $message = '')
    {
        $constraint = new constraints\TableExists();
        $constraint->setPDO($this->pdo);
        $this->assertThat($table, $constraint, $message);
    }
    
    public function assertColumnExists($column, $table, $message = '')
    {
        $constraint = new constraints\ColumnExists();
        $constraint->setPDO($this->pdo); 
        $constraint->setTable($table);
        $this->assertThat($column, $constraint, $message);
    }
    
    protected function initialize()
    {
        $this->pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWORD']);     
        $this->pdo->query("DROP TABLE IF EXISTS yentu_history CASCADE"); 
        $init = new \yentu\commands\Init();
        vfsStream::setup('home');
        Command::setDefaultHome(vfsStream::url('home/yentu'));
        $init->run(
            array(
                'driver' => 'postgresql',
                'host' => $GLOBALS['DB_HOST'],
                'dbname' => $GLOBALS['DB_NAME'],
                'user' => $GLOBALS['DB_USER'],
                'password' => $GLOBALS['DB_PASSWORD']
            )
        );            
    }
    
    protected function deinitialize()
    {
        
    }
}