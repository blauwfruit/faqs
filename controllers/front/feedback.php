<?php

class FaqsFeedbackModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        try {
            if (Tools::getToken(false) !== Tools::getValue('token')) {
                throw new Exception('Invalid token');
            }

            if (Tools::isSubmit('id_faq_question') && Tools::isSubmit('is_useful')) {
                $answerId = (int) Tools::getValue('id_faq_question');
                $isUseful = (int) Tools::getValue('is_useful');
                $ipAddress = ip2long(Tools::getRemoteAddr());

                // Check for rate limiting
                $timeFrame = 3600; // 1 hour in seconds
                $maxRequests = 5; // Max 5 feedback submissions per hour
                $query = new DbQuery();
                $query->select('COUNT(*)');
                $query->from('faq_feedback');
                $query->where('ip_address = "' . pSQL($ipAddress) . '"');
                $query->where('date_add > DATE_SUB(NOW(), INTERVAL ' . (int) FAQS::FEEDBACK_SESSION_TIME_FRAME . ' SECOND)');

                $recentFeedbackCount = (int) Db::getInstance()->getValue($query);

                if ($recentFeedbackCount >= FAQS::MAX_FEEDBACK_PER_SESSION) {
                    goto fake_success;
                }

                // Validate the input
                if ($answerId > 0 && ($isUseful === 0 || $isUseful === 1)) {
                    $feedback = new FaqFeedbackModel();
                    $feedback->id_faq_question = $answerId;
                    $feedback->is_useful = $isUseful;
                    $feedback->ip_address = $ipAddress;
                    // throw new Exception($ipAddress, 1);
                    

                    if (!$feedback->add()) {
                        throw new Exception('Could not save feedback');
                    }
                } else {
                    throw new Exception('Invalid input');
                }
            } else {
                throw new Exception('Missing parameters');
            }
        } catch (Exception $e) {
            echo Tools::jsonEncode([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
            die;
        }

        fake_success:
        echo Tools::jsonEncode([
            'success' => true,
            'message' => $this->module->l('Thank you for your feedback!', 'feedback')
        ]);
        die;
    }
}
