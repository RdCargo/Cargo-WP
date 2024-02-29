<?php


namespace CSLFW\Includes\CargoAPI;


class CSLFW_Order
{
    public string|null $shippingMethod;

    public function __construct(public $order)
    {
        $this->setShippingMethod();
    }

    public function getMeta($key)
    {
        return $this->order->get_meta($key, true);
    }

    public function updateMeta($key, $value)
    {
        return $this->order->update_meta_data($key, $value);
    }

    public function save()
    {
        $this->order->save();

        return $this;
    }

    public function setShippingMethod()
    {
        $allowForAllShippingMethods = get_option('cslfw_shipping_methods_all');

        if ($shippingMethod = $this->getMeta('cslfw_shipping_method')) {
            $this->shippingMethod = $shippingMethod;
        } else {
            $shippingMethodObject = @array_shift($this->order->get_shipping_methods());

            $shippingMethod = $shippingMethodObject
                ? $shippingMethodObject['method_id']
                : null;

            $this->shippingMethod = !$shippingMethod && $allowForAllShippingMethods ? 'cargo-express' : $shippingMethod;
        }
    }

    public function getShippingMethod()
    {
        return $this->shippingMethod;
    }
}
