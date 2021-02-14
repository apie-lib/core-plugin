<?php
namespace Apie\CorePlugin\Exceptions;

use Apie\Core\Exceptions\ApieException;

class CanNotDetermineIdException extends ApieException
{
    public function __construct($resource, string $identifier = 'id')
    {
        parent::__construct(500, 'Resource ' . get_class($resource) . ' has no ' . $identifier . ' property');
    }
}
