<?php

// This function emulates the php glob function with is disabled at free.fr !

// http://mart.1.free.fr/progfree.php

function freeGlob($path) {
  $result = array();
  $index = 0;
  preg_match("#^(.*/)?([^/]*)$#", $path, $matches);
  list(, $dir, $path) = $matches;
  $path = "#" . str_replace(array("\\", ".", "[", "]", "?", "+", "(", ")", "#", "*"),
                            array("\\\\", "\\.", "\\[", "\\]", "\\?","\\+", "\\(", "\\)", "\\#", "([^/]*)"),
                            $path) . "#";
  if ($dir == '')
    $handle = opendir('.');
  else
    $handle = opendir($dir);
  while($file = readdir($handle))
  {
    if ($file == '..' || $file == '.')
      continue;
    if(preg_match($path, $file))
    {
      if (is_file($dir . $file))
      {
        $result[$index] = $dir . $file;
        $index++;
      }
    }
  }
  closedir($handle);
  return $result;
}

?>