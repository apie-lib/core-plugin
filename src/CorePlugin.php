<?php

namespace Apie\CorePlugin;

use Apie\Core\Interfaces\ApiResourceFactoryInterface;
use Apie\Core\Interfaces\FormatRetrieverInterface;
use Apie\Core\Interfaces\ResourceSerializerInterface;
use Apie\Core\PluginInterfaces\AnnotationReaderProviderInterface;
use Apie\Core\PluginInterfaces\ApieAwareInterface;
use Apie\Core\PluginInterfaces\ApieAwareTrait;
use Apie\Core\PluginInterfaces\ApiResourceFactoryProviderInterface;
use Apie\Core\PluginInterfaces\CacheItemPoolProviderInterface;
use Apie\Core\PluginInterfaces\EncoderProviderInterface;
use Apie\Core\PluginInterfaces\NormalizerProviderInterface;
use Apie\Core\PluginInterfaces\ObjectAccessProviderInterface;
use Apie\Core\PluginInterfaces\ResourceLifeCycleInterface;
use Apie\Core\PluginInterfaces\SerializerProviderInterface;
use Apie\Core\PluginInterfaces\SymfonyComponentProviderInterface;
use Apie\CorePlugin\Encodings\FormatRetriever;
use Apie\CorePlugin\Mapping\BaseGroupLoader;
use Apie\CorePlugin\ObjectAccess\ExceptionObjectAccess;
use Apie\CorePlugin\ResourceFactories\FallbackFactory;
use Apie\CorePlugin\ResourceSerializers\SymfonySerializerAdapter;
use Apie\ObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;
use Apie\ObjectAccessNormalizer\Normalizers\MethodCallDenormalizer;
use Apie\ObjectAccessNormalizer\ObjectAccess\ObjectAccessInterface;
use Apie\ObjectAccessNormalizer\ObjectAccess\SelfObjectAccess;
use Apie\ObjectAccessNormalizer\ObjectAccess\SelfObjectAccessInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\PhpFileCache;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;

/**
 * Plugin with most default functionality.
 */
class CorePlugin implements SerializerProviderInterface,
    ApieAwareInterface,
    NormalizerProviderInterface,
    EncoderProviderInterface,
    SymfonyComponentProviderInterface,
    CacheItemPoolProviderInterface,
    AnnotationReaderProviderInterface,
    ApiResourceFactoryProviderInterface,
    ObjectAccessProviderInterface
{
    use ApieAwareTrait;

    private $classMetadataFactory;

    private $propertyConverter;

    private $annotationReader;

    private $arrayAdapter;

    private $apiResourceFactory;

    /**
     * {@inheritDoc}
     */
    public function getResourceSerializer(): ResourceSerializerInterface
    {
        $normalizers = $this->getApie()->getNormalizers();
        $encoders = $this->getApie()->getEncoders();
        $serializer = new Serializer($normalizers, $encoders);
        $lifecycles = $this->apie->getPluginsWithInterface(ResourceLifeCycleInterface::class);
        return new SymfonySerializerAdapter($serializer, $this->getApie()->getFormatRetriever(), $lifecycles);
    }

    /**
     * {@inheritDoc}
     */
    public function getNormalizers(): array
    {
        $apieObjectAccessNormalizer = new ApieObjectAccessNormalizer(
            $this->getApie()->getObjectAccess(),
            $this->getApie()->getPropertyConverter(),
            $this->getApie()->getClassMetadataFactory()
        );

        return [
            new JsonSerializableNormalizer(),
            new ArrayDenormalizer(),
            new MethodCallDenormalizer($this->getApie()->getObjectAccess(), $apieObjectAccessNormalizer, $this->getApie()->getPropertyConverter()),
            $apieObjectAccessNormalizer,
        ];

    }

    /**
     * {@inheritDoc}
     */
    public function getClassMetadataFactory(): ClassMetadataFactoryInterface
    {
        if (!$this->classMetadataFactory) {
            $this->classMetadataFactory = new ClassMetadataFactory(
                new LoaderChain([
                    new AnnotationLoader($this->getApie()->getAnnotationReader()),
                    new BaseGroupLoader(['read', 'write', 'get', 'post', 'put']),
                ])
            );
        }
        return $this->classMetadataFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyConverter(): NameConverterInterface
    {
        if (!$this->propertyConverter) {
            $classMetadataFactory = $this->getApie()->getClassMetadataFactory();
            $this->propertyConverter = new MetadataAwareNameConverter(
                $classMetadataFactory,
                new CamelCaseToSnakeCaseNameConverter()
            );
        }
        return $this->propertyConverter;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheItemPool(): CacheItemPoolInterface
    {
        if (!$this->arrayAdapter) {
            $this->arrayAdapter = new ArrayAdapter(0, true);
        }
        return $this->arrayAdapter;
    }

    /**
     * {@inheritDoc}
     */
    public function getAnnotationReader(): Reader
    {
        if (!$this->annotationReader) {
            /** @scrutinizer ignore-deprecated */AnnotationRegistry::registerLoader('class_exists');
            if (class_exists(PhpFileCache::class) && $this->getApie()->getCacheFolder()) {
                $this->annotationReader = new CachedReader(
                    new AnnotationReader(),
                    new PhpFileCache($this->getApie()->getCacheFolder() . DIRECTORY_SEPARATOR . '/doctrine-cache'),
                    $this->getApie()->isDebug()
                );
            } else {
                $this->annotationReader = new AnnotationReader();
            }
        }
        return $this->annotationReader;
    }

    /**
     * {@inheritDoc}
     */
    public function getEncoders(): array
    {
        return [
            new XmlEncoder([XmlEncoder::ROOT_NODE_NAME => 'item']),
            new JsonEncoder(
                new JsonEncode([JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES]),
                new JsonDecode([JsonDecode::ASSOCIATIVE => false])
            )
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getFormatRetriever(): FormatRetrieverInterface
    {
        return new FormatRetriever([
            'application/json' => 'json',
            'application/xml' => 'xml',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getApiResourceFactory(): ApiResourceFactoryInterface
    {
        if (!$this->apiResourceFactory) {
            $this->apiResourceFactory = new FallbackFactory(
                $this->getApie()->getObjectAccess(),
                $this->getApie()->getIdentifierExtractor()
            );
        }
        return $this->apiResourceFactory;
    }

    /**
     * @return ObjectAccessInterface[]
     */
    public function getObjectAccesses(): array
    {
        return [
            SelfObjectAccessInterface::class => new SelfObjectAccess(),
            Throwable::class => new ExceptionObjectAccess($this->getApie()->isDebug()),
        ];
    }
}
