<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes as ServiceCodes;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes as CoreCodes;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\OrderSelectionManager;

class AddShippingProductOptionsProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var OrderSelectionManager
     */
    private $selectionManager;

    /**
     * @var ShippingConfigInterface
     */
    private $shippingConfig;

    /**
     * @var SalesProductCollectionLoader
     */
    private $productCollectionLoader;

    /**
     * @var ShipmentDate
     */
    private $shipmentDate;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    public function __construct(
        OrderSelectionManager $selectionManager,
        ShippingConfigInterface $shippingConfig,
        SalesProductCollectionLoader $productCollectionLoader,
        ShipmentDate $shipmentDate,
        OptionInterfaceFactory $optionFactory
    ) {
        $this->selectionManager = $selectionManager;
        $this->shippingConfig = $shippingConfig;
        $this->productCollectionLoader = $productCollectionLoader;
        $this->shipmentDate = $shipmentDate;
        $this->optionFactory = $optionFactory;
    }

    /**
     * Add Deutsche Post shipping products to the shipping product options.
     *
     * In case some service selection was made in checkout that cannot be
     * fulfilled with INTERNETMARKE products, do not add anything.
     *
     * @param string $carrierCode
     * @param array $shippingOptions
     * @param int $storeId
     * @param string $countryCode
     * @param string $postalCode
     * @param ShipmentInterface|null $shipment
     *
     * @return ShippingOptionInterface[]
     * @throws LocalizedException
     */
    public function process(
        string $carrierCode,
        array $shippingOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ShipmentInterface $shipment = null
    ): array {
        $order = $shipment->getOrder();
        $carrierCode = strtok((string) $order->getShippingMethod(), '_');

        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $shippingOptions;
        }

        if (!$shipment) {
            // checkout scope, nothing to modify.
            return $shippingOptions;
        }

        $packageDetails = $shippingOptions[Codes::PACKAGE_OPTION_DETAILS] ?? false;
        if (!$packageDetails instanceof ShippingOptionInterface) {
            // not the package details option.
            return $shippingOptions;
        }

        $selectedPaketOnlyServices = array_filter(
            $this->selectionManager->load((int) $shipment->getShippingAddressId()),
            static function (SelectionInterface $selection) {
                return in_array(
                    $selection->getShippingOptionCode(),
                    [
                        ServiceCodes::SERVICE_OPTION_PREFERRED_DAY,
                        ServiceCodes::SERVICE_OPTION_DROPOFF_DELIVERY,
                        ServiceCodes::SERVICE_OPTION_NEIGHBOR_DELIVERY,
                        ServiceCodes::SERVICE_OPTION_NO_NEIGHBOR_DELIVERY,
                        ServiceCodes::SERVICE_OPTION_DELIVERY_TYPE,
                        CoreCodes::SERVICE_OPTION_DELIVERY_LOCATION,
                        CoreCodes::SERVICE_OPTION_CASH_ON_DELIVERY,
                    ],
                    true
                );
            }
        );

        if (!empty($selectedPaketOnlyServices)) {
            // a service was selected that is not supported by INTERNETMARKE shipping products.
            return $shippingOptions;
        }

        $shipmentDate = $this->shipmentDate->getDate($shipment->getStoreId());
        $originCountry = $this->shippingConfig->getOriginCountry($shipment->getStoreId());
        $destinationCountry = $order->getShippingAddress()->getCountryId();

        $productCollection = $this->productCollectionLoader->getCollectionByDate($shipmentDate);
        $productCollection->setRouteFilter($originCountry, $destinationCountry);
        $dpProducts = [];

        foreach ($productCollection->getItems() as $salesProduct) {
            $option = $this->optionFactory->create();
            $option->setValue((string) $salesProduct->getPPLId());
            $option->setLabel('Deutsche Post ' . $salesProduct->getName());

            $dpProducts[] = $option;
        }

        foreach ($shippingOptions as $optionGroup) {
            foreach ($optionGroup->getInputs() as $input) {
                if ($input->getCode() === Codes::PACKAGE_INPUT_PRODUCT_CODE) {
                    $products = array_merge($input->getOptions(), $dpProducts);
                    $input->setOptions($products);
                    break 2;
                }
            }
        }

        return $shippingOptions;
    }
}
