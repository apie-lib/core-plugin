<?php

namespace Apie\CorePlugin\ResourceFactories;

use Apie\Core\Exceptions\CouldNotConstructApiResourceClassException;
use Apie\Core\Exceptions\InvalidClassTypeException;
use Apie\Core\Interfaces\ApiResourceFactoryInterface;
use Apie\Core\Interfaces\ApiResourcePersisterInterface;
use Apie\Core\Interfaces\ApiResourceRetrieverInterface;
use Apie\CorePlugin\DataLayers\MemoryDataLayer;
use Apie\ObjectAccessNormalizer\ObjectAccess\ObjectAccessInterface;
use ReflectionClass;
use ReflectionException;
use W2w\Lib\Apie\Core\IdentifierExtractor;

class FallbackFactory implements ApiResourceFactoryInterface
{
    private $propertyAccessor;

    private $identifierExtractor;

    private $memoryDataLayer;

    public function __construct(
        ObjectAccessInterface $propertyAccessor,
        IdentifierExtractor $identifierExtractor
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->identifierExtractor = $identifierExtractor;
    }

    private function getMemoryDataLayer(): MemoryDataLayer
    {
        if (!$this->memoryDataLayer) {
            $this->memoryDataLayer = new MemoryDataLayer(
                $this->propertyAccessor,
                $this->identifierExtractor
            );
        }
        return $this->memoryDataLayer;
    }

    /**
     * Returns true if this factory can create this identifier.
     *
     * @param string $identifier
     * @return bool
     */
    public function hasApiResourceRetrieverInstance(string $identifier): bool
    {
        return  $identifier === MemoryDataLayer::class || $this->isClassWithoutConstructorArguments($identifier);
    }

    /**
     * Gets an instance of ApiResourceRetrieverInstance
     * @param string $identifier
     * @return ApiResourceRetrieverInterface
     */
    public function getApiResourceRetrieverInstance(string $identifier): ApiResourceRetrieverInterface
    {
        if ($identifier === MemoryDataLayer::class) {
            return $this->getMemoryDataLayer();
        }
        $retriever = $this->createClassWithoutConstructorArguments($identifier);
        if (!$retriever instanceof ApiResourceRetrieverInterface) {
            throw new InvalidClassTypeException($identifier, 'ApiResourceRetrieverInterface');
        }
        return $retriever;
    }

    /**
     * Returns true if this factory can create this identifier.
     *
     * @param string $identifier
     * @return bool
     */
    public function hasApiResourcePersisterInstance(string $identifier): bool
    {
        return $identifier === MemoryDataLayer::class || $this->isClassWithoutConstructorArguments($identifier);
    }

    /**
     * Gets an instance of ApiResourceRetrieverInstance
     * @param string $identifier
     * @return ApiResourcePersisterInterface
     */
    public function getApiResourcePersisterInstance(string $identifier): ApiResourcePersisterInterface
    {
        if ($identifier === MemoryDataLayer::class) {
            return $this->getMemoryDataLayer();
        }
        $retriever = $this->createClassWithoutConstructorArguments($identifier);
        if (!$retriever instanceof ApiResourcePersisterInterface) {
            throw new InvalidClassTypeException($identifier, 'ApiResourcePersisterInterface');
        }
        return $retriever;
    }

    private function isClassWithoutConstructorArguments(string $identifier): bool
    {
        try {
            $reflClass = new ReflectionClass($identifier);
        } catch (ReflectionException $reflectionException) {
            return false;
        }
        return !$reflClass->getConstructor() || $reflClass->getConstructor()->getNumberOfRequiredParameters() === 0;
    }

    private function createClassWithoutConstructorArguments(string $identifier): object
    {
        try {
            $reflClass = new ReflectionClass($identifier);
        } catch (ReflectionException $reflectionException) {
            throw new CouldNotConstructApiResourceClassException($identifier, $reflectionException);
        }
        if ($reflClass->getConstructor() && $reflClass->getConstructor()->getNumberOfRequiredParameters() > 0) {
            throw new CouldNotConstructApiResourceClassException($identifier);
        }
        return new $identifier();
    }
}
