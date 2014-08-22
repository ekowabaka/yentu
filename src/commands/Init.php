<?php
namespace yentu\commands;

/**
 * 
 */
class Init implements \yentu\Command
{
    private $home = './yentu';
    
    private function getParams($options)
    {
        if($options['interractive']) 
        {
            $cli = new \ClearIce();
            
            $params['driver'] = $cli->getResponse('Database type', array(
                    'required' => true,
                    'answers' => array(
                        'postgresql'
                    )
                )
            );
            
            $params['host'] = $cli->getResponse('Database host', array(
                    'required' => true,
                    'default' => 'localhost'
                )
            );
            
            $params['port'] = $cli->getResponse('Database port');
            
            $params['dbname'] = $cli->getResponse('Database name', array(
                    'required' => true
                )
            );
            
            $params['user'] = $cli->getResponse('Database user name', array(
                    'required' => true
                )
            );            
            
            $params['password'] = $cli->getResponse('Database password', array(
                    'required' => FALSE
                )
            );            
        }
        else
        {
            $params = $options;
        }
        return $params;
    }
    
    public function setDefaultHome($home)
    {
        $this->home = $home;
    }
    
    public function createConfigFile($params)
    {
        mkdir($this->home);
        mkdir("{$this->home}/config");
        mkdir("{$this->home}/migrations");
        
        $configFile = new \yentu\CodeWriter();
        $configFile->add('$config = array(');
        $configFile->addIndent();
        $configFile->add("'driver' => '{$params['driver']}',");
        $configFile->add("'host' => '{$params['host']}',");
        $configFile->add("'port' => '{$params['port']}',");
        $configFile->add("'dbname' => '{$params['dbname']}',");
        $configFile->add("'user' => '{$params['user']}',");
        $configFile->add("'password' => '{$params['password']}',");
        $configFile->decreaseIndent();
        $configFile->add(');');
        
        file_put_contents("{$this->home}/config/default.php", $configFile);        
    }
    
    public function run($options)
    {
        if(file_exists($this->home))
        {
            throw new CommandError("Could not initialize yentu. Your project has already been initialized with yentu.");
        }
        else if(!is_writable(dirname($this->home)))
        {
            throw new CommandError("Your current directory is not writable.");
        }
        
        $params = $this->getParams($options);
        
        if(count($params) == 0 && defined('STDOUT'))
        {
            global $argv;
            throw new CommandError(
                "Ooops! You didn't provide any parameters. Please execute "
                . "`{$argv[0]} init -i` to execute command interractively. "
                . "You can also try `{$argv[0]} init --help` for more information.\n"
            );
        }
        
        $db = \yentu\DatabaseDriver::getConnection($params);
        
        if($db->doesTableExist('yentu_history'))
        {
            throw new CommandError("Could not initialize yentu. Your database has already been initialized with yentu.");
        }
        
        $db->createHistory();
        $this->createConfigFile($params);
                
        echo "Yentu successfully initialized.\n";
    }
}

