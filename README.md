# wtQsoPlayer

## What is it ?

This free and open source package allows you to publish on the Web your contest audio recordings done with [Win-Test](http://www.win-test.com/) v4.
Once setup, anyone will be able to search in your log and listen to his (her) QSOs, if audio was recorded at that time.
The extracted clips are totally compatible with the mp3 standard, and their ID3 tags are automatically filled. It means you can download, save and replay them locally.

This package is totally open, feel free to submit and share your work with the community !

<img src="wtQsoPlayer.svg" alt="Search and audio extraction processed by wtQsoPlayer" width="200">

## Requirements

- Win-Test 4.4.0-dev revision 236 or better
- One .wt4 log and its associated audio recording(s)
- Web hosting space (available space must be at least the size of the mp3 file(s) + 1MB)
- PHP must be available on the hosting space (No mySQL database required)
- Minimal knowledge on web hosting and FTP usage

## Export data from Win-Test

- In Win-Test, export timestamps in a file (Contextual menu of the recorder window). Use the raw format (TXT). All the mp3 related to this contest log must be present in the log directory when exporting the timestamps. The file must stay with it extension name .ts.txt

![Screenshot of a context menu on Audio Recorder](wtQSOPlayer%20Export%20Audio%20timestamp.png)

## Installation and configuration on web server

- Transfer the unzipped software on your webserver
- Specify the data in the index.php file, in section `CONFIGURATION` your callsign (`$myCallsign`), and if you want modify the other parameters like duration of the audio extraction.
- Create a subdirectory for (each different) contest you want to publish.
 - Transfer in it **all** the Win-Test mp3 files, and also the timestamps file (`*.ts.txt`)
 - You can transfer a JPEG image replacing the file qsl.jpg (main directory) or put an specific image for each contest (in contest subdirectory, with a specific name).
- Configure now the list of managed contest. You will add a line for each more contest : Edit contest_list.csv
  - Do not modify the header line
  - Specify all the columns
    - `CONTEST NAME` : Whaterver you want for naming the Contest
    - `DIRECTORY` : Must correspond to the contest subdirectory where mp3 files and timestamps file are located
    - `PUBLICATION(YYYY-MM-DD)`: Date (format `YYYY-MM-DD`) from which your contest will be accessible on the server. We advise you not to publish your audio recordings before the deadline of log submission.
    - `MODE_SSB` :  1 if phone (SSB) is practiced during the contest, 0 if not.
    - `MODE_CW` : 1 if morse (CW) is practiced during the contest, 0 if not.
    - `CONTEST_YEAR` : Year of the contest (example 2024)
    - `160M` : Put 1 if this band is one of the bands in the contest, otherwise put 0.
    - `80M` : Put 1 if this band is one of the bands in the contest, otherwise put 0.
    - `40M` : Put 1 if this band is one of the bands in the contest, otherwise put 0.
    - `20M` : Put 1 if this band is one of the bands in the contest, otherwise put 0.
    - `15M` : Put 1 if this band is one of the bands in the contest, otherwise put 0.
    - `10M` : Put 1 if this band is one of the bands in the contest, otherwise put 0.
    - `IMAGE` : The image filename you have included in the contest subdirectory (example `"REF_ssb_2024.jpg"`)

## Access

You will access the web page pointing the `index.php` root file.
You may want to add some webserver rules for deny the access to the main mp3 files. Also you can add empty `index.htm` file in your contest subdirectories.

### URL parameters

You can reach a specific contest and callsign by add in the URL the parameters like `index.php?search=f5uii&contest=REF_SSB_2024` :
- `search=` : callsign
- `contest=` : contest subdirectory

# History & Credits

- v1.0 : Dec 2 2009 by F6FVY
- v1.1 : July 31 2010 by F6FVY ([Utils download](http://download.win-test.com/utils/))
- v1.2 : Feb 2024 by [F5UII](https://www.f5uii.net)




