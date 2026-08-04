<?php

class QRCodeGenerator {
    private $version;
    private $size;
    private $modules;
    private $isDark;
    private $ecLevel;

    const EC_M = 1;
    const EC_L = 0;

    private static $ALIGNMENT_PATTERNS = [
        1 => [], 2 => [6,18], 3 => [6,22], 4 => [6,26], 5 => [6,30],
        6 => [6,34], 7 => [6,22,38], 8 => [6,24,42], 9 => [6,26,46],
        10 => [6,28,50], 11 => [6,30,54], 12 => [6,32,58], 13 => [6,34,62],
    ];

    private static $EC_CODEWORDS = [
        1 => [0,10,7,13,17], 2 => [0,16,10,22,18], 3 => [0,26,15,18,26],
        4 => [0,18,20,26,18], 5 => [0,24,26,18,26], 6 => [0,16,18,24,30],
        7 => [0,18,20,28,24], 8 => [0,22,24,26,30], 9 => [0,22,30,28,28],
        10 => [0,26,22,28,30],
    ];

    private static $NUM_EC_BLOCKS = [
        1 => [0,1,1,1,1], 2 => [0,1,1,1,1], 3 => [0,1,1,2,2],
        4 => [0,2,1,2,2], 5 => [0,2,1,4,2], 6 => [0,4,2,4,4],
        7 => [0,4,2,4,4], 8 => [0,2,2,6,4], 9 => [0,3,2,6,4],
        10 => [0,4,2,6,6],
    ];

    private static $TOTAL_CODEWORDS = [
        1=>26, 2=>44, 3=>70, 4=>100, 5=>134, 6=>172, 7=>196, 8=>242, 9=>292, 10=>346,
    ];

    private static $DATA_CODEWORDS = [
        1=>16, 2=>28, 3=>44, 4=>64, 5=>86, 6=>108, 7=>124, 8=>154, 9=>182, 10=>216,
    ];

    private static $GF_EXP;
    private static $GF_LOG;

    public function __construct() {
        if (self::$GF_EXP === null) {
            self::initGaloisField();
        }
    }

