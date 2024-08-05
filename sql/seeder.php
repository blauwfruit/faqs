<?php

$shopId = (int) Context::getContext()->shop->id;
$langId = (int) Context::getContext()->language->id;

$productId = (int) Db::getInstance()->getValue('
    SELECT p.`id_product`
    FROM `' . _DB_PREFIX_ . 'product` p
    JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON p.`id_product` = ps.`id_product`
    WHERE p.`active` = 1 AND ps.`id_shop` = ' . $shopId . '
    ORDER BY p.`id_product` ASC
');

if (!$productId) {
    $productId = 0;
}

$questions = [
    [
        'object_type' => 'Product',
        'object_id' => $productId,
        'question' => Module::getInstanceByName('faqs')->l('How to use product X?', 'seeder'),
        'answer' => Module::getInstanceByName('faqs')->l('To use product X, follow these steps: ...', 'seeder'),
    ],
    [
        'object_type' => 'Product',
        'object_id' => $productId,
        'question' => Module::getInstanceByName('faqs')->l('What are the features of product Y?', 'seeder'),
        'answer' => Module::getInstanceByName('faqs')->l('Product Y has the following features: ...', 'seeder'),
    ],
    [
        'object_type' => 'Home',
        'object_id' => null,
        'question' => Module::getInstanceByName('faqs')->l('What is your return policy?', 'seeder'),
        'answer' => Module::getInstanceByName('faqs')->l('Our return policy is as follows: ...', 'seeder'),
    ]
];

foreach ($questions as $question) {
    Db::getInstance()->insert('faq_question', [
        'object_type' => pSQL($question['object_type']),
        'object_id' => $question['object_id'] !== null ? (int)$question['object_id'] : null,
        'date_add' => date('Y-m-d H:i:s'),
        'date_upd' => date('Y-m-d H:i:s'),
    ]);

    $faqQuestionId = Db::getInstance()->Insert_ID();

    Db::getInstance()->insert('faq_question_lang', [
        'id_faq_question' => (int)$faqQuestionId,
        'id_shop' => $shopId,
        'id_lang' => $langId,
        'question' => pSQL($question['question']),
        'answer' => pSQL('<p>' . $question['answer'] . '</p>'),
    ]);
}

return true;
