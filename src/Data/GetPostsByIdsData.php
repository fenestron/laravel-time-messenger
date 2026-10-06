<?php

namespace Fenestron\TimeMessenger\Data;

use InvalidArgumentException;

class GetPostsByIdsData
{
    /**
     * @param  string[]  $ids
     */
    public function __construct(
        public readonly array $ids,
    ) {
        if ($this->ids === []) {
            throw new InvalidArgumentException('The list of ids must not be empty.');
        }
    }

    /** @return string[] */
    public function toBody(): array
    {
        return $this->ids;
    }
}
