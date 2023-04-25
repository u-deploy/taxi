<?php return array(
    'app'      =>
         array(
            'name' => 'Cached Name',
         ),
    'database' =>
         array(
            'default'     => 'mysql',
            'connections' =>  array(
                'sqlite' =>  array(
                    'driver'                  => 'sqlite',
                    'url'                     => '',
                    'database'                => '/home/user/sites/database.sqlite',
                    'prefix'                  => '',
                    'foreign_key_constraints' => true,
                ),

                'mysql' =>  array(
                    'driver'         => 'mysql',
                    'url'            => '',
                    'host'           => '127.0.0.1',
                    'port'           => '3306',
                    'database'       => 'forge',
                    'username'       => 'forge',
                    'password'       => 'secret',
                    'unix_socket'    => '',
                    'charset'        => 'utf8mb4',
                    'collation'      => 'utf8mb4_unicode_ci',
                    'prefix'         => '',
                    'prefix_indexes' => true,
                    'strict'         => false,
                    'engine'         => null,
                    'options'        =>  array(),
                ),

                'pgsql' =>  array(
                    'driver'         => 'pgsql',
                    'url'            => '',
                    'host'           => '127.0.0.1',
                    'port'           => '5432',
                    'database'       => 'forge',
                    'username'       => 'forge',
                    'password'       => '',
                    'charset'        => 'utf8',
                    'prefix'         => '',
                    'prefix_indexes' => true,
                    'search_path'    => 'public',
                    'sslmode'        => 'prefer',
                ),
            ),
         )
);
