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

class AdminFaqController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'faq_question';
        $this->className = 'FAQModel';
        $this->lang = true;
        $this->identifier = 'id_faq_question';

        parent::__construct();

        $this->fields_list = [
            'id_faq_question' => [
                'title' => $this->l('ID'),
                'class' => 'fixed-width-xs'
            ],
            'question' => [
                'title' => $this->l('Question'),
                'lang' => true
            ],
            'answer' => [
                'title' => $this->l('Answer'),
                'lang' => true,
                'callback' => 'stripHtmlAnswer',
            ],
            'object_type' => [
                'title' => $this->l('Link'),
                'callback' => 'getPageValue',
                'search' => false,
            ],
            'id_shop' => [
                'title' => $this->l('Shop'),
                'callback' => 'getShop',
                'search' => false,
            ],
            'object_id' => [
                'title' => $this->l('Page ID'),
                'align' => ''
            ],
            'date_add' => [
                'title' => $this->l('Date Add'),
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            ],
        ];

        $this->actions = ['edit', 'delete', 'duplicate'];

        // Adding the shop filter
        $this->_select .= 'id_shop';
        $this->_where .= ' AND b.id_shop = ' . (int)$this->context->shop->id;
    }
    
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJS('https://code.jquery.com/ui/1.12.1/jquery-ui.min.js');
        $this->addCSS('https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        $this->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/back.js');
    }

    public function renderForm()
    {
        $ajaxSearchUrl = $this->context->link->getAdminLink('AdminFaq') . '&ajax=1&action=searchObjects&token=' . Tools::getAdminTokenLite('AdminFaq');

        Media::addJsDef([
            'ajaxSearchUrl' => $ajaxSearchUrl
        ]);

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('FAQ'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Question'),
                    'name' => 'question',
                    'lang' => true,
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Answer'),
                    'name' => 'answer',
                    'lang' => true,
                    'required' => true,
                    'autoload_rte' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Page'),
                    'desc' => $this->l('Search for Product, Category, CMS-page or select the Home-page'),
                    'name' => 'object_name',
                ],
                [
                    'type' => 'hidden',
                    'name' => 'object_type',
                    'required' => true,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'object_id',
                    'required' => true,
                ],
                [
                    'type' => 'hidden',
                    'name' => 'id_shop'
                ]
            ],
            'submit' => [
                'title' => $this->l('Save')
            ]
        ];

        $this->fields_value['id_shop'] = Shop::getContext() == Shop::CONTEXT_ALL ? null : Shop::getContextShopID();

        if (Tools::isSubmit('updatefaq_question') || Tools::isSubmit('addfaq_question')) {
            $this->fields_value['date_upd'] = date('Y-m-d H:i:s');
            if (Tools::isSubmit('addfaq_question')) {
                $this->fields_value['date_add'] = date('Y-m-d H:i:s');
            }
        }

        return parent::renderForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('duplicatefaq_question')) {
            $this->processDuplicate();
        }

        return parent::postProcess();
    }

    public function displayAjax()
    {
        $query = Tools::getValue('term');

        echo PageFinder::search($query);
        die;
    }

    public function stripHtmlAnswer($value, $row)
    {
        $strippedValue = strip_tags($value);

        return (strlen($strippedValue) > 128) ? substr($strippedValue, 0, 128) . '...' : $strippedValue;
    }

    public function processDuplicate()
    {
        if ($id_faq_question = (int) Tools::getValue('id_faq_question')) {
            $faq = new FAQModel($id_faq_question);

            if (Validate::isLoadedObject($faq)) {
                $faqDuplicate = new FAQModel();
                $faqDuplicate->question = $faq->question;
                $faqDuplicate->answer = $faq->answer;
                $faqDuplicate->object_type = $faq->object_type;
                $faqDuplicate->object_id = $faq->object_id;
                $faqDuplicate->date_add = date('Y-m-d H:i:s');
                $faqDuplicate->date_upd = date('Y-m-d H:i:s');
                
                if ($faqDuplicate->add()) {
                    $this->confirmations[] = $this->l('The FAQ has been duplicated.');
                } else {
                    $this->errors[] = $this->l('An error occurred while duplicating the FAQ.');
                }
            } else {
                $this->errors[] = $this->l('The FAQ cannot be found.');
            }
        }
    }

    public function getPageValue($value, $row)
    {
        $faqModel = new FAQModel($row['id_faq_question']);

        if ($faqModel->object_type == 'Home') {
            return sprintf('%s (%s)', $faqModel->object->name, $faqModel->object_type);
        }

        return sprintf('%s (%s #%d)', $faqModel->object->name, $faqModel->object_type, $faqModel->object->id);
    }

    public function getShop($value, $row)
    {
        $shop = new Shop($value);

        return "{$shop->name} ($shop->id)";
    }
}
