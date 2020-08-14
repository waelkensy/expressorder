<?php

namespace Wlks\ExpressOrder\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class Expressorder extends \Magento\Checkout\Controller\Cart\Add implements HttpPostActionInterface
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
    }

    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();
        if (isset($params)) {
            try {
                $product = $this->_initProduct();
                if (!$product) {
                    return $this->goBack();
                }

                $this->cart->addProduct($product, ['qty' => '1']);
                $this->cart->save();

                $this->_eventManager->dispatch(
                    'checkout_cart_add_product_complete',
                    ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
                );

                if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                    if (!$this->cart->getQuote()->getHasError()) {
                        $message = __(
                            'You added %1 to your shopping cart.',
                            $product->getName()
                        );
                        $this->messageManager->addSuccessMessage($message);
                    }
                }

                return $this->goBack(null, $product);
            } catch (\Exception $exception) {
                var_dump($exception->getMessage());
                die;
                $this->messageManager->addExceptionMessage(
                    $exception,
                    __('We can\'t add this item to your shopping cart right now.')
                );
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($exception);

                return $this->goBack();
            }
        }
    }

}
