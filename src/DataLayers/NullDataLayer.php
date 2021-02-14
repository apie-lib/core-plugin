<?php
namespace Apie\CorePlugin\DataLayers;

use Apie\Core\Exceptions\ResourceNotFoundException;
use Apie\Core\Interfaces\ApiResourcePersisterInterface;
use Apie\Core\Interfaces\ApiResourceRetrieverInterface;
use Apie\Core\SearchFilters\SearchFilterRequest;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Persists and retrieves nothing. Only created for entities that require POST, but do not need any storage.
 */
class NullDataLayer implements ApiResourcePersisterInterface, ApiResourceRetrieverInterface
{
    /**
     * {@inheritDoc}
     */
    public function persistNew($resource, array $context = [])
    {
        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function persistExisting($resource, $int, array $context = [])
    {
        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $resourceClass, $id, array $context)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function retrieve(string $resourceClass, $id, array $context)
    {
        throw new ResourceNotFoundException($id);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveAll(string $resourceClass, array $context, SearchFilterRequest $searchFilterRequest
    ): iterable {
        return new Pagerfanta(new ArrayAdapter([]));
    }
}
