<?php
  $folder = 'csv';
  $file = $_GET['filename'];
  //$file = 'TAF_04-2023.csv';
  $filename = $folder.DIRECTORY_SEPARATOR.$file;
  
  echo file_get_contents($filename);
