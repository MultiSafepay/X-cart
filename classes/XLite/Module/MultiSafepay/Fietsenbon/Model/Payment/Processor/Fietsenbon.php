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

namespace XLite\Module\MultiSafepay\Fietsenbon\Model\Payment\Processor;

use XLite\Core\Converter;
use XLite\Model\Order;
use XLite\Model\Payment\Method;
use XLite\Module\MultiSafepay\Connect\Model\Payment\Processor\Connect;

class Fietsenbon extends Connect
{
    /**
     * {@inheritDoc}
     */
    public function getSettingsWidget()
    {
        return 'modules/MultiSafepay/Fietsenbon/config.twig';
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
        return Converter::buildURL('Fietsenbon', 'transaction');
    }

    /**
     * {@inheritDoc}
     */
    public function getIconPath(Order $order = null, Method $method = null)
    {
        $processor = new Connect();
        $processor->gateway = 'Fietsenbon';
        $processor->icon = 'msp_fietsenbon.png';
        return $processor->getIconPath($order, $method);
    }
}
