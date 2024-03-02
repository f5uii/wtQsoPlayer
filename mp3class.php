<?php

// Class inspired by http://www.sourcerally.net/Scripts/20-PHP-MP3-Class

// But, among others, this one doesn't need require the *entire* file in memory !!!

// Limitations :
// - VBR and ABR files are *not* supported
// - The frames count is computed (no sync errors compensation)

/**********************************************************/
/*  This script is (C) Nov 2009 by Laurent Haas F6FVY     */
/*  You can copy or use this script by naming the author  */
/*  on your website with a link to the Win-Test site      */
/**********************************************************/

class mp3
{
  var $path = "";
  var $frameTag = ""; // 3 chars
  var $frameOffset = -1; // Offset of the first frame from the beginning of the file
  var $frameLen = 0; // In bytes
  var $frameTime = 0; // In ms

  // Various data - Will be used in the ID3 tags of the extracted clips

  var $myCallsign;
  var $myContest;
  var $myYear;

  // Constructor

  function mp3($path = "", $myCallsign = "", $myContest = "", $myYear = "")
  {
    if ($path != "")
      if (file_exists($path)) // Read file details
        if (filesize($path)) // If non-empty file
        {
          $this->path = $path;
          if (!$fp = fopen($path, "rb"))
          {
            echo "fopen error\n";
            return;
          }

          // Extract the ID3 size (we assume there is one !)

          fseek($fp, 6, SEEK_CUR);
          $str = fread($fp, 4);

          // Caution ! This size is coded in base 128 (not 256) - See http://www.id3.org/d3v2.3.0

          $base = 1;
          for ($i = 3; $i >=0; $i--)
          {
            $id3Size += (ord($str[$i]) * $base);
            $base *= 128;
          }

          fseek($fp, $id3Size, SEEK_CUR); // Skip the ID3 tags

          // Search for the frameTag

          while (!feof($fp))
          {
            $str = fread($fp, 4); // Read the 4 first chars and check if it is a frame header
            $parts = array();
            for ($i = 0; $i < 4; $i++)
              $parts[] = str_pad(decbin(ord($str[$i])), 8, "0", STR_PAD_LEFT); // Normalize each char in a 8-bit string
            if (substr($parts[1], 0, 3) == "111" && substr($parts[2], 0, 4) != "1111") // Found !
            {
              $this->frameTag = substr($str, 0, 3); // Restrict to the first 3 chars
              $this->frameOffset = ftell($fp) - 4; // Offset of the 1st frame
              $a = $this->decodeFrameHeader($parts); // Decoding of the frame header
              $this->frameLen = $a[0];
              $this->frameTime = $a[1];
              break;
            }
            else // Keep looking for the frame tag (not very safe !)
            {
              fseek($fp, -3, SEEK_CUR);
              while( ($c = @fgetc($fp)) != chr(255));
              fseek($fp, -1, SEEK_CUR); // Back one byte
            }
          }
          fclose($fp);

//        Debug
 
//					printf ("FrameLen : " . $this->frameLen. " ");
//					printf ("FrameTime : " . $this->frameTime. " ");
//					printf ("FrameOffset : " . $this->frameOffset. " ");

          // Additional data

          $this->myCallsign = $myCallsign;
          $this->myContest = $myContest;
          $this->myYear = $myYear;

          return;
        }
    echo "Bad path\n";
    return;
  }

