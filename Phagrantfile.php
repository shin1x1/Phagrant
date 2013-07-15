<?php
return [
  [
    'provider' => [
      'box' => 'centos64_ja',
      'modifyvm' => [
        ['--name' => 'phpmatsuri'],
        ['--memory' => '1024'],
      ],
    ],
    'provision' => [
      'package' => [
        'php',
        'mysql-server',
      ],
      'service' => [
        'mysqld' => ['enable' => true, 'action' => 'start'],
      ],
    ],
  ]
];
