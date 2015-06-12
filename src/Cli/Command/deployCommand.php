<?php
// src/Cli/Command/BasicCommand.php

namespace Cli\Command;


use Ssh\Authentication\PublicKeyFile;
use Ssh\Session;
use Ssh\SshConfigFileConfiguration;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;


class deployCommand extends Command
{

  protected $_serverConfig = [];
  protected $_builtin = [];
  protected $_out = [];
  protected $_dry = false;


  protected function configure()
  {
    $this
    ->setName('deploy')
    ->setDescription('A Simple Deploy Tool')
    ->addArgument(
      'name',
      InputArgument::REQUIRED,
      'Name of the configuration file? e.g. book '
    )
    ->addArgument(
      'cmd',
      InputArgument::OPTIONAL,
      'run the command ? e.g. gs ',
      'default'
    )
    ->addOption(
      'pre-cmd',
      'p',
      InputArgument::OPTIONAL,
      'run the command as pre-requisite? e.g. gs '
      )
    ->addOption(
      'dry-run',
      'd',
      InputArgument::OPTIONAL,
      'Print Only Script',
      false
    )
    ;
  }

  /**
   * Command Execution
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return boolean
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $name = $input->getArgument('name');
    $cmd  = $input->getArgument('cmd');
    $pre  = $input->getOption('pre-cmd');

    $this->_serverConfig = $this->loadConfiguration( "$name.yml");
    $this->_builtin      = $this->loadConfiguration( "built-in.yml");
    $this->_out = $output;
    $this->_dry = $input->getOption('dry-run');

    $output->writeln( 'Executing in : ' . $this->_serverConfig['server']['name']);

    // Make Connection
    $exec = $this->makeConnection();

    // GET Command Scripts
    $script = $this->getCommandScript( $cmd );
    if( $pre != null )
      $script = $this->getCommandScript( $pre ) . "\n" . $script;

    // Run Command
    $this->runCommand( $exec, $script );


    return true;

  }

  protected function runCommand( $exec, $script ){

    if( $this->_dry == true ){
      $this->_out->writeln('Here is the command :');
      $this->_out->writeln( $script );
      exit;
    }

    if( empty( $script ) ) return;

    try {
      $out = $exec->run($script);
      $this->_out->writeln( $out );
    } catch (\Exception $e) {
      $this->_out->writeln($e->getMessage());
    }
  }

  protected function getCommandScript( $cmd ){

    if( isset( $this->_serverConfig['server']['commands'][ $cmd ] ) )
      $command = $this->_serverConfig['server']['commands'][ $cmd ];
    else if( isset($this->_builtin['commands'][$cmd]))
      $command = $this->_builtin['commands'][ $cmd ];
    else
      return '';

    $scripts = "";
    if( isset( $command['pre']) ) {

      foreach( $command['pre'] as $p_cmd )
        $scripts .=  $this->getCommandScript( $p_cmd ) . "\n";
    }

    $scripts .= implode( "\n", $command['script'] );
    return $scripts;




  }

  protected function makeConnection(){
    $connection = $this->_serverConfig['server']['credential'];
    extract( $connection );

    try{

      switch( $type ){

        case 'user' :
          $configuration = new \Ssh\Configuration( $hostname );
          $authentication = new \Ssh\Authentication\Password( $username, $password);
          $session = new Session($configuration, $authentication);
          break;

        case 'host' :
          $configuration = new SshConfigFileConfiguration('~/.ssh/config', $hostname);
          $session = new Session($configuration, $configuration->getAuthentication() );
          break;

        default:
          break;
      }
    }
    catch(Exception $ex){
      exit;
    }


    $exec = $session->getExec();
    return $exec;

  }


  /**
   * Loading Congiuration file based on filename
   * @TODO a better error handling
   *
   * @param $filename
   * @return array
   */
  protected function loadConfiguration( $filename ){
    try{
      $configDirectories = array( CONFIG_PATH);

      $locator = new FileLocator($configDirectories);
      $yamlUserFiles = $locator->locate( $filename, null, false);

      $config = Yaml::parse(file_get_contents( $yamlUserFiles[0]));

      return $config;

    }catch(Exception $ex ){

      exit;
    }
  }

}