  function extract($offset, $preOffsetDuration, $postOffsetDuration, $file, $title = "") // Times in seconds
  {
    if ($this->frameOffset < 0)
    {
      print("No mp3 frame in the original file");
      return;
    }

    if (file_exists($file)) // delete the clip file if it already exists
      unlink($file);

    $fpOrg = fopen($this->path, "rb");

    if (!$fpOrg)
    {
      print("Cant open file");
      return;
    }

    // Write id3v3 tag in the clip file

    $fp = fopen($file, "w");
    $strID3tag = $this->encodeIdv3_2(
      "1",
      $title,
      $this->myCallsign,
      $this->myContest,
      $this->myYear
      );
    fwrite($fp, $strID3tag);

    // Search for the first frameTag

    $offset = max($offset - (($preOffsetDuration * 1000 * $this->frameLen) / $this->frameTime), $this->frameOffset);
    fseek($fpOrg, $offset);

    $str = fread($fpOrg, 3);

    if ($str != $this->frameTag) // Unlucky - Search for the next frame header tag
    {
      $found = 0;
        while (false !== ($c = @fgetc($fpOrg))) // Search for the next 255 char
        {
          if (ord($c[0]) == 255)
          {
            fseek($fpOrg, -1, SEEK_CUR); // Back one position
            if (fread($fpOrg, 3) == $this->frameTag) // Found a frame header !
            {
              $found = 1;
              break;
            }
            else
              fseek($fpOrg, -2, SEEK_CUR); // Try again
          }
        }
    }
    else // Right on it ! Lucky me !
      $found = 1;

    if ($found)
    {
	    fseek($fpOrg, -3, SEEK_CUR); // The file ptr is now right on the first valid frame !

	    // Now, copy $preOffsetDuration + $postOffsetDuration seconds
	    // CAUTION : Possible sync errors at the end of the file are *not* fixed,
	    // but the serious mp3 players handle it with no problem.

	    $str = fread($fpOrg, (($preOffsetDuration + $postOffsetDuration) * 1000 * $this->frameLen) / $this->frameTime);
	    fwrite($fp, $str);
	  }

    fclose($fpOrg);
    fclose($fp);
  }

  function decodeFrameHeader($parts)
  {
    // Get Audio Version

    $errors = array();
    switch(substr($parts[1], 3, 2))
    {
      case '01':
        $errors[] = 'Reserved audio version';
        break;
      case '00':
        $audio = 25; // Stands for 2.5 but we restrict to integer values
        break;
      case '10':
        $audio = 2;
        break;
      case '11':
        $audio = 1;
        break;
    }
//        printf("Audio => $audio\n");
    // Get Layer
    
    switch(substr($parts[1], 5, 2))
    {
      case '01':
        $layer = 3;
        break;
      case '00':
        $errors[] = 'Reserved layer';
        break;
      case '10':
        $layer = 2;
        break;
      case '11':
        $layer = 1;
        break;
    }
//        printf("Layer => $layer\n");
    // Get Bitrate
    
    $bitFlag = substr($parts[2], 0, 4);
    $bitArray = array(
//    '0000'    => array(free,    free,    free,    free,    free), // replace free by 0 to make php happy
    '0000'    => array(0,    0,    0,    0,    0),
    '0001'    => array(32,    32,    32,    32,    8),
    '0010'    => array(64,    48,    40,    48,    16),
    '0011'    => array(96,    56,    48,    56,    24),
    '0100'    => array(128,    64,    56,    64,    32),
    '0101'    => array(160,    80,    64,    80,    40),
    '0110'    => array(192,    96,    80,    96,    48),
    '0111'    => array(224,    112,    96,    112,    56),
    '1000'    => array(256,    128,    112,    128,    64),
    '1001'    => array(288,    160,    128,    144,    80),
    '1010'    => array(320,    192,    160,    160,    96),
    '1011'    => array(352,    224,    192,    176,    112),
    '1100'    => array(384,    256,    224,    192,    128),
    '1101'    => array(416,    320,    256,    224,    144),
    '1110'    => array(448,    384,    320,    256,    160),
//    '1111'    => array(bad,    bad,    bad,    bad,    bad) // replace bad by -1 to make PHP happy
    '1111'    => array(-1,    -1,    -1,    -1,    -1)
    );
    $bitPart = $bitArray[$bitFlag];
    if ($audio == 1)
    {
      switch($layer)
      {
        case 1:
          $bitArrayNumber = 0;
          break;
        case 2:
          $bitArrayNumber = 1;
          break;
        case 3:
          $bitArrayNumber = 2;
          break;
      }
    }
    else
    {
      switch($layer)
      {
      case 1:
        $bitArrayNumber = 3;
        break;
      case 2:
        $bitArrayNumber = 4;
        break;
      case 3:
        $bitArrayNumber = 4;
        break;
      }
    }
    $bitRate = $bitPart[$bitArrayNumber];
//        printf("BitRate => $bitRate\n");
    // Get Frequency

    $frequencies = array(
     1 => array('00' => 44100,
     '01'=>48000,
     '10'=>32000,
     '11'=>'reserved'),
     2 => array('00' => 22050,
     '01'=>24000,
     '10'=>16000,
     '11'=>'reserved'),        
     25 => array('00' => 11025,
     '01'=>12000,
     '10'=>8000,
     '11'=>'reserved'));

    $freq = $frequencies[$audio][substr($parts[2],4,2)];
//        printf("Parts => %s\n", substr($parts[2],4,2));
//        printf("Freq => $freq\n");

     // Is Padded?
    $padding = substr($parts[2], 6, 1);

    if ($layer == 3 || $layer == 2)
    {
      // See
      // http://www.hydrogenaudio.org/forums/index.php?s=20b0058047bc4e4df1c8db5975eccf43&showtopic=59105
      // http://minnie.tuhs.org/pipermail/mp3encoder/2003-February/005598.html

      if ($audio == 2 || $audio == 25) // MPEG 2
        $frameLength = ((72000 * $bitRate) / $freq) + $padding;
      else
        $frameLength = ((144000 * $bitRate) / $freq) + $padding;
    }
    else // layer 1
      $frameLength = 4 * (((12000 * $bitRate) / $freq) + $padding) ;
    
    $frameLength = floor($frameLength);

//        printf("FrameLen => $frameLength\n");
    $milliseconds = $frameLength * 8 / $bitRate;
    return array($frameLength, $milliseconds);
  }

