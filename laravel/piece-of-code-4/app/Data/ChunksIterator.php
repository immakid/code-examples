<?php

namespace App\Data;

use ArrayIterator;
use InvalidArgumentException;
use Iterator;

trait ChunksIterator
{
    /**
     * @param  Iterator  $iterator
     * @param int $chunkSize
     *
     * @return Iterator
     */
    function chunkIterator(Iterator $iterator, int $chunkSize): Iterator
    {
        if (! $iterator->valid()) {
            return new ArrayIterator();
        }

        if ($chunkSize < 0 ) {
            throw new InvalidArgumentException(
                "The chunk size must be equal or greater than zero; $chunkSize given"
            );
        }

        $count = 0;
        $chunk = [];

        do {
            $chunk[] = $iterator->current();
            $iterator->next();
            $count++;

            if (!(count($chunk) % $chunkSize) || !$iterator->valid()) {
                yield($chunk);
                $chunk = [];
                $count = 0;
            }

        } while ($iterator->valid());
    }
}