    private static function initGaloisField() {
        self::$GF_EXP = array_fill(0, 256, 0);
        self::$GF_LOG = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$GF_EXP[$i] = $x;
            self::$GF_LOG[$x] = $i;
            $x = ($x << 1) ^ ($x & 0x80 ? 0x11D : 0);
        }
        self::$GF_EXP[255] = self::$GF_EXP[0];
    }

    private static function gfMul($a, $b) {
        if ($a === 0 || $b === 0) return 0;
        return self::$GF_EXP[(self::$GF_LOG[$a] + self::$GF_LOG[$b]) % 255];
    }

    private static function rsEncode($data, $ecLen) {
        $gen = [1];
        for ($i = 0; $i < $ecLen; $i++) {
            $newGen = array_fill(0, count($gen) + 1, 0);
            for ($j = 0; $j < count($gen); $j++) {
                $newGen[$j] ^= $gen[$j];
                $newGen[$j + 1] ^= self::gfMul($gen[$j], self::$GF_EXP[$i]);
            }
            $gen = $newGen;
        }

        $result = array_fill(0, $ecLen, 0);
        for ($i = 0; $i < count($data); $i++) {
            $coef = $data[$i] ^ $result[0];
            array_shift($result);
            $result[] = 0;
            for ($j = 0; $j < $ecLen; $j++) {
                $result[$j] ^= self::gfMul($gen[$j + 1], $coef);
            }
        }
        return $result;
    }

    public function generate($text, $ecLevel = self::EC_M) {
        $this->ecLevel = $ecLevel;
        $bytes = array_values(unpack('C*', $text));
        $this->version = $this->chooseVersion(count($bytes));
        $this->size = $this->version * 4 + 17;

        $data = $this->encodeData($bytes);
        $totalData = self::$DATA_CODEWORDS[$this->version];
        $totalEc = self::$TOTAL_CODEWORDS[$this->version] - $totalData;
        $numBlocks = self::$NUM_EC_BLOCKS[$this->version][$ecLevel + 1];
        $ecPerBlock = intdiv($totalEc, $numBlocks);
        $dataPerBlock = intdiv($totalData, $numBlocks);

        $blocks = [];
        $ecBlocks = [];
        $offset = 0;
        for ($i = 0; $i < $numBlocks; $i++) {
            $blockData = array_slice($data, $offset, $dataPerBlock);
            $offset += $dataPerBlock;
            $blocks[] = $blockData;
            $ecBlocks[] = self::rsEncode($blockData, $ecPerBlock);
        }

        $final = [];
        $maxDataLen = $dataPerBlock;
        for ($i = 0; $i < $maxDataLen; $i++) {
            foreach ($blocks as $block) {
                if (isset($block[$i])) $final[] = $block[$i];
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $block) {
                if (isset($block[$i])) $final[] = $block[$i];
            }
        }

        $bits = '';
        foreach ($final as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        $totalBits = $this->version * 4 + 17;
        $totalBits = $totalBits * $totalBits;
        $reservedCount = $this->countReservedModules();
        $availableDataBits = 0;
        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                if (!$this->isReserved($row, $col)) {
                    $availableDataBits++;
                }
            }
        }

        while (strlen($bits) < $availableDataBits) {
            $bits .= '0000';
        }
        $bits = substr($bits, 0, $availableDataBits);

        $this->modules = array_fill(0, $this->size, array_fill(0, $this->size, null));
        $this->isDark = array_fill(0, $this->size, array_fill(0, $this->size, false));

        $this->placeFinderPatterns();
        $this->placeAlignmentPatterns();
        $this->placeTimingPatterns();
        $this->placeDarkModule();
        $this->reserveFormatArea();

        $this->placeData($bits);

        $bestMask = 0;
        $bestPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $this->applyMask($mask);
            $this->placeFormatInfo($ecLevel, $mask);
            $penalty = $this->calculatePenalty();
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMask = $mask;
            }
            $this->applyMask($mask);
        }

        $this->applyMask($bestMask);
        $this->placeFormatInfo($ecLevel, $bestMask);

        return $this->generatePng($this->isDark, $this->size);
    }

    private function chooseVersion($dataLen) {
        for ($v = 1; $v <= 10; $v++) {
            $cap = self::$DATA_CODEWORDS[$v] - 2;
            if ($dataLen <= $cap) return $v;
        }
        return 10;
    }

    private function encodeData($bytes) {
        $bits = '0100';
        $charCountBits = $this->version <= 9 ? 8 : 16;
        $bits .= str_pad(decbin(count($bytes)), $charCountBits, '0', STR_PAD_LEFT);
        foreach ($bytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }

        $totalBits = self::$DATA_CODEWORDS[$this->version] * 8;
        if (strlen($bits) < $totalBits) {
            $bits .= '11101100';
        }
        if (strlen($bits) < $totalBits) {
            $bits .= '00010001';
        }
        $bits = substr($bits, 0, $totalBits);

        $data = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $data[] = bindec(substr($bits, $i, 8));
        }
        return $data;
    }

    private function isReserved($row, $col) {
        if ($row < 0 || $row >= $this->size || $col < 0 || $col >= $this->size) return false;

        if (($row < 9 && $col < 9) || ($row < 9 && $col >= $this->size - 8) ||
            ($row >= $this->size - 8 && $col < 9)) {
            return true;
        }

        if ($row === 6 || $col === 6) return true;

        if ($this->version >= 2) {
            foreach (self::$ALIGNMENT_PATTERNS[$this->version] as $r) {
                foreach (self::$ALIGNMENT_PATTERNS[$this->version] as $c) {
                    if ($r === 6 && $c === 6) continue;
                    if (abs($row - $r) <= 2 && abs($col - $c) <= 2) return true;
                }
            }
        }

        if ($row === 8 || $col === 8) return true;

        return false;
    }

    private function countReservedModules() {
        $count = 0;
        for ($r = 0; $r < $this->size; $r++) {
            for ($c = 0; $c < $this->size; $c++) {
                if ($this->isReserved($r, $c)) $count++;
            }
        }
        return $count;
    }

    private function placeFinderPatterns() {
        $pattern = [
            [1,1,1,1,1,1,1],
            [1,0,0,0,0,0,1],
            [1,0,1,1,1,0,1],
            [1,0,1,1,1,0,1],
            [1,0,1,1,1,0,1],
            [1,0,0,0,0,0,1],
            [1,1,1,1,1,1,1],
        ];

        $positions = [[0,0],[0,$this->size-7],[$this->size-7,0]];
        foreach ($positions as list($r, $c)) {
            for ($i = 0; $i < 7; $i++) {
                for ($j = 0; $j < 7; $j++) {
                    $this->setModule($r + $i, $c + $j, $pattern[$i][$j] === 1);
                }
            }
        }

        for ($i = 0; $i < 8; $i++) {
            if ($this->inBounds(7, $i)) $this->setModule(7, $i, false);
            if ($this->inBounds($i, 7)) $this->setModule($i, 7, false);
            if ($this->inBounds(7, $this->size - 1 - $i)) $this->setModule(7, $this->size - 1 - $i, false);
            if ($this->inBounds($i, $this->size - 8)) $this->setModule($i, $this->size - 8, false);
            if ($this->inBounds($this->size - 8, $i)) $this->setModule($this->size - 8, $i, false);
            if ($this->inBounds($this->size - 1 - $i, 7)) $this->setModule($this->size - 1 - $i, 7, false);
        }

        for ($i = 0; $i < 7; $i++) {
            if ($this->inBounds($this->size - 7 + $i, 7)) $this->setModule($this->size - 7 + $i, 7, false);
            if ($this->inBounds(7, $this->size - 7 + $i)) $this->setModule(7, $this->size - 7 + $i, false);
        }
    }

    private function placeAlignmentPatterns() {
        if ($this->version < 2) return;
        $positions = self::$ALIGNMENT_PATTERNS[$this->version];

        $pattern = [
            [1,1,1,1,1],
            [1,0,0,0,1],
            [1,0,1,0,1],
            [1,0,0,0,1],
            [1,1,1,1,1],
        ];

        foreach ($positions as $r) {
            foreach ($positions as $c) {
                if (($r <= 8 && $c <= 8) || ($r <= 8 && $c >= $this->size - 9) ||
                    ($r >= $this->size - 9 && $c <= 8)) {
                    continue;
                }
                for ($i = -2; $i <= 2; $i++) {
                    for ($j = -2; $j <= 2; $j++) {
                        $this->setModule($r + $i, $c + $j, $pattern[$i + 2][$j + 2] === 1);
                    }
                }
            }
        }
    }

    private function placeTimingPatterns() {
        for ($i = 8; $i < $this->size - 8; $i++) {
            $dark = ($i % 2 === 0);
            if ($this->modules[6][$i] === null) $this->setModule(6, $i, $dark);
            if ($this->modules[$i][6] === null) $this->setModule($i, 6, $dark);
        }
    }

    private function placeDarkModule() {
        $this->setModule(4 * $this->version + 9, 8, true);
    }

    private function reserveFormatArea() {
        for ($i = 0; $i < 15; $i++) {
            if ($i < 8) {
                $this->modules[8][$i] = 'reserved';
                $this->modules[$this->size - 1 - $i][8] = 'reserved';
            }
            if ($i < 6) {
                $this->modules[$i][8] = 'reserved';
            } else {
                $this->modules[$i + 1][8] = 'reserved';
            }
            $this->modules[8][$this->size - 15 + $i] = 'reserved';
        }
        $this->modules[8][8] = 'reserved';
    }

    private function placeData($bits) {
        $bitIndex = 0;
        $right = $this->size - 1;

        while ($right >= 0) {
            if ($right === 6) $right--;

            $upward = (($this->size - 1 - $right) % 2 === 0);

            for ($cnt = 0; $cnt < $this->size; $cnt++) {
                $row = $upward ? ($this->size - 1 - $cnt) : $cnt;

                for ($dx = 0; $dx < 2; $dx++) {
                    $col = $right - $dx;
                    if ($col < 0) continue;
                    if ($this->modules[$row][$col] !== null) continue;

                    $dark = false;
                    if ($bitIndex < strlen($bits)) {
                        $dark = $bits[$bitIndex] === '1';
                        $bitIndex++;
                    }
                    $this->setModule($row, $col, $dark);
                }
            }

            $right -= 2;
        }
    }

    private function applyMask($mask) {
        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                if ($this->modules[$row][$col] === 'reserved') continue;

                $invert = false;
                switch ($mask) {
                    case 0: $invert = (($row + $col) % 2 === 0); break;
                    case 1: $invert = ($row % 2 === 0); break;
                    case 2: $invert = ($col % 3 === 0); break;
                    case 3: $invert = (($row + $col) % 3 === 0); break;
                    case 4: $invert = ((intdiv($row, 2) + intdiv($col, 3)) % 2 === 0); break;
                    case 5: $invert = (($row * $col) % 2 + ($row * $col) % 3 === 0); break;
                    case 6: $invert = ((($row * $col) % 2 + ($row * $col) % 3) % 2 === 0); break;
                    case 7: $invert = ((($row + $col) % 2 + ($row * $col) % 3) % 2 === 0); break;
                }

                if ($invert) {
                    $this->isDark[$row][$col] = !$this->isDark[$row][$col];
                }
            }
        }
    }

    private function placeFormatInfo($ecLevel, $mask) {
        $ecBits = [self::EC_L => 01, self::EC_M => 00];
        $formatData = ($ecBits[$ecLevel] << 3) | $mask;

        $rem = $formatData;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $formatBits = (($formatData << 10) | $rem) ^ 0x5412;

        $bits = str_pad(decbin($formatBits), 15, '0', STR_PAD_LEFT);

        $coords1 = [
            [8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],
            [7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]
        ];
        $coords2 = [
            [$this->size-1,8],[$this->size-2,8],[$this->size-3,8],[$this->size-4,8],
            [$this->size-5,8],[$this->size-6,8],[$this->size-7,8],[8,$this->size-8],
            [8,$this->size-7],[8,$this->size-6],[8,$this->size-5],[8,$this->size-4],
            [8,$this->size-3],[8,$this->size-2],[8,$this->size-1]
        ];

        for ($i = 0; $i < 15; $i++) {
            $dark = $bits[$i] === '1';
            list($r1, $c1) = $coords1[$i];
            list($r2, $c2) = $coords2[$i];
            $this->isDark[$r1][$c1] = $dark;
            $this->isDark[$r2][$c2] = $dark;
        }
    }

    private function calculatePenalty() {
        $penalty = 0;

        for ($row = 0; $row < $this->size; $row++) {
            $count = 1;
            for ($col = 1; $col < $this->size; $col++) {
                if ($this->isDark[$row][$col] === $this->isDark[$row][$col-1]) {
                    $count++;
                } else {
                    if ($count >= 5) $penalty += $count - 2;
                    $count = 1;
                }
            }
            if ($count >= 5) $penalty += $count - 2;
        }

        for ($col = 0; $col < $this->size; $col++) {
            $count = 1;
            for ($row = 1; $row < $this->size; $row++) {
                if ($this->isDark[$row][$col] === $this->isDark[$row-1][$col]) {
                    $count++;
                } else {
                    if ($count >= 5) $penalty += $count - 2;
                    $count = 1;
                }
            }
            if ($count >= 5) $penalty += $count - 2;
        }

        for ($row = 0; $row < $this->size - 1; $row++) {
            for ($col = 0; $col < $this->size - 1; $col++) {
                $c = $this->isDark[$row][$col];
                if ($c === $this->isDark[$row][$col+1] &&
                    $c === $this->isDark[$row+1][$col] &&
                    $c === $this->isDark[$row+1][$col+1]) {
                    $penalty += 3;
                }
            }
        }

        $darkCount = 0;
        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                if ($this->isDark[$row][$col]) $darkCount++;
            }
        }
        $total = $this->size * $this->size;
        $pct = ($darkCount / $total) * 100;
        $penalty += abs((int)floor($pct / 5) * 5 - 50) / 5 * 10;

        return $penalty;
    }

    private function setModule($row, $col, $dark) {
        if ($row >= 0 && $row < $this->size && $col >= 0 && $col < $this->size) {
            $this->modules[$row][$col] = $dark;
            $this->isDark[$row][$col] = $dark;
        }
    }

    private function inBounds($row, $col) {
        return $row >= 0 && $row < $this->size && $col >= 0 && $col < $this->size;
    }

    private function generatePng($isDark, $moduleCount) {
        $moduleSize = 6;
        $margin = 4;
        $imgSize = ($moduleCount + $margin * 2) * $moduleSize;

        $pixels = [];
        for ($y = 0; $y < $imgSize; $y++) {
            $row = [];
            for ($x = 0; $x < $imgSize; $x++) {
                $mRow = intdiv($y, $moduleSize) - $margin;
                $mCol = intdiv($x, $moduleSize) - $margin;
                if ($mRow >= 0 && $mRow < $moduleCount && $mCol >= 0 && $mCol < $moduleCount) {
                    $row[] = $isDark[$mRow][$mCol] ? 0 : 255;
                } else {
                    $row[] = 255;
                }
            }
            $pixels[] = $row;
        }

        $raw = '';
        for ($y = 0; $y < $imgSize; $y++) {
            $raw .= "\x00";
            for ($x = 0; $x < $imgSize; $x++) {
                $v = $pixels[$y][$x];
                $raw .= chr($v) . chr($v) . chr($v);
            }
        }

        $ihdr = pack('NNccccc', $imgSize, $imgSize, 8, 2, 0, 0, 0);
        $ihdrData = 'IHDR' . $ihdr;

        $compressed = gzcompress($raw);
        $idatData = 'IDAT' . $compressed;

        $iendData = 'IEND';

        $png = "\x89PNG\r\n\x1a\n";
        $png .= $this->makeChunk($ihdrData);
        $png .= $this->makeChunk($idatData);
        $png .= $this->makeChunk($iendData);

        return $png;
    }

    private function makeChunk($data) {
        $len = pack('N', strlen($data) - 4);
        $crc = pack('N', $this->crc32($data));
        return $len . $data . $crc;
    }

    private function crc32($data) {
        $crc = 0xFFFFFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]);
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc >> 1) ^ (0xEDB88320 & (-($crc & 1)));
            }
        }
        return $crc ^ 0xFFFFFFFF;
    }
}
