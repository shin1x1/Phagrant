<?php
require_once 'Server.php';
require_once 'Provision.php';

use \Phagrant\Server;

$values = include('Phagrantfile.php');

$command = null;
if (!empty($argv[1])) {
  $command = $argv[1];
}

// destroy
if ($command == 'destroy') {
  $server = Server::makeServerFromUuid();
  $server->vboxmanage('controlvm', ['poweroff']);
  $server->vboxmanage('unregistervm', ['--delete']);
  system('rm -rf .phagrant');
  printf("%s unregistered.\n", $server->getUuid());
  exit;
}

// ssh
if ($command == 'ssh') {
  $server = Server::makeServerFromUuid();
  $sshPort = $server->getForwardSshPort();
  passthru('ssh vagrant@localhost -p '. $sshPort);
  exit;
}

// up
foreach ($values as $v) {
  $server = Server::makeServer($v['provider'])->modifyvms($v['provider']);
  $server->up();

  $sshPort = $server->getForwardSshPort();

  $provision = new \Phagrant\Provision();
  $provision->connect('localhost', $sshPort, 'vagrant', 'vagrant');

  if (!empty($v['provision']['package'])) {
    foreach ($v['provision']['package'] as $name) {
      $provision->package($name);
    }
  }

  if (!empty($v['provision']['service'])) {
    foreach ($v['provision']['service'] as $name => $actions) {
      $provision->service($name, $actions);
    }
  }

  $publicKey = file_get_contents(getenv('HOME').'/.ssh/id_rsa.pub');
  $command = sprintf('echo "%s" >> /home/vagrant/.ssh/authorized_keys', $publicKey);
  $provision->command($command);
}
