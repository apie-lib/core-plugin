<?php

namespace Apie\CorePlugin\ObjectAccess\Getters;

use Apie\ObjectAccessNormalizer\Getters\GetterInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Maps getCode of exceptions.
 *
 * @internal
 */
class CodeGetter implements GetterInterface
{
    public function getName(): string
    {
        return 'code';
    }

    public function getValue($object)
    {
        $code = $object->getCode();
        // even though exceptions should return ints, some php exceptions like DBOException throw strings.
        return (int) $code;
    }

    public function toType(): ?Type
    {
        return new Type(Type::BUILTIN_TYPE_INT);
    }

    public function getPriority(): int
    {
        return 0;
    }
}
