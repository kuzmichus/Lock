<?php

require __DIR__ . '/../vendor/autoload.php';

$lock = new \Lock\Lock(new \Cache\FilesystemCache(__DIR__ . '/cache/'));

echo 'Запускаем программу', PHP_EOL;
if ($lock->isLocked()) {
    echo 'Программа уже выполняется.', PHP_EOL;
    exit(0);
}
$lock->lock();
echo 'Выполняем программу', PHP_EOL;
sleep(20);
$lock->unlock();
echo 'Завершили выполнениее программу', PHP_EOL;
