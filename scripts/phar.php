<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');

$bucketInfo = json_decode(file_get_contents('../bucket.json'));

if (Phar::canWrite()) {
    $p = new Phar('mpf-core.phar');
    $p = $p->convertToExecutable(Phar::TAR, Phar::GZ, $bucketInfo->version.'.phar.tgz');
    $p->startBuffering();

    $p->buildFromDirectory(realpath('../'), '/^(.(?!.git))*$/i');
    $p->stopBuffering();

} else {
    echo 'cant write...'."\n";
}
