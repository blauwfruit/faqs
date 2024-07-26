<?php
/**
*   FaqFeedbackModel
*
*   Do not copy, modify or distribute this document in any form.
*
*   @author     Matthijs <matthijs@blauwfruit.nl>
*   @copyright  Copyright (c) 2013-2024 blauwfruit (https://blauwfruit.nl)
*   @license    Proprietary Software
*
*/

class FaqFeedbackModel extends ObjectModel
{
    public $id_faq_feedback;
    public $id_faq_question;
    public $is_useful;
    public $ip_address;
    public $date_add;

    public static $definition = [
        'table' => 'faq_feedback',
        'primary' => 'id_faq_feedback',
        'fields' => [
            'id_faq_question' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'is_useful' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'ip_address' => ['type' => self::TYPE_STRING, 'validate' => 'isIp2Long', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];
}
