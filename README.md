# Phagrant

## Usage

### configuration

    $ vim Phagrant.php
    <?php
    return [
      'provider' => [
        'box' => 'centos64_ja', // <--- vagrant add した box ファイル
        'modifyvm' => [
          ['--name' => 'phpmatsuri'],
          ['--memory' => '1024'],
        ],
      ]
    ];
  
### up
  
    $ php phagrant.php

### ssh

    $ php phagrant.php ssh

### destory

    $ php phagrant.php destroy

  
