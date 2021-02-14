<?php

namespace Apie\CorePlugin\ObjectAccess\Getters;

use Apie\ObjectAccessNormalizer\Exceptions\ValidationException;
use Apie\ObjectAccessNormalizer\Getters\GetterInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Mapping getErrors for ValidationException
 *
 * @see ValidationException
 * @internal
 */
class ErrorsGetter implements GetterInterface
{
    public function getName(): string
    {
        return 'errors';
    }

    public function getValue($object)
    {
        return $object->getErrors();
    }

    public function toType(): ?Type
    {
        return new Type(
            Type::BUILTIN_TYPE_ARRAY,
            false,
            null,
            true,
            new Type(Type::BUILTIN_TYPE_STRING),
            new Type(Type::BUILTIN_TYPE_ARRAY)
        );
    }

    public function getPriority(): int
    {
        return 0;
    }
}
