<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/* Alex 
require_once __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/ClassLoader/MapClassLoader.php';
use Symfony\Component\ClassLoader\MapClassLoader;
$mapping = array(
		'tcpdf' => __DIR__.'/../vendor/tcpdf/tcpdf.php',
);
$loader = new MapClassLoader($mapping);
$loader->register(true);

require_once __DIR__.'/../vendor/symfony/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->register();
 Alex Fi */

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

// Al final requerit a Classes/TcpdfBridge.php
//require_once __DIR__.'/../vendor/tcpdf/tcpdf.php';

return $loader;
