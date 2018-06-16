<?php

/*
 * The MIT License
 *
 * Copyright 2017 ekow.
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

namespace yentu;

use clearice\io\Io;
use yentu\manipulators\AbstractDatabaseManipulator;
use ntentan\atiaa\DriverFactory;
use ntentan\config\Config;

/**
 * Description of DatabaseManipulatorFactory
 *
 * @author ekow
 */
class DatabaseManipulatorFactory
{
    private $yentu;
    private $driverFactory;
    private $io;
    private $config;
    
    public function __construct(Yentu $yentu, Config $config, DriverFactory $driverFactory, Io $io)
    {
        $this->yentu = $yentu;
        $this->driverFactory = $driverFactory;
        $this->io = $io;
        $this->config = $config;
    }
    
    public function createManipulator() : AbstractDatabaseManipulator
    {
        $config = $this->config->get('db');
        $this->driverFactory->setConfig($config);
        $class = "\\yentu\\manipulators\\" . ucfirst($config['driver']);
        return new $class($this->yentu, $this->driverFactory, $this->io);
    }
}
