<?php

/**
 * эвристический анализ текста на предмет выявления тэгов
 *
 * Исходные тэги должны быть массивом
 *
 * Алгоритм:
 * - удаляет у тэгов суффиксы/окончания (стемминг Портера)
 * - проверяет наличие в тексте полученных стем (существование)
 * - меряет расстояние Левенштейна между существующим тэгом и
 * словами исходного текста
 *
 * Пример использования:
 * <code>
 * $text = "Старика Ивана Петровича разбудил шум";
 * $tags = ["Иван Петрович", "парень", "сТаРиК"]
 *
 * $analyzer = new heuristicAnalyzer($tags, $text);
 * $found = $analyzer->analyze();
 *
 * var_dump($found);
 * // результат будет: Иван Петрович, сТаРиК
 * </code>
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
     * макс. расстояние Левенштейна между словом в тексте и словом тэга
     * при которм слово считается найденным
     */
    const LEVENSTEIN_MAX_DISTANCE = 4;

    /**
     * массив тэгов
     *
     * @var array
     */
    protected $tags = array();

    /**
     * текст
     *
     * @var string
     */
    protected $text = "";

    /**
     * экземпляр класса стеммера Портера
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
     * анализ текста на наличие заданных тэгов
     *
     * Возвращает массив с ключами массива $this->tags, т.е. если
     * $this->tags = ['Вася', 'Петя', ...] и в тексте найден только Петя
     * рез. массив будет [1]
     *
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
            /*
             * превичная фильрация массива тэгов
             */
            $tagArray = explode(" ", $tag);
            $tagArray = array_filter($tagArray);

            /*
             * проверка наличия стем тэгов в тексте
             */
            $stems = $this->stemTag($tagArray);
            if (!$this->testTagsExist($this->getText(), $stems)) {
                continue;
            }

            /*
             * проверка - расстояние Левенштейна (<= заданному)
             */
            if ($this->testDistance($tagArray)) {
                $foundTags[] = $key;
            }
        }

        return $foundTags;
    }

    /**
     * стемминг слов тэга, возвращает рез. массив
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
     * измеряем расстояние Левенштейна между каждым словом текста и
     * найденным тэгом.
     *
     * Стоимость замены в 2 раза выше стоимости вставки и удаления
     *
     * @param $tagArray
     * @return bool
     */
    private function testDistance($tagArray)
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
                if ($distance <= self::LEVENSTEIN_MAX_DISTANCE) {
                    $result[$tag] = $distance;
                }
            }
        }

        return !empty($result);
    }
} 