  function encodeIdv3_2($track, $title, $artist, $album, $year, $genre = "", $comments = "", $composer = "", $origArtist = "", $copyright = "", $url = "", $encodedBy = "")
  {
    // id3v2.3.0

    // See http://www.id3.org/d3v2.3.0 for specs

    $str = "ID3";
    $str .= chr(3);// v2.*3*.0
    $str .= chr(0);// v2.3.*0*
    $str .= chr(0);// Flags
    $str .= chr(0);// Size 1
    $str .= chr(0);// Size 2
    $str .= chr(0);// Size 3
    $str .= chr(0);// Size 4 => See the end of the function (and the doc !)

    if ($track != "")
    {
      $trackLength = (int) strlen($track) + 1;

      $str .= "TRCK";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($trackLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $track;
    }

    if ($encodedBy != "")
    {
      $encodedByLength = (int)(strlen($encodedBy) + 1);

      $str .= "TENC";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($encodedByLength);
      $str .= '@';//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $encodedBy;
    }

    if ($url != "")
    {
      $urlLength = (int)(strlen($url) + 2);

      $str .= "WXXX";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($urlLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $url;
    }

    if ($copyright != "")
    {
      $copyrightLength = (int)(strlen($copyright) + 1);

      $str .= "TCOP";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($copyrightLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $copyright;
    }

    if ($origArtist)
    {
      $origArtistLength = (int)(strlen($origArtist) + 1);

      $str .= "TOPE";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($origArtistLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $origArtist;
    }

    if ($composer != "")
    {
      $composerLength = (int)(strlen($composer) + 1);

      $str .= "TCOM";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($composerLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $composer;
    }

    if ($comments != "")
    {
      $commentsLength = (int)strlen($comments) + 5;

      $str .= "COMM";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($commentsLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(9);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $comments;
    }

    if ($genre != "")
    {
      $genreLength = (int) strlen($genre) + 1;

      $str .= "TCON";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($genreLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $genre;
    }

    if ($year != "")
    {
      $yearLength = (int) strlen($year)+1;

      $str .= "TYER";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($yearLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $year;
    }

    if ($album != "")
    {
      $albumLength = (int) strlen($album) + 1;

      $str .= "TALB";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($albumLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $album;
    }

    if ($artist != "")
    {
      $artistLength = (int)strlen($artist) + 1;

      $str .= "TPE1";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($artistLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $artist;
    }

    if ($title != "")
    {
      $titleLength = (int) strlen($title) + 1;

      $str .= "TIT2";
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr($titleLength);
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= chr(0);//
      $str .= $title;
    }

    // Zero-padding to the next 512 bytes border
    // to allow easy tag modifications if needed
    
    while (strlen($str) % 512 != 0)
      $str .= chr(0);

    // Set the tag size (See http://www.id3.org/d3v2.3.0)
    // We assume a size < 16384

    $size = strlen($str) - 10;
    $str[8] = chr($size / 128);
    $str[9] = chr($size % 128);

    return $str;
  }
}
?>