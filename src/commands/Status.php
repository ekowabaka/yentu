<?php
namespace yentu\commands;

use yentu\Command;
use yentu\Yentu;
use clearice\ClearIce;

/**
 * 
 */
class Status implements Command
{
    public function run($options)
    {
        $driver = \yentu\DatabaseManipulator::create();
        $version = $driver->getVersion();
        
        if($version == null)
        {
            ClearIce::output("\nYou have not applied any migrations\n");
            return;
        }
        
        $migrationInfo = $this->getMigrationInfo($version);
        
        ClearIce::output("\n" . ($migrationInfo['counter']['previous'] == 0 ? 'No' : $migrationInfo['counter']['previous']) . " migration(s) have been applied so far.\n");
        $this->displayMigrations($migrationInfo['run']['previous']);
        
        ClearIce::output("\nLast migration applied:\n    {$migrationInfo['current']}\n");
        
        if($migrationInfo['counter']['yet'] > 0)
        {
            ClearIce::output("\nThere are {$migrationInfo['counter']['yet']} migration(s) that could be applied.\n");
            $this->displayMigrations($migrationInfo['run']['yet']);
        }
        else
        {
            ClearIce::output("\nThere are no pending migrations.\n");
        }
    }
    
    private function getMigrationInfo($version)
    {
        $runMigrations = Yentu::getRunMirations();
        $migrations = Yentu::getMigrations();
        
        $counter['previous'] = count($runMigrations);
        end($runMigrations);
        $current = "{$runMigrations[key($runMigrations)]['timestamp']} {$runMigrations[key($runMigrations)]['migration']}";
        $run = array(
            'previous' => array(),
            'yet' => array()
        );
        
        foreach($runMigrations as $timestamp => $migration)
        {
            unset($migrations[$timestamp]);
            $run['previous'][] = "{$timestamp} {$migration['migration']}";
        }
        
        foreach ($migrations as $migration)
        {
            $run['yet'][] = "{$timestamp} {$migration['migration']}";
        }
                
        return array(
            'counter' => $counter,
            'current' => $current,
            'run' => $run
        );
    }
    
    private function displayMigrations($descriptions)
    {
        foreach($descriptions as $description)
        {
            if(trim($description) == '') 
            {
                continue;
            }
            ClearIce::output("    $description\n");
        }        
    }
}
