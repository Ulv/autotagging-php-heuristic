<?php

/**
 * эвристический анализ текста на предмет выявления тэгов
 *
 * Алгоритм:
 * - удаляет у тэгов суффиксы/окончания (Стемминг Портера)
 * - проверяет наличие в тексте полученных стем (существование)
 * - меряет расстояние Левенштейна между существующим тэгом и
 * словами исходного текста
 *
 * PHP version 5
 *
 * @category
 * @package
 * @author   Vladimir Chmil <vladimir.chmil@gmail.com>
 * @license
 * @link
 */
class heuristicAnalyzer
{
    /**
     * мин. расстояние Левенштейна при котором тэг считается найденным
     */
    const LEVENSTEIN_DISTANCE = 4;

    /**
     * @var array
     */
    protected $tags = array();
    /**
     * @var string
     */
    protected $text = "";
    /**
     * @var
     */
    protected $stemmer;

    /**
     * @param array $tags
     * @param string $text
     */
    function __construct($tags = [], $text = "")
    {
        mb_internal_encoding('UTF-8');

        $this->setTags($tags);
        $this->setText($text);
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        if (is_array($tags) && !empty($tags)) {
            $this->tags = array_map('mb_strtolower', $tags);
            $this->tags = array_filter($this->tags);
        }
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        if (is_string($text) && !empty($text)) {
            $this->text = mb_strtolower($text);
        }
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return bool
     */
    private function validate()
    {
        return !empty($this->tags) && !empty($this->text);
    }

    /**
     * @return bool|array массив ключей массива найденных тэга(ов) или false
     */
    public function analyze()
    {
        if (!$this->validate()) {
            return false;
        }

        $this->stemmer = new PorterStemmer();

        $foundTags = array();
        foreach ($this->tags as $key => $tag) {
            $tagArray = explode(" ", $tag);
            $tagArray = array_filter($tagArray);

            $stems = $this->stemTag($tagArray);
            if (!$this->testTagsExist($this->getText(), $stems)) {
                continue;
            }

            if ($this->testLevenstein($tagArray)) {
                $foundTags[] = $key;
            }
        }

        return $foundTags;
    }

    /**
     * "стемит" слова тэга, возвращает рез. массив
     *
     * @param $tag
     */
    private function stemTag($tagArray)
    {
        return array_map(function ($tag) {
                return $this->stemmer->stem($tag);
            },
            $tagArray
        );
    }

    /**
     * проверяет наличие _всех_ тэгов в исх. тексте
     *
     * @param $haystack
     * @param array $needles
     * @param int $offset
     * @return bool|mixed
     */
    private function testTagsExist($haystack, $needles = array(), $offset = 0)
    {
        $counter = 0;
        foreach ($needles as $needle) {
            $res = strpos($haystack, $needle, $offset);
            if ($res !== false) {
                $counter++;
            }
        }

        return $counter == count($needles);
    }

    /**
     * @param $tagArray
     * @return bool
     */
    private function testLevenstein($tagArray)
    {
        /*
         * разбиваем исходный текст на слова
         */
        $words = explode(" ", $this->getText());
        $words = array_unique($words);
        $words = array_filter($words, function ($word) {
                return strlen($word) > 3;
            }
        );

        $result = array();
        foreach ($words as $word) {
            foreach ($tagArray as $tag) {
                $distance = levenshtein($word, $tag, 1, 2, 1);
                if ($distance <= self::LEVENSTEIN_DISTANCE) {
                    $result[$tag] = $distance;
                }
            }
        }

        return !empty($result);
    }
} 