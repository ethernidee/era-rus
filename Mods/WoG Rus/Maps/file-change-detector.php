<?php
declare(strict_types=1);
error_reporting(-1);

define('FILE_PATH', __DIR__ . '/test.h3c');

function unpackFile (string $file): string {
  return file_get_contents('compress.zlib://data://text/plain;base64,' . base64_encode($file));
}

function detectChange (string $file, ?string $prevFile) {
  if (!isset($prevFile)) {
    return;
  }

  $isChanged  = false;
  $numChanges = 0;

  for ($i = 0, $k = min(strlen($file), strlen($prevFile)); $i < $k; $i++) {
    if ($file[$i] !== $prevFile[$i]) {
      $isChanged = true;
      $modStart  = $i;

      for (; $i < $k && $file[$i] !== $prevFile[$i]; $i++) { 
        // next
      }

      $modLen  = min(100, $i - $modStart);
      $origStr = bin2hex(substr($prevFile, $modStart, $modLen));
      $modStr  = bin2hex(substr($file, $modStart, $modLen));

      print("Changed $modLen bytes at 0" . dechex($modStart) . "\r\n");
      print("Old bytes: $origStr\r\n");
      print("New bytes: $modStr\r\n");
      print("\r\n");

      $numChanges++;

      if ($numChanges >= 10) {
        break;
      }
    } // .if
  } // .for

  $isChanged and print("\r\n-------------------------------------------\r\n");
} // .function detectChange

$prevFile    = null;
$prevModTime = null;

while (true) {
  usleep(100 * 1000);
  clearstatcache(true, FILE_PATH);

  if (is_file(FILE_PATH) && ($modTime = filemtime(FILE_PATH)) !== $prevModTime) {
    $file = unpackFile(file_get_contents(FILE_PATH));
    file_put_contents(preg_replace('~\.[^.]++\z~', '', FILE_PATH), $file);
    
    if (is_string($file)) {
      detectChange($file, $prevFile);
      $prevFile    = $file;
      $prevModTime = $modTime;
    }
  }
}