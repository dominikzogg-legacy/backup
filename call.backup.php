#!/usr/bin/env php
<?php

include('backup.php');

$objBackup = new Backup(true);
$objBackup
    ->setFolderPath('/backups/www')
    ->setFoldersToBackup(array('/var/www'))
    ->setBackupCount(7)
    ->setDateFormat('d.m.Y')
    ->setTimeZone('Europe/Berlin')
    ->backup()
;