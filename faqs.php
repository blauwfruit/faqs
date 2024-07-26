<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class faqs extends Module
{
    protected $config_form = false;

    const FEEDBACK_SESSION_TIME_FRAME = 3600;
    const MAX_FEEDBACK_PER_SESSION = 5;

    public function __construct()
    {
        $this->name = 'faqs';
        $this->tab = 'content_management';
        $this->version = '1.0.0';
        $this->author = 'blauwfruit';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('FAQS: Frequently Asked Questions with Structured Data');
        $this->description = $this->l('Add frequently asked questions to pages like product, category or CMS. They frequently asked questions will have structured data in them.');

        $this->confirmUninstall = $this->l('Are you sure to delete this module? All frequently asked questions will be deleted.');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];

        require_once 'classes/FAQModel.php';
        require_once 'classes/PageFinder.php';
        require_once 'classes/FaqFeedbackModel.php';
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');
        include(dirname(__FILE__).'/sql/seeder.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayHome') &&
            $this->registerHook('displayCmsFooter') &&
            $this->registerHook('displayCategoryFooter') &&
            $this->installTab('AdminCatalog', 'AdminFaq', 'FAQ');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall() &&
            $this->uninstallTab('AdminFaq');
    }

    private function installTab($parent, $className, $name)
    {
        $tab = new Tab();
        $tab->id_parent = (int)Tab::getIdFromClassName($parent);
        $tab->class_name = $className;
        $tab->module = $this->name;
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }
        return $tab->add();
    }

    private function uninstallTab($className)
    {
        $id_tab = (int) Tab::getIdFromClassName($className);
        
        if ($id_tab) {
            $tab = new Tab($id_tab);
            
            return $tab->delete();
        }

        throw new Exception("Cannot uninstall", 1);
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookHeader()
    {
        if (!in_array($this->context->controller->php_self, ['product', 'category', 'cms', 'index'])) {
            return;
        }

        $this->context->controller->addCSS($this->_path.'views/css/front.css');
        $this->context->controller->addJS($this->_path.'views/js/front.js');

        $faqs = [];

        if ($this->context->controller->php_self == 'product') {
            $faqs = FAQModel::list('Product', Tools::getValue('id_product'), $this->context->shop->id, $this->context->language->id);
        }

        if ($this->context->controller->php_self == 'category') {
            $faqs = FAQModel::list('Category', Tools::getValue('id_category'), $this->context->shop->id, $this->context->language->id);
        }

        if ($this->context->controller->php_self == 'cms') {
            $faqs = FAQModel::list('Cms', 0, $this->context->shop->id, $this->context->language->id);
        }

        if ($this->context->controller->php_self == 'index') {
            $faqs = FAQModel::list('Home', 0, $this->context->shop->id, $this->context->language->id);
        }

        if (!$faqs) {
            return;
        }

        $mainEntities = [];
        if (is_array($faqs) && count($faqs)) {
            foreach ($faqs as $key => &$faq) {
                $acceptedAnswer = new stdClass();
                $acceptedAnswer->{"@type"} = "Answer";
                $acceptedAnswer->{"text"} = strip_tags($faq['answer']);

                $question = new stdClass();
                $question->{"@type"} = "Question";
                $question->{"name"} = $faq['question'];
                $question->{"acceptedAnswer"} = $acceptedAnswer;

                $mainEntities[$key] = $question;
            }
        }

        $schema = new stdClass();
        $schema->{"@context"} = "https://schema.org";
        $schema->{"@type"} = "FAQPage";
        $schema->{"mainEntity"} = $mainEntities;

        $this->context->smarty->assign([
            'FAQPage' => json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
        ]);

        return $this->context->smarty->fetch($this->local_path.'views/templates/hook/structured-data.tpl');
    }

    public function populateAndFetchTemplate($faqs, $pageName)
    {
        if (!$faqs) {
            return;
        }

        $array = [
            'faqs' => $faqs,
            'feedback_callback_url' => $this->context->link->getModuleLink(
                $this->name,
                'feedback',
                [
                    'token' => Tools::getToken(false),
                    'ajax' => true
                ]
            ),
            'pageName' => $pageName,
        ];

        Media::addJsDef($array);

        $this->context->smarty->assign($array);

        return $this->context->smarty->fetch($this->local_path.'views/templates/hook/faq.tpl');
    }

    public function hookDisplayFooterProduct($params)
    {
        $faqs = FAQModel::list(
            'Product',
            $params['product']->id,
            $this->context->shop->id,
            $this->context->language->id
        );

        return $this->populateAndFetchTemplate($faqs, $params['product']->name);
    }

    public function hookDisplayHome()
    {
        $faqs = FAQModel::list(
            'Home',
            0,
            $this->context->shop->id,
            $this->context->language->id
        );

        return $this->populateAndFetchTemplate($faqs, Configuration::get('PS_SHOP_NAME'));
    }

    public function hookDisplayCmsFooter($params)
    {
        $faqs = FAQModel::list(
            'Cms',
            $params['cms']->id,
            $this->context->shop->id,
            $this->context->language->id
        );

        return $this->populateAndFetchTemplate($faqs, $params['cms']->meta_title);
    }

    public function hookDisplayCategoryFooter($params)
    {
        $faqs = FAQModel::list(
            'Category',
            $params['category']->id,
            $this->context->shop->id,
            $this->context->language->id
        );

        return $this->populateAndFetchTemplate($faqs, $params['category']->name);
    }
}
