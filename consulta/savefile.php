<?php
  $folder = 'csv';
  if (!file_exists ($folder))
    mkdir ($folder);

  $file = $_POST['filename'];
  //$file = 'taf.csv';
  $filename = $folder.DIRECTORY_SEPARATOR.$file;
  $texto = $_POST['texto'];
  file_put_contents($filename,$texto);
  echo '{ "filename": '.'"'. $filename .'"}';//   $filename, 'textogravado': $texto, 'php': sim }';
