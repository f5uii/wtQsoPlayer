<?php

/**********************************************************/
/*  Contest Search Page for wtQsoPlayer                   */
/*  Search across all contests for a specific callsign   */
/**********************************************************/

// CONFIGURATION
include("config.php");
include("mp3class.php");
// CONFIGURATION (END)

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$url = $protocol . '://' . $_SERVER['HTTP_HOST'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = dirname($url) . '/';

$searchResults = [];
$callsign = '';
$searchPerformed = false;

// Process direct contest selection
if (isset($_POST["Contest"]) && !empty($_POST["Contest"]) && !isset($_POST["search"])) {
    // Redirect to index.php with the selected contest
    header("Location: index.php?contest=" . urlencode($_POST["Contest"]));
    exit();
}

// Process search
if (isset($_POST["search"]) || isset($_GET["search"])) {
    $callsign = isset($_POST["search"]) ? trim($_POST["search"]) : strtoupper(urldecode($_GET["search"]));
    $callsign = strtoupper($callsign);
    $searchPerformed = true;

    // Validate callsign
    if (preg_match("@[A-Za-z0-9/]{3,}@", $callsign) && $callsign != $myCallsign) {
        // Read contest list
        if (file_exists($contestList)) {
            $fichier = fopen($contestList, 'r');

            if ($fichier) {
                fgetcsv($fichier, 0, ';'); // Skip header

                // Browse all contests
                while (($ligne = fgetcsv($fichier, 0, ';')) !== false) {
                    $nomContest = $ligne[0];
                    $repertoire = $ligne[1];
                    $date = $ligne[2];

                    // Check if contest is published
                    $timestampDate = strtotime($date . '');
                    if ($timestampDate <= strtotime('today UTC')) {
                        // Check if directory exists
                        if (is_dir($repertoire)) {
                            // Find timestamp file
                            $tsFiles = glob($repertoire . '/*.ts.txt');

                            if (!empty($tsFiles)) {
                                $tsFile = basename($tsFiles[0]);
                                $tsFullPath = $repertoire . '/' . $tsFile;

                                // Read timestamp file to search for callsign
                                if ($fpLog = fopen($tsFullPath, "rt")) {
                                    $regExp = sprintf("@^\"%s\"@", $callsign);
                                    $qsoCount = 0;

                                    while (!feof($fpLog)) {
                                        $line = fgets($fpLog, 128);
                                        if (preg_match($regExp, $line)) {
                                            $qsoCount++;
                                        }
                                    }

                                    fclose($fpLog);

                                    // If QSO found, add to results
                                    if ($qsoCount > 0) {
                                        $searchResults[] = [
                                            'contest' => $nomContest,
                                            'directory' => $repertoire,
                                            'count' => $qsoCount,
                                            'date' => $date
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }

                fclose($fichier);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Contest Search - Win-Test QSO Player by F6FVY | F5UII</title>
    <meta charset="UTF-8">
    <meta name="robots" content="index, follow">
    <meta name="description" content="Search for your QSO across all contests with <?php echo $myCallsign; ?>.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <meta property="og:title" content="Contest Search - Win-Test QSO Player by F6FVY | F5UII" />
    <meta property="og:description" content="Search for your QSO across all HamRadio contests." />
    <meta property="og:url" content="<?php echo $url; ?>" />
    <meta property="og:image" content="<?php echo $url . 'wtQsoPlayer.png'; ?>" />
</head>

<body class="m-5">
    <div class="flex items-center justify-center mb-8">
        <h1 class="text-2xl sm:text-4xl font-bold text-center">
            Search your QSO with <?php echo $myCallsign; ?> across all contests
        </h1>
    </div>

    <!-- Search Form -->
    <div class="max-w-2xl mx-auto mb-8">
        <div class="bg-gray-200 p-6 rounded-lg">
            <form action="" method="post" class="flex flex-col sm:flex-row gap-4 items-center justify-center">
                <label for="search" class="font-semibold">Enter your callsign:</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    class="bg-blue-200 rounded-md text-blue-gray-700 font-sans font-normal outline outline-0 focus:outline-0 disabled:bg-blue-gray-50 disabled:border-0 transition-all placeholder-shown:border placeholder-shown:border-blue-gray-200 placeholder-shown:border-t-blue-gray-200 border focus:border-2 border-t-transparent focus:border-t-transparent text-sm px-3 py-2.5 rounded-[7px] border-blue-gray-200 focus:border-gray-900"
                    placeholder="Callsign"
                    size="12"
                    maxlength="12"
                    value="<?php echo htmlspecialchars($callsign); ?>"
                    tabindex="1"
                    required
                />
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md font-semibold">
                    Search
                </button>
            </form>
        </div>
    </div>

    <!-- Results -->
    <?php if ($searchPerformed): ?>
        <div class="max-w-4xl mx-auto">
            <?php if (empty($callsign) || !preg_match("@[A-Za-z0-9/]{3,}@", $callsign)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p class="font-bold">Invalid callsign!</p>
                    <p>Please enter a valid amateur radio callsign (at least 3 characters).</p>
                </div>
            <?php elseif ($callsign == $myCallsign): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    <p class="font-bold">Are you serious? ;-)</p>
                </div>
            <?php elseif (empty($searchResults)): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
                    <p class="font-bold">No QSO found</p>
                    <p>No contacts found for callsign <strong><?php echo htmlspecialchars($callsign); ?></strong> in any published contest.</p>
                </div>
            <?php else: ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <p class="font-bold">
                        <?php echo count($searchResults); ?> contest<?php echo count($searchResults) > 1 ? 's' : ''; ?> found for
                        <strong><?php echo htmlspecialchars($callsign); ?></strong>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($searchResults as $result): ?>
                        <div class="bg-white border border-gray-300 rounded-lg p-6 shadow-md hover:shadow-lg transition-shadow">
                            <h2 class="text-xl font-bold mb-2 text-gray-800">
                                <?php echo htmlspecialchars($result['contest']); ?>
                            </h2>
                            <p class="text-gray-600 mb-4">
                                <span class="font-semibold"><?php echo $result['count']; ?></span> QSO<?php echo $result['count'] > 1 ? 's' : ''; ?> found
                            </p>
                            <p class="text-sm text-gray-500 mb-4">
                                Date: <?php echo htmlspecialchars($result['date']); ?>
                            </p>
                            <a
                                href="index.php?search=<?php echo urlencode($callsign); ?>&contest=<?php echo urlencode($result['directory']); ?>"
                                class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md font-semibold transition-colors"
                            >
                                Listen to QSO
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="max-w-2xl mx-auto text-center text-gray-600">
            <p class="text-lg">Enter your callsign above to search across all available contests.</p>
        </div>
    <?php endif; ?>

    <!-- Direct Contest Selection -->
    <div class="max-w-2xl mx-auto mt-12 mb-8">
        <hr class="mb-6">
        <div class="bg-gray-100 p-6 rounded-lg">
            <h2 class="text-center text-xl font-semibold mb-4">Or browse a specific contest directly</h2>

            <?php
            // Load contest list for direct selection
            if (file_exists($contestList)) {
                $fichier = fopen($contestList, 'r');

                if ($fichier) {
                    fgetcsv($fichier, 0, ';'); // Skip header
                    $options = array();
                    $err = array();

                    while (($ligne = fgetcsv($fichier, 0, ';')) !== false) {
                        $nomContest = $ligne[0];
                        $repertoire = $ligne[1];
                        $date = $ligne[2];

                        $timestampDate = strtotime($date . '');
                        if ($timestampDate <= strtotime('today UTC')) {
                            if (is_dir($repertoire)) {
                                $mp3Files = glob($repertoire . '/*.mp3');
                                $ts = glob($repertoire . '/*.ts.txt');

                                if (empty($mp3Files)) {
                                    $err[$nomContest] = ' (mp3 missing)';
                                } elseif (empty($ts)) {
                                    $err[$nomContest] = ' (timestamp file missing)';
                                } else {
                                    $options[$nomContest] = $repertoire;
                                }
                            } else {
                                $err[$nomContest] = ' (directory missing)';
                            }
                        }
                    }

                    fclose($fichier);

                    if (!empty($options)) {
                        echo '<form action="" method="post" class="text-center">';
                        echo '<label for="Contest" class="font-semibold mr-2">Contest selection:</label>';
                        echo '<select class="bg-blue-200 rounded px-4 py-2 m-1" name="Contest" id="Contest">';
                        echo '<option value="">-- Select a contest --</option>';

                        foreach ($options as $nomContest => $repertoire) {
                            $disabled = isset($err[$nomContest]) && strpos($err[$nomContest], 'missing') !== false ? 'disabled' : '';
                            $errorMsg = isset($err[$nomContest]) ? $err[$nomContest] : '';
                            echo '<option value="' . htmlspecialchars($repertoire) . '" ' . $disabled . '>' . htmlspecialchars($nomContest) . $errorMsg . '</option>';
                        }

                        echo '</select>';
                        echo '<input type="submit" class="rounded px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold m-1 cursor-pointer" value="Go">';
                        echo '</form>';
                    } else {
                        echo '<p class="text-center text-gray-600">No contests available at the moment.</p>';
                    }
                } else {
                    echo '<p class="text-center text-red-600">Unable to open the contest list.</p>';
                }
            } else {
                echo '<p class="text-center text-red-600">The contest list CSV is missing.</p>';
            }
            ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-10">
        <hr class="mt-10 mb-4">
        <i class="text-sm text-gray-600">
            Search processed by wtQsoPlayer - (C) Laurent Haas F6FVY - Nov 2009 | F5UII - Feb 2024, Jan 2026<br>
            A companion program of the <a class="underline font-semibold" href="http://www.win-test.com" target="_blank">Win-Test contest logger</a>
        </i>
    </div>
</body>
</html>
