<?php
namespace Apie\CorePlugin\DataLayers;

use Apie\Core\Exceptions\ResourceNotFoundException;
use Apie\Core\Interfaces\ApiResourcePersisterInterface;
use Apie\Core\Interfaces\ApiResourceRetrieverInterface;
use Apie\Core\Interfaces\SearchFilterProviderInterface;
use Apie\Core\SearchFilters\SearchFilterFromMetadataTrait;
use Apie\Core\SearchFilters\SearchFilterHelper;
use Apie\Core\SearchFilters\SearchFilterRequest;
use Apie\CorePlugin\Exceptions\CanNotDetermineIdException;
use Apie\ObjectAccessNormalizer\ObjectAccess\ObjectAccess;
use Apie\ObjectAccessNormalizer\ObjectAccess\ObjectAccessInterface;
use Pagerfanta\Pagerfanta;
use W2w\Lib\Apie\Core\IdentifierExtractor;

/**
 * Persists and retrieves from an array in memory. Only useful for unit tests.
 */
class MemoryDataLayer implements ApiResourcePersisterInterface, ApiResourceRetrieverInterface, SearchFilterProviderInterface
{
    use SearchFilterFromMetadataTrait;

    /**
     * @var ObjectAccessInterface
     */
    private $propertyAccessor;

    /**
     * @var IdentifierExtractor
     */
    private $identifierExtractor;

    /**
     * @var mixed[]
     */
    private $persisted = [];

    public function __construct(ObjectAccessInterface $propertyAccessor = null, IdentifierExtractor $identifierExtractor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? new ObjectAccess();
        $this->identifierExtractor = $identifierExtractor ?? new IdentifierExtractor($this->propertyAccessor);
    }
    /**
     * Persist a new API resource. Should return the new API resource.
     *
     * @param mixed $resource
     * @param array $context
     * @return mixed
     */
    public function persistNew($resource, array $context = [])
    {
        $className = get_class($resource);
        $identifier = $this->identifierExtractor->getIdentifierKey($resource, $context);
        $keepReference = $context['keep_reference'] ?? false;
        if (null === $identifier) {
            throw new CanNotDetermineIdException($resource, $identifier ?? 'id');
        }
        $id = (string) $this->propertyAccessor->getValue($resource, $identifier);
        if (empty($this->persisted[$className])) {
            $this->persisted[$className] = [];
        }
        if (!$keepReference) {
            $resource = clone $resource;
        }
        $this->persisted[$className][$id] = $resource;
        return $resource;
    }

    /**
     * Persist an existing API resource. The input resource is the modified API resource. Should return the new API
     * resource.
     *
     * @param mixed $resource
     * @param string|int $int
     * @param array $context
     * @return mixed
     */
    public function persistExisting($resource, $int, array $context = [])
    {
        $className = get_class($resource);
        $keepReference = $context['keep_reference'] ?? false;
        if (!$keepReference) {
            $resource = clone $resource;
        }
        $this->persisted[$className][(string) $int] = $resource;
        return $resource;
    }

    /**
     * Removes an existing API resource.
     *
     * @param string $resourceClass
     * @param string|int $id
     * @param array $context
     */
    public function remove(string $resourceClass, $id, array $context)
    {
        if (!empty($this->persisted[$resourceClass][$id])) {
            unset($this->persisted[$resourceClass][$id]);
        }
    }

    /**
     * Retrieves a single resource by some identifier.
     *
     * @param string $resourceClass
     * @param string|int $id
     * @param array $context
     * @return mixed
     */
    public function retrieve(string $resourceClass, $id, array $context)
    {
        $id = (string) $id;
        if (empty($this->persisted[$resourceClass][$id])) {
            throw new ResourceNotFoundException($id);
        }
        return $this->persisted[$resourceClass][$id];
    }

    /**
     * Retrieves a list of resources with some pagination.
     *
     * @param string $resourceClass
     * @param array $context
     * @param SearchFilterRequest $searchFilterRequest
     * @return Pagerfanta|array
     */
    public function retrieveAll(string $resourceClass, array $context, SearchFilterRequest $searchFilterRequest): iterable
    {
        if (empty($this->persisted[$resourceClass])) {
            return [];
        }
        return SearchFilterHelper::applyPaginationToSearchFilter(
            $this->persisted[$resourceClass],
            $searchFilterRequest,
            $this->propertyAccessor
        );
    }
}
