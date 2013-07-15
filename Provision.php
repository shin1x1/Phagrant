<?php
namespace Phagrant;

class Provision {
  /**
   * @var resource
   */
  protected $connect = null;

  /**
   * constructor
   */
  public function __construct() {
  }

  /**
   * connect
   *
   * @param string $host
   * @param integer $port
   * @param string $username
   * @param string $password
   */
  public function connect($host, $port, $username, $password) {
    $this->connect = ssh2_connect($host, $port);
    ssh2_auth_password($this->connect, $username, $password);
  }

  /**
   * package
   *
   * @param string $name
   */
  public function package($name) {
    $stream = ssh2_exec($this->connect, 'rpm -qi '.$name);
    stream_set_blocking($stream, 1);
    $ret = fread($stream, 4096);

    $stream = ssh2_exec($this->connect, 'echo $?');
    stream_set_blocking($stream, 1);
    $ret = fread($stream, 4096);
    if ($ret === '0') {
      return;
    }

    $command = sprintf('yum -y install %s', $name);
    $this->command($command);
  }

  /**
   * service
   *
   * @param string $name
   * @param array $actions
   */
  public function service($name, $actions) {
    if (!empty($actions['enable'])) {
      $command = sprintf('/sbin/chkconfig %s on', $name);
      $this->command($command);
    }
    if (!empty($actions['action'])) {
      $command = sprintf('/sbin/service %s %s', $name, $actions['action']);
      $this->command($command);
    }
  }

  /**
   * command
   *
   * @param string $command
   */
  public function command($command) {
    $command = 'sudo '.$command;
    $stream = ssh2_exec($this->connect, $command);

    stream_set_blocking($stream, 1);

    while ($ret = fread($stream, 4096)) {
      echo $ret;
    }
  }
}
