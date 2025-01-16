<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AES Algorithm</title>
</head>


<?php
function shiftRows(&$array, $posizioni)
{
    $elementiRimossi = array_splice($array, 0, $posizioni);
    $array = array_merge($array, $elementiRimossi);
}

function moltiplicaPolinomi($poly1, $poly2)
{
    $resultSize = count($poly1) + count($poly2) - 1;
    $result = array_fill(0, $resultSize, 0);

    for ($i = 0; $i < count($poly1); $i++) {
        for ($j = 0; $j < count($poly2); $j++) {
            $result[$i + $j] += $poly1[$i] * $poly2[$j];
        }
    }

    return $result;
}


function divisionePolinomiale($dividendo, $divisore)
{
    $n = count($dividendo);
    $m = count($divisore);

    // Se il divisore è più grande del dividendo, non possiamo dividere
    if ($n < $m) {
        return [$q = [0], $dividendo];
    }

    $q = array_fill(0, $n - $m + 1, 0);

    // Eseguiamo la divisione per ogni termine del dividendo
    for ($i = 0; $i <= $n - $m; $i++) {
        if ($dividendo[$i] == 0) {
            continue;
        }

        // Calcoliamo il coefficiente del quoziente
        $q[$i] = $dividendo[$i] / $divisore[0];

        // Sottrai il prodotto del quoziente e del divisore dal dividendo
        for ($j = 0; $j < $m; $j++) {
            $dividendo[$i + $j] = ($dividendo[$i + $j] - ($q[$i] * $divisore[$j])) % 256;
        }
    }

    // Rimuoviamo i termini zero finali dal dividendo (remainder)
    while (count($dividendo) > 0 && $dividendo[count($dividendo) - 1] == 0) {
        array_pop($dividendo);
    }

    return $dividendo; // Restituiamo il resto
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $chars = str_split($_POST["chars"]);

    if (count($chars) == 16) {
        // Input
        $matrice = [];
        $caratteri_inseriti = array_chunk($chars, 4);

        for ($i = 0; $i <= 3; $i++) {
            for ($j = 0; $j <= 3; $j++) {
                $matrice[$j][$i] = base_convert((string)ord($caratteri_inseriti[$i][$j]), 10, 16);
            }
        }

        // Substitute process
        $sBox = array(
            "63", "7c", "77", "7b", "f2", "6b", "6f", "c5", "30", "01", "67", "2b", "fe", "d7", "ab", "76",
            "ca", "82", "c9", "7d", "fa", "59", "47", "f0", "ad", "d4", "a2", "af", "9c", "a4", "72", "c0",
            "b7", "fd", "93", "26", "36", "3f", "f7", "cc", "34", "a5", "e5", "f1", "71", "d8", "31", "15",
            "04", "c7", "23", "c3", "18", "96", "05", "9a", "07", "12", "80", "e2", "eb", "27", "b2", "75",
            "09", "83", "2c", "1a", "1b", "6e", "5a", "a0", "52", "3b", "d6", "b3", "29", "e3", "2f", "84",
            "53", "d1", "00", "ed", "20", "fc", "b1", "5b", "6a", "cb", "be", "39", "4a", "4c", "58", "cf",
            "d0", "ef", "aa", "fb", "43", "4d", "33", "85", "45", "f9", "02", "7f", "50", "3c", "9f", "a8",
            "51", "a3", "40", "8f", "92", "9d", "38", "f5", "bc", "b6", "da", "21", "10", "ff", "f3", "d2",
            "cd", "0c", "13", "ec", "5f", "97", "44", "17", "c4", "a7", "7e", "3d", "64", "5d", "19", "73",
            "60", "81", "4f", "dc", "22", "2a", "90", "88", "46", "ee", "b8", "14", "de", "5e", "0b", "db",
            "e0", "32", "3a", "0a", "49", "06", "24", "5c", "c2", "d3", "ac", "62", "91", "95", "e4", "79",
            "e7", "c8", "37", "6d", "8d", "d5", "4e", "a9", "6c", "56", "f4", "ea", "65", "7a", "ae", "08",
            "ba", "78", "25", "2e", "1c", "a6", "b4", "c6", "e8", "dd", "74", "1f", "4b", "bd", "8b", "8a",
            "70", "3e", "b5", "66", "48", "03", "f6", "0e", "61", "35", "57", "b9", "86", "c1", "1d", "9e",
            "e1", "f8", "98", "11", "69", "d9", "8e", "94", "9b", "1e", "87", "e9", "ce", "55", "28", "df",
            "8c", "a1", "89", "0d", "bf", "e6", "42", "68", "41", "99", "2d", "0f", "b0", "54", "bb", "16"
        );


        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $parts = str_split(str_pad((string)$matrice[$i][$j], 2, "0", STR_PAD_LEFT), 1);
                $index = base_convert($parts[0], 16, 10) * 16 + base_convert($parts[1], 16, 10);
                $matrice[$i][$j] = $sBox[$index];
            }
        }
    } else {
        echo "Inserisci una parola da 16 byte.";
    }

    // Shift Rows
    for ($i = 1; $i < 4; $i++) {
        shiftRows($matrice[$i], $i);
    }

    // Mix Columns
    $polinomi = [[], [], [], []]; // t(x), u(x), v(x), w(x)
    $cx = [3, 1, 1, 2]; // 3x^3 + x^2 + x + 2
    $nx = [1, 0, 0, 0, 1]; // x^4 + 1

    for ($i = 0; $i < 4; $i++) {
        for ($j = 0; $j < 4; $j++) {
            $polinomi[$i][$j] = base_convert($matrice[$j][$i], 16, 10);
        }
    }

    for ($i = 0; $i < 4; $i++) {
        $polinomi[$i] = moltiplicaPolinomi($polinomi[$i], $cx);
    }

    for ($i = 0; $i < 4; $i++) {
        $polinomi[$i] = divisionePolinomiale($polinomi[$i], $nx);
    }

    // AddRoundKey
    for ($i = 0; $i < 4; $i++) {
        for ($j = 0; $j < 7; $j++) {
            $matrice[$j][$i] = base_convert($polinomi[$i][$j], 10, 16);
        }
    }

    unset($matrice[0]);
    unset($matrice[1]);
    unset($matrice[2]);

    $matrice = array_values($matrice);

    $key = [[0xcd, 0x92, 0xe1, 0x8f], [0x55, 0x29, 0x66, 0x20], [0x91, 0x83, 0xdf, 0x76], [0xef, 0xf1, 0x0a, 0x54]];

    for ($i = 0; $i < 4; $i++) {
        for ($j = 0; $j < 4; $j++) {
            $matrice[$i][$j] = dechex(hexdec($matrice[$i][$j]) ^ $key[$i][$j]);
        }
    }

    echo "<br>The encrypt string is: ";
    foreach ($matrice as $index) {
        foreach ($index as $value) {
            echo strtoupper($value) . " ";
        }
    }
}
?>

<body bgcolor="lightgrey">
    <form action="<?php $_SERVER['PHP_SELF'] ?>" method="post">
        <label for="chars">Inserisci una stringa da 16 byte</label>
        <input type="text" name="chars" />
        <input type="submit" value="Invia">
    </form>
</body>