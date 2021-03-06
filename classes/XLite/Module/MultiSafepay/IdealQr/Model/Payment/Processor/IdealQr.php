<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2017 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace XLite\Module\MultiSafepay\IdealQr\Model\Payment\Processor;

use XLite\Core\Converter;
use XLite\Model\Order;
use XLite\Model\Payment\Method;
use XLite\Module\MultiSafepay\Connect\Model\Payment\Processor\Connect;

class IdealQr extends Connect
{
    /**
     * {@inheritDoc}
     */
    public function getSettingsWidget()
    {
        return 'modules/MultiSafepay/IdealQr/config.twig';
    }

    /**
     * {@inheritDoc}
     */
    public function isConfigured(Method $method)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormURL()
    {
        return Converter::buildURL('idealqr', 'transaction');
    }
    /**
     * {@inheritDoc}
     */
    public function getIconPath(Order $order = null, Method $method = null)
    {
        $processor = new Connect();
        $processor->gateway = 'IdealQr';
        $processor->icon = 'msp_idealqr.png';
        return $processor->getIconPath($order, $method);
    }
}
