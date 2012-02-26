#!/usr/bin/env php
<?php

include('backup.php');

$objBackup = new Backup(true);
$objBackup
    ->setFolderPath('/backups')
    ->setFoldersToBackup(
        array
        (
            '/etc/apache2/',
            '/var/www' => array
            (
                'vhosts/test',
            )
        )
    )
    ->setBackupCount(7)
    ->setDateFormat('d.m.Y')
    ->setTimeZone('Europe/Berlin')
    ->backup()
;