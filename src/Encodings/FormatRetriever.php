<?php

namespace Apie\CorePlugin\Encodings;

use Apie\Core\Interfaces\FormatRetrieverInterface;

/**
 * Converts between the format string used for the symfony serializer and the request/response header to indicate
 * the mime type.
 */
class FormatRetriever implements FormatRetrieverInterface
{
    private $mapping;

    public function __construct(array $mapping = [])
    {
        $this->mapping = $mapping;
    }
    /**
     * @param string $contentType
     * @return string|null
     */
    public function getFormat(string $contentType): ?string
    {
        return $this->mapping[$contentType] ?? null;
    }

    public function getContentType(string $format): ?string
    {
        $res = array_search($format, $this->mapping);
        if ($res === false) {
            return null;
        }
        return $res;
    }
}
