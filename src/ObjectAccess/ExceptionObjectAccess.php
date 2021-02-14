<?php


namespace Apie\CorePlugin\ObjectAccess;

use Apie\CorePlugin\ObjectAccess\Getters\CodeGetter;
use Apie\CorePlugin\ObjectAccess\Getters\ErrorsGetter;
use Apie\CorePlugin\ObjectAccess\Getters\MessageGetter;
use Apie\ObjectAccessNormalizer\Exceptions\ValidationException;
use Apie\ObjectAccessNormalizer\ObjectAccess\ObjectAccess;
use ReflectionClass;

class ExceptionObjectAccess extends ObjectAccess
{
    /**
     * @var bool
     */
    private $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
        parent::__construct(true, true);
    }

    /**
     * Excepions are always immutable.
     *
     * @param ReflectionClass $reflectionClass
     * @return array
     */
    protected function getSetterMapping(ReflectionClass $reflectionClass): array
    {
        return [];
    }

    protected function getGetterMapping(ReflectionClass $reflectionClass): array
    {
        $mapping = parent::getGetterMapping($reflectionClass);
        $mapping['message'] = [new MessageGetter()];
        $mapping['code'] = [new CodeGetter()];
        unset($mapping['file']);
        unset($mapping['line']);
        unset($mapping['previous']);
        if ($this->debug) {
            $mapping['trace'] = $mapping['traceAsString'];
        } else {
            unset($mapping['trace']);
        }
        unset($mapping['traceAsString']);
        if ($reflectionClass->name === ValidationException::class) {
            $mapping['errors'] = [new ErrorsGetter()];
            unset($mapping['exceptions']);
            unset($mapping['i18n']);
            unset($mapping['errorBag']);
            unset($mapping['headers']);
            unset($mapping['statusCode']);
        }
        return $mapping;
    }
}
