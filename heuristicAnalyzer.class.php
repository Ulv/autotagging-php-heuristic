<?php

/**
 * эвристический анализ текста на предмет выявления тэгов
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
    protected $tags = array();
    protected $text = "";

    function __construct($tags = [], $text = "")
    {
        $this->setTags($tags);
        $this->setText($text);
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        if (is_array($tags) && !empty($tags)) {
            $this->tags = $tags;
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
            $this->text = $text;
        }
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }


} 