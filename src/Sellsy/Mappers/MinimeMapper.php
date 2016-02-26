<?php

namespace Sellsy\Mappers;

use Minime\Annotations\Reader;
use phpDocumentor\Reflection\DocBlock\Tag;
use Sellsy\Exception\RuntimeException;
use Sellsy\Models\Accounting\Currency;
use Sellsy\Models\Accounting\CurrencyInterface;
use Sellsy\Models\ApiInfos;
use Sellsy\Models\ApiInfosInterface;
use Sellsy\Models\Catalogue\Item;
use Sellsy\Models\Catalogue\ItemInterface;
use Sellsy\Models\Client\Contact;
use Sellsy\Models\Client\ContactInterface;
use Sellsy\Models\Client\Customer;
use Sellsy\Models\Client\CustomerInterface;
use Sellsy\Models\CustomFields\CustomField;
use Sellsy\Models\CustomFields\CustomFieldInterface;
use Sellsy\Models\Documents\Delivery;
use Sellsy\Models\Documents\DeliveryInterface;
use Sellsy\Models\Documents\Document\Step;
use Sellsy\Models\Documents\Document\StepInterface;
use Sellsy\Models\Documents\Estimate;
use Sellsy\Models\Documents\EstimateInterface;
use Sellsy\Models\Documents\Invoice;
use Sellsy\Models\Documents\InvoiceInterface;
use Sellsy\Models\Documents\Order;
use Sellsy\Models\Documents\OrderInterface;
use Sellsy\Models\Documents\Proforma;
use Sellsy\Models\Documents\ProformaInterface;
use Sellsy\Models\SmartTags\TagInterface;
use Sellsy\Models\Staff\People;
use Sellsy\Models\Staff\PeopleInterface;

/**
 * Class MinimeMapper
 * @package Sellsy\Mappers
 */
class MinimeMapper implements MapperInterface
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var array
     */
    protected $interfacesMappings;

    /**
     * MinimeMapper constructor.
     *
     * @param Reader $reader
     * @param array $interfacesMappings
     */
    public function __construct(Reader $reader, array $interfacesMappings = array())
    {
        $this->reader = $reader;
        $this->interfacesMappings = $this->getDefaultInterfacesMappings();

        foreach($interfacesMappings as $interface => $class) {
            $this->setInterfaceMapping($interface, $class);
        }
    }

    /**
     * @param $interface
     * @param $class
     * @throws RuntimeException
     */
    public function setInterfaceMapping($interface, $class)
    {
        if (! isset($this->interfacesMappings[$interface])) {
            throw new RuntimeException(sprintf("Unable to set a mapping for unknown interface %s", $interface));
        }

        $this->interfacesMappings[$interface] = $class;
    }

    /**
     * @param null $interface
     * @return bool
     */
    public function resetInterfaceMapping($interface = null)
    {
        if (!$interface) {
            $this->interfacesMappings = $this->getDefaultInterfacesMappings();

            return true;
        }

        if (isset($this->interfacesMappings[$interface])) {
            $this->interfacesMappings[$interface] = $this->getDefaultInterfacesMappings()[$interface];

            return true;
        }

        return false;
    }

    /**
     * @param $interface
     * @param array $data
     * @return mixed
     */
    public function mapObject($interface, array $data)
    {
        $interface = ltrim($interface, '\\');

        $objectFactory = new \ReflectionClass($this->interfacesMappings[$interface]);
        $object = $objectFactory->newInstanceWithoutConstructor();

        $objectReflection = new \ReflectionObject($object);

        foreach($objectReflection->getProperties() as $property) {
            $propertyAnnotation = $this->reader->getPropertyAnnotations($objectReflection->getName(), $property->getName());

            // Skip static property
            if ($property->isStatic()) {
                continue;
            }

            // Unlock property if need
            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }

            $translations = $propertyAnnotation->get('copy');
            $translations = $translations === true ? $property->getName() : $translations;

            // Case 1: Copy of scalar value inside the property
            if (is_scalar($translations)) {
                $property->setValue($object, $this->extractData($data, $translations));
                continue;
            }

            // Get the type of target property
            $type = $propertyAnnotation->get('var');

            // Case 2: Copy of many attributes inside the property as an associative array
            if (is_object($translations) && strtolower($type) == 'array') {
                $propertyValue = array();

                foreach($translations as $origin => $target) {
                    $propertyValue[$target] = $this->extractData($data, $origin);
                }

                $property->setValue($object, $propertyValue);
                continue;
            }

            // Case 3: Copy of many attributes inside the property as a collection of objects
            if (is_object($translations) && strpos($type, '[]') !== false) {
                $propertyValues = array();

                // Extract collection translations and data
                $collectionOrigin = key($translations);
                $collectionTranslations = current($translations);
                $collectionDataList = $this->extractData($data, $collectionOrigin);

                foreach($collectionDataList as $collectionData) {
                    if (is_array($collectionData)) {
                        $newInstanceData = $this->extractDataObject($collectionData, $collectionTranslations);

                        $propertyValues[] = $this->mapObject(str_replace('[]', '', $type), $newInstanceData);
                    }
                }

                $property->setValue($object, $propertyValues);
                continue;
            }

            // Case 4: Copy of many attributes inside the property as an object
            if (is_object($translations)) {
                // Map our property, ie. the new instance just created
                $dataForProperty = $this->extractDataObject($data, $translations);
                $propertyValue = $this->mapObject($type, $dataForProperty);

                // Set the property
                $property->setValue($object, $propertyValue);
                continue;
            }
        }

        return $object;
    }

    /**
     * @param array $data
     * @param $path
     * @return mixed
     */
    protected function extractData(array $data, $path)
    {
        $extractedData = null;

        foreach(explode('.', $path) as $keyPart) {
            $extractedData = isset($data[$keyPart]) ? $data[$keyPart] : null;
            $data = $extractedData;
        }

        return $this->castData($extractedData);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    protected function castData($data)
    {
        if (! is_scalar($data)) {
            return $data;
        }

        if ($data === 'Y') {
            return true;
        }

        if ($data === 'N') {
            return false;
        }

        if (is_numeric($data)) {
            return $data + 0;
        }

        if (strtotime($data)) {
            return new \DateTime($data);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param $translations
     * @return array
     */
    protected function extractDataObject(array $data, $translations)
    {
        $targetData = array();

        foreach($translations as $origin => $target) {
            $targetData[$target] = $this->extractData($data, $origin);
        }

        return $targetData;
    }

    /**
     * @return array
     */
    protected function getDefaultInterfacesMappings()
    {
        return array(
            ApiInfosInterface::class => ApiInfos::class,
            CurrencyInterface::class => Currency::class,
            ItemInterface::class => Item::class,
            ContactInterface::class => Contact::class,
            CustomerInterface::class => Customer::class,
            CustomFieldInterface::class => CustomField::class,
            DeliveryInterface::class => Delivery::class,
            EstimateInterface::class => Estimate::class,
            InvoiceInterface::class => Invoice::class,
            OrderInterface::class => Order::class,
            ProformaInterface::class => Proforma::class,
            StepInterface::class => Step::class,
            TagInterface::class => Tag::class,
            PeopleInterface::class => People::class,
        );
    }
}
