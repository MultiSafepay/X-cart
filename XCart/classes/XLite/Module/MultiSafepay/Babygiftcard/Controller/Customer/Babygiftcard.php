<?php
// vim: set ts=4 sw=4 sts=4 et:

namespace XLite\Module\MultiSafepay\Babygiftcard\Controller\Customer;

class Babygiftcard extends \XLite\Controller\Customer\ACustomer
{
    /**
     *
     * @return void
     */
    protected function doActionTransaction()
    {
        try {

            $processor = new \XLite\Module\MultiSafepay\Babygiftcard\Model\Payment\Processor\Babygiftcard();

            $processor->doTransactionRequest(
                \XLite\Core\Request::getInstance()->iid,
                \XLite\Core\Request::getInstance()->transid
            );

        } catch (\Exception $e) {

            \XLite\Core\TopMessage::addError(
                static::t('An error occured processing your transaction request, please try again using another payment method.')
            );

            $this->setReturnURL('checkout');
        }
    }
}