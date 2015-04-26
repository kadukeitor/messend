<?php

    // framework core
    $f3 = require( 'lib/base.php' );

    // routes
    $f3->config('app/routes.ini');

    // config
    $f3->config('app/config.ini');

    // let's rock !!!
    $f3->run();