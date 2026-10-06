<?php

namespace Fenestron\TimeMessenger\Data;

use InvalidArgumentException;

class GetChannelPostsData
{
    public function __construct(
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
        public readonly ?int $since = null,
        public readonly ?string $before = null,
        public readonly ?string $after = null,
        public readonly ?bool $skipFetchThreads = null,
        public readonly ?bool $collapsedThreads = null,
        public readonly ?bool $collapsedThreadsExtended = null,
    ) {
        if ($this->since !== null && ($this->before !== null || $this->after !== null || $this->page !== null || $this->perPage !== null)) {
            throw new InvalidArgumentException('The since parameter cannot be combined with before, after, page or per_page.');
        }
    }

    /** @return array<string, mixed> */
    public function toQuery(): array
    {
        return array_filter([
            'page'                     => $this->page,
            'per_page'                 => $this->perPage,
            'since'                    => $this->since,
            'before'                   => $this->before,
            'after'                    => $this->after,
            'skipFetchThreads'         => $this->skipFetchThreads,
            'collapsedThreads'         => $this->collapsedThreads,
            'collapsedThreadsExtended' => $this->collapsedThreadsExtended,
        ], fn (mixed $value): bool => $value !== null);
    }
}
