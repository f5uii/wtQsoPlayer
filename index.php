<?php

/**********************************************************/
/*  This script is (C) Nov 2009 by Laurent Haas F6FVY     */
/*  You can copy or use this script by naming the author  */
/*  on your website with a link to the Win-Test site      */
/**********************************************************/

/* Version history

v1.2 : Feb 2024
- Tailwindcss, html5 audio player, and multi contests

v1.1 : July 31 2010
- $callsign variable trimmed
- File type specified in the listening link

Tnx N6TV

v1.0 : Dec 2 2009
- Initial version

*/


// CONFIGURATION
$myCallsign = "FY5KE"; // Uppercase
$clipPath = "audioclips/";

$preQsoDuration = 30; // Seconds
$postQsoDuration = 30; // Seconds
$contestList = "contest_list.csv" ; //To be complete separatily each time you want to add a contest online
// CONFIGURATION (END)


if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $selectedNomContest = $_POST["Contest"];
  if ($selectedNomContest) {
    $details= getContestDataByRepertoire($selectedNomContest,$contestList);
  } else {
    $details=getLastContestDataFilteredByDate($contestList);
  }
}
else {
  if (isset ($_GET["contest"])) // Search contest with an url link example : ?search=f6ref&contest=wtQSO_fy5ke_REF_ssb_2024
  {
    $selectedNomContest = $_GET["contest"];
    if ($selectedNomContest) {
      $details= getContestDataByRepertoire($selectedNomContest,$contestList);
    } else {
      $details=getLastContestDataFilteredByDate($contestList);
    }
  } else
  $details=getLastContestDataFilteredByDate($contestList);
}
if ($details) {
  $contestName= $details['CONTEST_NAME'];
  $contestYear = $details['CONTEST_YEAR'];
  $dataPath = $details['DIRECTORY'];
  if ($details['IMAGE'] && file_exists($dataPath . '/' . $details['IMAGE'])) {
    $image = $dataPath  . '/' . $details['IMAGE'];
  }  else {
    $image = null;
  }
}
if (isset ($_POST["search"])) // Search callsign with the form
{
  $callsign = trim($_POST["search"]);
  if (isset($_GET["band"])) // Reset GET variables if the current URL have some of them 
  unset($_GET["band"]);
  if (isset($_GET["mode"]))
  unset($_GET["mode"]);
}
else if (isset ($_GET["search"] )) // Direct search with the URL
$callsign = strtoupper(urldecode($_GET["search"]));
$files = glob($dataPath . '/*.ts.txt');
if (count($files) === 1) {
  $tsFile = basename($files[0]);
} else {
  echo '<span class="font-bold text-red-500">No matching timestamp file or several timestamp files found.</span>';
}
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$url = $protocol . '://' . $_SERVER['HTTP_HOST'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url=rtrim(dirname($url), '/').'/';

include ("mp3class.php");
?>
<html>
<head>
<title><?php echo $contestName; ?> contest : Win-Test Qso Player by F6FVY | F5UII</title>
<meta charset="UTF-8">
<meta name=" robots" content=" index, follow">
<meta name="description" content="Listen to QSO with the amateur station <?php echo $myCallsign; ?> during <?php echo $contestName; ?> contest.">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<script>
function PlayQSO(nouveauFichier) {
  var lecteurAudio = document.getElementById('lecteurAudio');
  var sourceAudio = document.getElementById('sourceAudio');
  sourceAudio.src = nouveauFichier;
  lecteurAudio.load();
  lecteurAudio.play();
}
</script>
<meta property="og:title" content="<?php echo $contestName; ?> contest : Win-Test Qso Player by F6FVY | F5UII" />
<meta property="og:description" content="Listen to your QSO with <?php echo $myCallsign; ?> during HamRadio contest." />
<meta property="og:url" content="<?php echo $url ; ?>" />
<meta property="og:image" content="<?php echo $url .'wtQsoPlayer.png';?>" />

<meta name="twitter:title" content="<?php echo $contestName; ?> contest : Win-Test Qso Player by F6FVY | F5UII">
<meta name="twitter:description" content="Listen to your QSO with <?php echo $myCallsign; ?> during HamRadio contest.">
<meta name="twitter:image" content="<?php echo $url .'wtQsoPlayer.png';?>">
<meta name="twitter:site" content="<?php echo $url ; ?>" />
<meta name="twitter:card" content="summary">

</head>


<body class="  m-5">
<div class="flex items-center justify-center">
<h1  class="text-2xl sm:text-4xl  font-bold mb-6 ">Listen to your QSO with <?php echo $myCallsign; ?> during the <?php echo $contestName; ?> contest</h1>
<!-- <h1  class="text-4xl font-bold mb-4">Ecoutez votre QSO avec <?php echo $myCallsign; ?> durant le contest <?php echo $contestName; ?></h1> -->
</div>
<div class=" mb-5 grid grid-cols-1  md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 gap-4">

<div class="bg-gray-200 p-4 text-center rounded flex items-center">
<?php

if ( $image == null ) {
  if (file_exists("qsl.jpg")) { $image = 'qsl.jpg' ; } 
} 

echo "<img class='rounded-md mx-auto' src='".$image."'>";



?>
</div> 
<div class="bg-gray-200 p-4 text-center rounded">
<form action='' method='post'>
Enter your callsign :
<INPUT type="text" class="bg-blue-200 rounded-md text-blue-gray-700 font-sans font-normal outline outline-0 focus:outline-0 disabled:bg-blue-gray-50 disabled:border-0 transition-all placeholder-shown:border placeholder-shown:border-blue-gray-200 placeholder-shown:border-t-blue-gray-200 border focus:border-2 border-t-transparent focus:border-t-transparent text-sm px-3 py-2.5 rounded-[7px] border-blue-gray-200 focus:border-gray-900"
placeholder="Callsign" size="12" maxlength="12" name="search" value="<?php if (isset($callsign)) echo $callsign; ?>" tabindex="1">
<!-- <button class= 'rounded text-white bg-sky-500 hover:bg-sky-700' type="submit" value="Search"></button> -->
<?php echo '<input type="hidden" name="Contest" value="'.$dataPath.'">'; ?>
<button class="bg-blue-500 hover:bg-blue-600  text-white p-2 rounded-md">
Search
</button>
</form>

<audio id="lecteurAudio" controls class="mb-5 mx-auto">
<source id="sourceAudio" src="" type="audio/mp3">
Your browser does not support audio | Votre navigateur ne supporte pas l'élément audio
</audio>

</div>


<!-- Contenu de la 3eme colonne -->
<div class="bg-gray-200 p-4 rounded">

<?php

// Bands and mode(s) used

if (isset($_GET["band"])) // Searched band in the URL
$aBands[] = $_GET["band"];
else
{
  // List of the available bands for this contest file
  // $aBands[] = "160";
  // $aBands[] = "80";
  // $aBands[] = "40";
  // $aBands[] = "20";
  // $aBands[] = "15";
  // $aBands[] = "10";
  
  foreach (['10M','15M','20M','40M','80M', '160M'] as $band) {
    if ($details[$band] === '1') {
      $aBands[] = rtrim($band, 'M');
    }
  }
  
  
}



if (isset($_GET["mode"])) // Searched mode in the URL
$aModes[] = strtoupper($_GET["mode"]);
else
{
  //$aModes[] = "SSB"; 
  // List of the available modes for this contest file
  
  if($details['MODE_SSB'] === '1') {
    $aModes[] = "SSB";
  }
  if($details['MODE_CW'] === '1') {
    $aModes[] = "CW";
  }
  
}

// Delete audio clips older than 24h

$hours = 1;
$limit = time() - (60 * 60 * $hours);

if (! function_exists("glob") or glob(basename($_SERVER['PHP_SELF'])) == NULL) // Some providers disable the glob function 
{
  include ("freeGlob.php"); // Use our own glob function
  $files = freeGlob($clipPath . "*.mp3");
}
else
$files = glob($clipPath . "*.mp3");

foreach ($files as $filename)
if (filemtime($filename) < $limit)
unlink($filename);

// Search in log

if (! isset($callsign) || $callsign == '') // Leave
{
  echo "Please type in a callsign";
} else

if (!preg_match("@[A-Za-z0-9/]{3,}@", $callsign)) // Callsign error !
{
  echo "Invalid callsign !";
}

else

if (($callsign = strtoupper($callsign)) == $myCallsign)
{
  echo "Are your serious? ;-)";
  
}
else {
  
  
  // Get the version of the timestamps file (reserved for future use)
  
  $tsVersion = 100; // By default
  
  if ($fpLog = fopen($dataPath.'/'.$tsFile, "rt"))
  {
    $line = fgets($fpLog, 128);
    
    while ($line[0] == "#")
    {
      if ( (strstr($line, "# VERSION ") == $line) and (strlen($line)) > 12)
      {
        $tsVersion = (int) substr($line, 10);
        break;
        
      }
      $line = fgets($fpLog, 128);
    }
    
    fclose($fpLog);
  }
  
  // Search all timestamps regarding this callsign
  
  if ($fpLog = fopen($dataPath.'/'.$tsFile, "rt"))
  {
    $regExp = sprintf("@^\"%s\"@", $callsign);
    
    while (!feof($fpLog)) // Extract lines in the $log array
    {
      $line = fgets($fpLog, 128);
      if (preg_match($regExp, $line))
      $log[] = $line; // Append the line to the $log array
    }
    
    fclose($fpLog);
  }
  
  if (! isset($log))
  print("<p >No QSO found...");
  else
  {
    if ($tsVersion > 100) // This version of wtQsoPlayer only process version 100
    print("<p>This version of timestamps file is currently not supported");
    else
    {
      // Browse $log lines, and search a QSO for each band and mode
      echo '<div class="grid grid-cols-2 gap-4 ">';
      foreach ($aBands as $i => $band)
      foreach ($aModes as $j => $mode)
      {
        $regExp = sprintf("@^\"%s\" \"%s\" \"%s\" \".*\" \"(.*)\" ([\-0-9]+)@", $callsign, $band, $mode);
        $found = 0;
        $dupe = 0;
        foreach ($log as $k => $logLine)
        {
          if (preg_match($regExp, $logLine, $matches) > 0)
          {
            if ($matches[1] == "" or !file_exists($dataPath . '/' .$matches[1]) or ($matches[2] < 0))
            printf('<div class="bg-gray-200  flex items-center justify-end"><span class="font-semibold my-4">%sm %s :</span></div><div class="bg-gray-200 p-4 text-left"> QSO found but no audio available</div>', $band, $mode);
            else // Extract clip
            {
              if (! isset($mp3Files[$matches[1]]))
              $mp3Files[$matches[1]] = new mp3($dataPath . '/'.$matches[1], $myCallsign, $contestName, $contestYear);
              
              if ($dupe)
              $clipName = sprintf("%s%s_%s_%s_%d.mp3", $clipPath, str_replace("/", "_", $callsign), $band, $mode, $dupe);
              else
              $clipName = sprintf("%s%s_%s_%s.mp3", $clipPath, str_replace("/", "_", $callsign), $band, $mode);
              
              $title = sprintf("%s - %s (%sm %s)", $callsign, $contestName, $band, $mode);
              $mp3Files[$matches[1]]->extract($matches[2], $preQsoDuration, $postQsoDuration, $clipName, $title);
              printf('<div class="bg-gray-200 flex items-center justify-end "><span class=" font-semibold ">%sm %s %s :</div><div class="bg-gray-200 text-left"> <a type="audio/mpeg" href="%s" download="%s"> <button title="Download" class="bg-blue-500 hover:bg-blue-600 stroke-white text-white rounded-md px-4 py-2 m-1 "><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" class="w-6 h-6"><path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg></a></button>', $band, $mode, ($dupe ? "(Dupe)" : ""),$clipName,$myCallsign.'_'.$clipName);
                echo '<button  title="Listen, Play" onclick="PlayQSO(\''.$clipName.'\')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2  rounded-md"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg></button></div>';
                
                $dupe++;
              }
              $found = 1;
            }
          }
          
          if ($found == 0)
          printf('<div class="bg-gray-200 flex items-center justify-end ">%sm %s :</div><div class="bg-gray-200 text-left"><span title="Sorry, no QSO found"> ❌ </span></div>', $band, $mode);
        }
        echo '</div>';
      }
    }
  }
  
  ?>
  
  
  </div>
  
  
  </div>

  <?php 
   $fichier = fopen($contestList, 'r');
  
  if ($fichier) {
    fgetcsv($fichier, 0, ';');
    $options = array();

    while (($ligne = fgetcsv($fichier, 0, ';')) !== false) {
      $nomContest = $ligne[0];
      $repertoire = $ligne[1];
      $date = $ligne[2];
    
      $timestampDate = strtotime($date.'');
      if ($timestampDate <= strtotime('today UTC')) {
        $options[$nomContest] = $repertoire;
        if (is_dir($repertoire)) {
          $mp3Files = glob($repertoire . '/*.mp3');
          $ts = glob($repertoire . '/*.ts.txt');
          if (empty($mp3Files)) {
            $err[$nomContest] =  ' (mp3 missing)';
          } elseif (!$ts) { // Si le fichier ts.txt est manquant
            $err[$nomContest] = ' (timestamp file missing)';
          } else {
            $options[$nomContest] = $repertoire;
          }
        } else {
          $err[$nomContest] =  ' (directory missing)';
        }
      }
    }
    

    fclose($fichier);
    
   
    echo '<form action="" method="post">';
    echo '<label for="Contest">Contest selection:</label>';
    
    echo '<select class="bg-blue-200 rounded px-4 py-1 m-1 " name="Contest" id="Contest">';
    
    foreach ($options as $nomContest => $repertoire) {
      if ($details["CONTEST_NAME"]== $nomContest)  {
        $selected=" selected ";
      } else $selected ="";
      $disabled = strpos($err[$nomContest], 'missing') !== false ? 'disabled' : ''; // Désactiver le select si "missing" est dans la description
      echo '<option value="' . htmlspecialchars($repertoire) . '" ' . $disabled . $selected. '>' . htmlspecialchars($nomContest) .' ' .$err[$nomContest] . '</option>';
    }
    
    echo '</select>';
    echo '<input type="submit"  class="rounded px-4 py-1 bg-blue-200 m-1 " value="Go">';
    echo '</form>';
  } else {
    echo "The contest list CSV is missing.";
  }
  
  

  function getLastContestDataFilteredByDate($cheminFichierCSV) {
    $toutesLesLignes = file($cheminFichierCSV, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if (empty($toutesLesLignes)) {
        return null;
    }
    
    $entete = str_getcsv($toutesLesLignes[0], ';');
    $dateIndex = array_search('PUBLICATION(YYYY-MM-DD)', $entete); // Trouver l'index de la colonne "DATE"
    
    $resultat = null;
    
    foreach ($toutesLesLignes as $ligne) {
        $donnees = str_getcsv($ligne, ';');
        
        // Vérifier si la date est inférieure à la date du jour
        if (isset($donnees[$dateIndex])) {
            $date = $donnees[$dateIndex];
            $timestampDate = strtotime($date . '');
            
            if ($timestampDate <= strtotime('today UTC')) {
                $resultat = array_combine($entete, $donnees);
            }
        }
    }
    
    return $resultat;
}



  
  function getContestDataByRepertoire($nomRepertoire, $cheminFichierCSV) {
    // Ouvrez le fichier en mode lecture
    $fichier = fopen($cheminFichierCSV, 'r');
    
    // Vérifiez si le fichier est ouvert avec succès
    if ($fichier) {
      // Lisez la première ligne pour ignorer l'en-tête
      $entete = fgetcsv($fichier, 0, ';');
      
      // Vérifiez si la colonne "répertoire" existe dans l'en-tête
      if (!in_array('DIRECTORY', $entete)) {
        fclose($fichier);
        return null; // La colonne "répertoire" est manquante dans le fichier CSV.
      }
      
      // Parcourez le fichier ligne par ligne
      while (($ligne = fgetcsv($fichier, 0, ';')) !== false) {
        // Accédez à la valeur de la colonne "répertoire"
        $repertoire = $ligne[array_search('DIRECTORY', $entete)];
        
        // Vérifiez si le répertoire correspond à celui recherché
        if ($repertoire === $nomRepertoire) {
          // Fermez le fichier
          fclose($fichier);
          
          // Retournez toutes les données de la ligne
          return array_combine($entete, $ligne);
        }
      }
      
      // Fermez le fichier
      fclose($fichier);
    }
    
    // Aucune correspondance trouvée pour le répertoire spécifié
    return null;
  }
  ?>

<div class='fixed-bottom  text-right '><hr class='mt-10'><i>Search and audio extraction processed by wtQsoPlayer - (C) Laurent Haas F6FVY - Nov 2009 | F5UII - Feb 2024<br>
  A companion program of the <a class='underline font-semibold' href='http://www.win-test.com' target='blank_' >Win-Test contest logger</a></i></div>
  
  </body>
  </html>
  
