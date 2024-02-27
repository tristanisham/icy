<?php
// Check if the Phar extension is loaded and phar.readonly is off
if (!extension_loaded('phar') || ini_get('phar.readonly')) {
    die("Ensure the Phar extension is loaded and phar.readonly is set to 0\n");
}

// Name of the phar file
$pharFile = 'icy.phar';

// Create a phar
$phar = new Phar($pharFile, 0, $pharFile);
$phar->startBuffering();

// Add the source files to the phar
// Adjust the path and the directory structure according to your project
$defaultStub = $phar->createDefaultStub('src/main.php'); // Entry point
$phar->buildFromDirectory(dirname(__FILE__) . '/src', '/\.php$/');

// Set the stub
$phar->setStub($defaultStub);
$phar->stopBuffering();

// Plus - compressing it
$phar->compressFiles(Phar::GZ);

echo "$pharFile successfully created";
