<?php
namespace Phagrant;

class Server {
  /**
   * @var string
   */
  protected $uuid = null;
  /**
   * @var string
   */
  protected $name = null;
  /**
   * @var box
   */
  protected $box = null;
  /**
   * @var array
   */
  protected $params = [];
  /**
   * @var integer
   */
  protected $sshFowardPort = null;

  /**
   * constructor
   *
   * @param string $uuid
   * @param array $params
   */
  public function __construct($uuid, array $params = []) {
    $this->uuid = $uuid;
    $this->params = $params;
  }

  /**
   * makeServer
   *
   * @param array $params
   * @return \Phagrant\Server
   */
  public static function makeServer(array $params = []) {
    $self = new static(null, $params);
    return $self->import($params['box']);
  }

  /**
   * makeServerFromUuid
   *
   * @return \Phagrant\Server
   */
  public static function makeServerFromUuid() {
    $uuid = @file_get_contents('.phagrant/uuid');
    if (empty($uuid)) {
      throw new \Exception('server isn\'t exists.');
    }

    return new Server($uuid);
  }

  /**
   * import
   *
   * @param string $box
   * @return \Phagrant\Server
   */
  public function import($box) {
    $path = sprintf('%s/.vagrant.d/boxes/%s/virtualbox/box.ovf', getenv('HOME'), $box);
    if (!file_exists($path)) {
      throw new \Exception($path);
    }

    $this->vboxmanage('import', [$path]);
    $ret = $this->vboxmanage('list', ['vms']);
    if (empty($ret)) {
      throw new \Exception($ret);
    }

    $list = explode("\n", $ret);
    array_pop($list);
    $line = array_pop($list);
    if (preg_match('/{([^}]+)}/', $line, $m)) {
      $this->uuid = $m[1];
    }

    if (empty($this->uuid)) {
      throw new \Exception();
    }

    $dir = '.phagrant';
    system('mkdir -p '.$dir);
    file_put_contents($dir.'/uuid', $this->uuid);

    return $this;
  }

  /**
   * up
   *
   * @return \Phagrant\Server
   */
  public function up() {
    $this->vboxmanage('startvm', ['--type' => 'gui']);
    return $this;
  }

  /**
   * modifyvms
   *
   * @param array $params
   * @return \Phagrant\Server
   */
  public function modifyvms(array $params) {
    if (empty($params['modifyvm'])) {
      return $this;
    }

    foreach ($params['modifyvm'] as $v) {
      $this->modifyvm($v);
    }

    return $this;
  }

  /**
   * modifyvm
   *
   * @param array $params
   * @return \Phagrant\Server
   */
  public function modifyvm(array $params) {
    $this->vboxmanage('modifyvm', $params);
    return $this;
  }

  /**
   * vboxmanage
   *
   * @param string $command
   * @param array $params
   */
  public function vboxmanage($command, array $params = []) {
    $parameter = '';
    foreach ($params as $k => $v) {
      if (preg_match('/^[0-9]+/', $k)) {
        $parameter .= sprintf('%s ', $v);
      } else {
        $parameter .= sprintf('%s %s ', $k, $v);
      }
    }
    $command = sprintf('VBoxManage %s %s %s', $command, $this->uuid, $parameter);
    //var_dump($command);

    ob_start();
    $ret = system($command);
    if ($ret === false) {
      throw new \Exception();
    }
    $output = ob_get_clean();

    return $output;
  }

  /**
   * getForwardSshPort
   *
   * @return array($forwardPort, $port)
   */
  public function getForwardSshPort() {
    $ret = $this->vboxmanage('showvminfo', ['--machinereadable']);
    return $this->parseForwardSshPortFromVmInfo($ret)[0];
  }

  /**
   * parseForwardSshPortFromVmInfo
   *
   * @params string $vminfo
   * @return array($forwardPort, $port)
   */
  public function parseForwardSshPortFromVmInfo($vminfo) {
    $tmp = explode("\n", $vminfo);
    foreach ($tmp as $line) {
      if (preg_match('/ssh,tcp,,([0-9]+),,([0-9]+)/', $line, $m)) {
        return [$m[1], $m[2]];
      }
    }

    throw new \Exception('ssh forward port not found.');
  }

  /**
   * @return string
   */
  public function getUuid() {
    return $this->uuid;
  }
}
