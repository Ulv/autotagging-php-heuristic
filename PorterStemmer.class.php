<?php

/**
 * стеммер Портера
 *
 * описание алгоритма - http://tartarus.org/~martin/PorterStemmer/def.txt
 *
 * PHP version 5
 *
 * @category
 * @package
 * @author   Vladimir Chmil <vladimir.chmil@gmail.com>
 * @license
 * @link
 */
class PorterStemmer
{
    const VOWEL = '/аеиоуыэюя/u';
    const PERFECTIVEGROUND = '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/u';
    const REFLEXIVE = '/(с[яь])$/u';
    const ADJECTIVE = '/(ее|ие|ые|ое|ими|ыми|ей|ий|их|ый|ой|ем|им|ым|ом|ому|его|ого|еых|ую|юю|ая|яя|ою|ею)$/u';
    const PARTICIPLE = '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/u';
    const VERB = '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/u';
    const NOUN = '/(а|ев|ов|ович|овна|ин|кин|ий|ие|ье|е|иями|ями|у|ами|еи|ии|и|ией|ей|ой|ий|й|и|ы|ь|ию|ью|ю|ия|ья|я)$/u';
    const RVRE = '/^(.*?[аеиоуыэюя])(.*)$/u';
    const DERIVATIONAL = '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/u';

    private $useCache = true;
    private static $stemCache = array();

    public function __construct()
    {
        mb_internal_encoding('UTF-8');
    }

    /**
     * стемминг
     */
    public function stem($word)
    {
        $word = mb_strtolower($word);
        $word = str_replace('ё', 'е', $word);

        // Check against cache of stemmed words
        if ($this->useCache && isset($this->stemCache[$word])) {
            return $this->stemCache[$word];
        }

        $stem = $word;
        if (preg_match(self::RVRE, $word, $p)
            && isset($p[2]) || empty($p[2])
        ) {
            $start = $p[1];
            $RV = $p[2];

            $RV = $this->_step1($RV);
            $RV = $this->_step2($RV);
            $RV = $this->_step3($RV);
            $RV = $this->_step4($RV);

            $stem = $start . $RV;
        }

        $this->cache($word, $stem);

        return $stem;
    }


    private function s(&$s, $re, $to)
    {
        $orig = $s;
        $s = preg_replace($re, $to, $s);
        return $orig !== $s;
    }

    private function m($s, $re)
    {
        return preg_match($re, $s);
    }

    /**
     * @return mixed
     */
    private function _step1($RV)
    {
        if (!$this->s($RV, self::PERFECTIVEGROUND, '')) {
            $this->s($RV, self::REFLEXIVE, '');
            if ($this->s($RV, self::ADJECTIVE, '')) {
                $this->s($RV, self::PARTICIPLE, '');
                return $RV;
            } else {
                if (!$this->s($RV, self::VERB, '')) {
                    $this->s($RV, self::NOUN, '');
                    return $RV;
                }
                return $RV;
            }
        }
        return $RV;
    }

    /**
     * @return mixed
     */
    private function _step2($RV)
    {
        $this->s($RV, '/и$/', '');
        return $RV;
    }

    /**
     * @param $RV
     * @return mixed
     */
    private function _step3($RV)
    {
        if ($this->m($RV, self::DERIVATIONAL)) {
            $this->s($RV, '/ость?$/', '');
            return $RV;
        }
        return $RV;
    }

    /**
     * @return mixed
     */
    private function _step4($RV)
    {
        if (!$this->s($RV, '/ь$/', '')) {
            $this->s($RV, '/ейше?/', '');
            $this->s($RV, '/нн$/', 'н');
            return $RV;
        }
        return $RV;
    }

    /**
     * @param $word
     * @param $stem
     */
    private function cache($word, $stem)
    {
        if ($this->useCache) {
            $this->stemCache[$word] = $stem;
        }
    }
}