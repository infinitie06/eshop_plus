<?php

namespace App\Support;

/**
 * Pure-PHP Arabic glyph shaper + simple RTL bidi reordering for DomPDF.
 *
 * DomPDF (used by barryvdh/laravel-dompdf) has no text-shaping engine, so it
 * prints every Arabic codepoint in its *isolated* form and left-to-right.
 * That is why Arabic comes out as disconnected, reversed letters.
 *
 * This class converts an Arabic string into the correct Unicode presentation
 * forms (initial / medial / final / isolated — the U+FExx range that the
 * "DejaVu Sans" font bundled with DomPDF already contains) and reorders it for
 * right-to-left, so DomPDF only has to print already-shaped, ordered glyphs.
 *
 * No Composer dependency required.
 *
 * Note: combining harakat (tashkeel) are stripped, which is the safe choice for
 * names / addresses rendered through DomPDF.
 */
class ArabicReshaper
{
    /**
     * base codepoint => [isolated, final, initial, medial, joinsNext]
     * For letters that never connect to the following letter,
     * initial == isolated and medial == final, joinsNext == false.
     */
    private static $forms = [
        0x0621 => [0xFE80, 0xFE80, 0xFE80, 0xFE80, false], // hamza
        0x0622 => [0xFE81, 0xFE82, 0xFE81, 0xFE82, false], // alef madda
        0x0623 => [0xFE83, 0xFE84, 0xFE83, 0xFE84, false], // alef hamza above
        0x0624 => [0xFE85, 0xFE86, 0xFE85, 0xFE86, false], // waw hamza
        0x0625 => [0xFE87, 0xFE88, 0xFE87, 0xFE88, false], // alef hamza below
        0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C, true ], // yeh hamza
        0x0627 => [0xFE8D, 0xFE8E, 0xFE8D, 0xFE8E, false], // alef
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92, true ], // beh
        0x0629 => [0xFE93, 0xFE94, 0xFE93, 0xFE94, false], // teh marbuta
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98, true ], // teh
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C, true ], // theh
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0, true ], // jeem
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4, true ], // hah
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8, true ], // khah
        0x062F => [0xFEA9, 0xFEAA, 0xFEA9, 0xFEAA, false], // dal
        0x0630 => [0xFEAB, 0xFEAC, 0xFEAB, 0xFEAC, false], // thal
        0x0631 => [0xFEAD, 0xFEAE, 0xFEAD, 0xFEAE, false], // reh
        0x0632 => [0xFEAF, 0xFEB0, 0xFEAF, 0xFEB0, false], // zain
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4, true ], // seen
        0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8, true ], // sheen
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC, true ], // sad
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0, true ], // dad
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4, true ], // tah
        0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8, true ], // zah
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC, true ], // ain
        0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0, true ], // ghain
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4, true ], // feh
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8, true ], // qaf
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC, true ], // kaf
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0, true ], // lam
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4, true ], // meem
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8, true ], // noon
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC, true ], // heh
        0x0648 => [0xFEED, 0xFEEE, 0xFEED, 0xFEEE, false], // waw
        0x0649 => [0xFEEF, 0xFEF0, 0xFEEF, 0xFEF0, false], // alef maksura
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4, true ], // yeh
    ];

    /** lam (U+0644) + alef variant => [isolated ligature, final ligature] */
    private static $ligatures = [
        0x0622 => [0xFEF5, 0xFEF6], // lam-alef madda
        0x0623 => [0xFEF7, 0xFEF8], // lam-alef hamza above
        0x0625 => [0xFEF9, 0xFEFA], // lam-alef hamza below
        0x0627 => [0xFEFB, 0xFEFC], // lam-alef
    ];

    /** Combining harakat / marks that are dropped before shaping. */
    private static function isMark($cp)
    {
        return ($cp >= 0x0610 && $cp <= 0x061A)
            || ($cp >= 0x064B && $cp <= 0x065F)
            || $cp === 0x0670
            || ($cp >= 0x06D6 && $cp <= 0x06DC)
            || ($cp >= 0x06DF && $cp <= 0x06E8)
            || ($cp >= 0x06EA && $cp <= 0x06ED);
    }

    /**
     * Shape an arbitrary string. Strings with no Arabic are returned untouched.
     */
    public static function shape($text)
    {
        if ($text === null || $text === '') {
            return $text;
        }

        // No Arabic block characters -> nothing to do (keeps English untouched).
        if (!preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $text)) {
            return $text;
        }

        $lines = preg_split('/(\r\n|\n|\r)/', $text, -1);
        foreach ($lines as $k => $line) {
            $lines[$k] = self::shapeLine($line);
        }

        return implode("\n", $lines);
    }

    private static function shapeLine($line)
    {
        // 1) Split into codepoints, dropping combining marks.
        $chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
        $cps = [];
        foreach ($chars as $ch) {
            $cp = self::toCp($ch);
            if (self::isMark($cp)) {
                continue;
            }
            $cps[] = $cp;
        }

        $n = count($cps);
        $glyphs = [];

        for ($i = 0; $i < $n; $i++) {
            $cp = $cps[$i];

            // Non-Arabic character -> pass through unchanged.
            if (!isset(self::$forms[$cp])) {
                $glyphs[] = $cp;
                continue;
            }

            // Does the previous letter connect forward to this one?
            $prevConnects = false;
            if ($i > 0) {
                $p = $cps[$i - 1];
                if (isset(self::$forms[$p]) && self::$forms[$p][4]) {
                    $prevConnects = true;
                }
            }

            // lam + alef ligature.
            if ($cp === 0x0644 && $i + 1 < $n && isset(self::$ligatures[$cps[$i + 1]])) {
                $lig = self::$ligatures[$cps[$i + 1]];
                $glyphs[] = $prevConnects ? $lig[1] : $lig[0];
                $i++; // consume the alef
                continue;
            }

            $nextIsLetter = ($i + 1 < $n) && isset(self::$forms[$cps[$i + 1]]);
            $joinsNext = self::$forms[$cp][4] && $nextIsLetter;

            if ($prevConnects && $joinsNext) {
                $glyphs[] = self::$forms[$cp][3]; // medial
            } elseif ($prevConnects && !$joinsNext) {
                $glyphs[] = self::$forms[$cp][1]; // final
            } elseif (!$prevConnects && $joinsNext) {
                $glyphs[] = self::$forms[$cp][2]; // initial
            } else {
                $glyphs[] = self::$forms[$cp][0]; // isolated
            }
        }

        // 2) Reverse the whole run for RTL...
        $glyphs = array_reverse($glyphs);
        $str = '';
        foreach ($glyphs as $cp) {
            $str .= self::fromCp($cp);
        }

        // ...then put embedded Latin / digit runs back into left-to-right order.
        $str = preg_replace_callback(
            '/[A-Za-z0-9][A-Za-z0-9 \+\-_.,:;\/@#()%&\[\]\'"!?]*/u',
            function ($m) {
                $parts = preg_split('//u', $m[0], -1, PREG_SPLIT_NO_EMPTY);
                return implode('', array_reverse($parts));
            },
            $str
        );

        return $str;
    }

    /** UTF-8 character -> Unicode codepoint. */
    private static function toCp($ch)
    {
        $v = unpack('N', mb_convert_encoding($ch, 'UTF-32BE', 'UTF-8'));
        return $v[1];
    }

    /** Unicode codepoint -> UTF-8 character. */
    private static function fromCp($cp)
    {
        return mb_convert_encoding(pack('N', $cp), 'UTF-8', 'UTF-32BE');
    }
}
