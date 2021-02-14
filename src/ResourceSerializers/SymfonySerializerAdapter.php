<?php

namespace Apie\CorePlugin\ResourceSerializers;

use Apie\Core\Events\DecodeEvent;
use Apie\Core\Interfaces\FormatRetrieverInterface;
use Apie\Core\Interfaces\ResourceSerializerInterface;
use Apie\Core\PluginInterfaces\ResourceLifeCycleInterface;
use Apie\ObjectAccessNormalizer\ObjectAccess\ObjectAccess;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * Wrapper around Symfony Serializer.
 */
class SymfonySerializerAdapter implements ResourceSerializerInterface
{
    const INTERNAL_FOR_DATALAYER = 'datalayer';

    private $serializer;

    private $formatRetriever;

    private $resourceLifeCycles;

    /**
     * @param Serializer $serializer
     * @param FormatRetrieverInterface $formatRetriever
     * @param iterable<ResourceLifeCycleInterface> $resourceLifeCycles
     */
    public function __construct(
        Serializer $serializer,
        FormatRetrieverInterface $formatRetriever,
        iterable $resourceLifeCycles
    ) {
        $this->serializer = $serializer;
        $this->formatRetriever = $formatRetriever;
        $this->resourceLifeCycles = $resourceLifeCycles;
    }

    private function toFormat(?string $acceptHeader): string
    {
        return $acceptHeader === self::INTERNAL_FOR_DATALAYER
            ? self::INTERNAL_FOR_DATALAYER
            : ($this->formatRetriever->getFormat($acceptHeader) ?? 'json');
    }

    /**
     * Helper method to call the method on all all lifecycle instances.
     *
     * @param string $event
     * @param mixed[] $args
     */
    private function runLifeCycleEvent(string $event, ...$args)
    {
        foreach ($this->resourceLifeCycles as $resourceLifeCycle) {
            $resourceLifeCycle->$event(...$args);
        }
    }

    /**
     * In case the symfony serializer is needed outside the adapter. It's highly discouraged to use
     * directly.
     *
     * @return Serializer
     */
    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function putData(object $resource, string $requestBody, string $contentType): object
    {
        $contentFormat = $this->toFormat($contentType);
        $event = new DecodeEvent($requestBody, $contentType, $resource, get_class($resource));
        $this->runLifeCycleEvent('onPreDecodeRequestBody', $event);
        if (!$event->hasDecodedData()) {
            $event->setDecodedData($this->decodeRequestBody($requestBody, $contentType));
        }
        $this->runLifeCycleEvent('onPostDecodeRequestBody', $event);

        return $this->serializer->denormalize(
            $event->getDecodedData(),
            get_class($resource),
            $contentFormat,
            [
                'groups' => ['base', 'write', 'put'],
                'object_to_populate' => $resource
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function decodeRequestBody(string $requestBody, string $contentType)
    {
        $contentFormat = $this->toFormat($contentType);
        return $this->serializer->decode($requestBody, $contentFormat, []);
    }

    /**
     * {@inheritDoc}
     */
    public function postData(string $resourceClass, string $requestBody, string $contentType): object
    {
        $contentFormat = $this->toFormat($contentType);
        $event = new DecodeEvent($requestBody, $contentType, null, $resourceClass);
        $this->runLifeCycleEvent('onPreDecodeRequestBody', $event);
        if (!$event->hasDecodedData()) {
            $event->setDecodedData($this->decodeRequestBody($requestBody, $contentType));
        }
        $this->runLifeCycleEvent('onPostDecodeRequestBody', $event);
        return $this->serializer->denormalize(
            $event->getDecodedData(),
            $resourceClass,
            $contentFormat,
            [
                'groups' => ['base', 'write', 'post'],
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function toResponse($resource, string $acceptHeader): ResponseInterface
    {
        $format = $this->toFormat($acceptHeader);
        $contentType = $this->formatRetriever->getContentType($format);
        $response = $this->serializer->serialize($resource, $format, ['groups' => ['base', 'read', 'get']]);

        return new TextResponse($response, is_null($resource) ? 204 : 200, ['content-type' => $contentType]);
    }

    /**
     * {@inheritDoc}
     */
    public function normalize($resource, string $acceptHeader)
    {
        $format = $this->toFormat($acceptHeader);
        return $this->serializer->normalize($resource, $format, ['groups' => ['base', 'read', 'get']]);
    }

    /**
     * {@inheritDoc}
     */
    public function hydrateWithReflection(array $data, string $resourceClass)
    {
        return $this->serializer->denormalize(
            $data,
            $resourceClass,
            self::INTERNAL_FOR_DATALAYER,
            ['object_access' => new ObjectAccess(false, true), 'keep_setter_calls' => true]
        );
    }
}
