<?php

namespace Apie\CorePlugin\ObjectAccess\Getters;

use Apie\ObjectAccessNormalizer\Getters\GetterInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Maps getMessage from exceptions.
 *
 * @internal
 */
class MessageGetter implements GetterInterface
{
    public function getName(): string
    {
        return 'message';
    }

    public function getValue($object)
    {
        return $object->getMessage();
    }

    public function toType(): ?Type
    {
        return new Type(Type::BUILTIN_TYPE_STRING);
    }

    public function getPriority(): int
    {
        return 0;
    }
}
