<?php

namespace Apie\CorePlugin\Mapping;

use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

/**
 * Decorator for the Symfony serializer to always add a default group to all the properties.
 * The default behaviour is to ignore properties with no serialization group.
 */
class BaseGroupLoader implements LoaderInterface
{
    private $groups;

    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return bool
     */
    public function loadClassMetadata(ClassMetadataInterface $classMetadata)
    {
        foreach ($classMetadata->getAttributesMetadata() as $metadata) {
            if (empty($metadata->getGroups())) {
                foreach ($this->groups as $group) {
                    $metadata->addGroup($group);
                }
            }
        }
        return true;
    }
}
