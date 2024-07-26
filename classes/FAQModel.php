<?php
/**
*   FAQs
*
*   Do not copy, modify or distribute this document in any form.
*
*   @author     Matthijs <matthijs@blauwfruit.nl>
*   @copyright  Copyright (c) 2013-2024 blauwfruit (https://blauwfruit.nl)
*   @license    Proprietary Software
*
*/

class FAQModel extends ObjectModel
{
    public $id_faq_question;
    public $date_add;
    public $date_upd;
    public $object_type;
    public $object_id;

    /**
     * @var Setable object for clear form representation in the backoffice
     **/
    public $object;
    public $link;

    /**
     * @var Lang fields
     **/
    public $question;
    public $answer;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'faq_question',
        'primary' => 'id_faq_question',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => [
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'object_type' => ['type' => self::TYPE_STRING],
            'object_id' => ['type' => self::TYPE_INT],
            'question' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'answer' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);

        $this->setHumanReadableObject();
    }

    public static function list(
        $object_type = null,
        $object_id = null,
        $id_shop = null,
        $id_lang = null,
        $question = null,
        $answer = null,
        $date_add = null,
        $date_upd = null
    ) {
        $context = Context::getContext();
        if (!$id_shop) {
            $id_shop = $context->shop->id;
        }
        if (!$id_lang) {
            $id_lang = $context->language->id;
        }

        $query = new DbQuery();
        $query->select('
            fq.*,
            fql.question,
            fql.answer,
            ROUND(AVG(ff.is_useful)*100, 1) AS usefulness_score,
            SUM(ff.is_useful) AS usefulness_count,
            COUNT(*) AS vote_count
        ');
        $query->from('faq_question', 'fq');
        $query->innerJoin(
            'faq_question_lang',
            'fql',
            'fq.id_faq_question = fql.id_faq_question AND fql.id_lang = '.(int)$id_lang.' AND (fql.id_shop = '.(int)$id_shop.' OR fql.id_shop = 0)'
        );
        $query->leftJoin(
            'faq_feedback',
            'ff',
            'fq.id_faq_question = ff.id_faq_question AND ff.date_add < DATE_SUB(NOW(), INTERVAL 24 HOUR)'
        );
        
        if ($question) {
            $query->where('fql.question LIKE "%' . pSQL($question) . '%"');
        }

        if ($answer) {
            $query->where('fql.answer LIKE "%' . pSQL($answer) . '%"');
        }

        if ($object_type) {
            $query->where('fq.object_type = "' . pSQL($object_type) . '"');
        }

        if ($object_id) {
            $query->where('fq.object_id = ' . (int)$object_id);
        }

        if ($date_add) {
            $query->where('fq.date_add BETWEEN "' . pSQL($date_add) . '" AND "' . pSQL($date_add) . '"');
        }

        if ($date_upd) {
            $query->where('fq.date_upd BETWEEN "' . pSQL($date_upd) . '" AND "' . pSQL($date_upd) . '"');
        }

        $query->groupBy('fq.id_faq_question');
        $query->orderBy('usefulness_score DESC');

        return Db::getInstance()->executeS($query);
    }

    public function setHumanReadableObject()
    {
        $context = Context::getContext();

        if ($this->object_type == 'Product') {
            $object = new Product($this->object_id, false, $context->language->id);
            $object->link = $context->link->getProductLink($this->object_id, null, null, null, $this->id_lang, $this->id_shop);
        }

        if ($this->object_type == 'Category') {
            $object = new Category($this->object_id, $context->language->id);
            $object->link = $context->link->getCategoryLink($this->object_id, null, $this->id_lang, null, $this->id_shop);
        }

        if ($this->object_type == 'Cms') {
            $object = new CMS($this->object_id, $context->language->id);
            $object->name = $object->meta_title;
            $object->link = $context->link->getCMSLink($this->object_id, null, null, $this->id_lang, $this->id_shop);
        }

        if ($this->object_type == 'Home') {
            $object = new stdClass();
            $object->name = 'Home page';
            $object->id = 0;
            $object->link = $context->link->getPageLink('index');
        }

        if (!isset($object)) {
            return;
        }

        $this->object = $object;
        $this->object_name = "$object->name ({$this->object_type})";
        $this->link = $object->link;
    }

    public function getHumanReadableObject()
    {
        return $this->object;
    }
}
