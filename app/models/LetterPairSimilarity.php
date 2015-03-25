<?php
/**
 * LetterPairSimilarity algorithm implementation in PHP
 * @author Igal Alkon
 * @link http://www.catalysoft.com/articles/StrikeAMatch.html
 * friom http://stackoverflow.com/questions/653157/a-better-similarity-ranking-algorithm-for-variable-length-strings
 */
class LetterPairSimilarity
{
    /**
     * @param $str
     * @return mixed
     */
    private static function wordLetterPairs($str)
    {
        $allPairs = array();

        // Tokenize the string and put the tokens/words into an array

        $words = explode(' ', $str);

        // For each word
        for ($w = 0; $w < count($words); $w++)
        {
            // Find the pairs of characters
            $pairsInWord = LetterPairSimilarity::letterPairs($words[$w]);

            for ($p = 0; $p < count($pairsInWord); $p++)
            {
                $allPairs[] = $pairsInWord[$p];
            }
        }

        return $allPairs;
    }

    /**
     * @param $str
     * @return array
     */
    private static function letterPairs($str)
    {
        $numPairs = mb_strlen($str)-1;
        $pairs = array();

        for ($i = 0; $i < $numPairs; $i++)
        {
            $pairs[$i] = mb_substr($str,$i,2);
        }

        return $pairs;
    }

    /**
     * @param $str1
     * @param $str2
     * @return float
     */
    public static function compareStrings($str1, $str2)
    {
        $pairs1 = LetterPairSimilarity::wordLetterPairs(strtoupper($str1));
        $pairs2 = LetterPairSimilarity::wordLetterPairs(strtoupper($str2));

        $intersection = 0;

        $union = count($pairs1) + count($pairs2);

        for ($i=0; $i < count($pairs1); $i++)
        {
            $pair1 = $pairs1[$i];

            $pairs2 = array_values($pairs2);
            for($j = 0; $j < count($pairs2); $j++)
            {
                $pair2 = $pairs2[$j];
                if ($pair1 === $pair2)
                {
                    $intersection++;
                    unset($pairs2[$j]);
                    break;
                }
            }
        }

        return (2.0*$intersection)/$union;
    }
}
