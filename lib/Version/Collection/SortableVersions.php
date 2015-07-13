<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <https://github.com/baleen/migrations>.
 */

namespace Baleen\Migrations\Version\Collection;

use Baleen\Migrations\Exception\CollectionException;
use Baleen\Migrations\Version;
use Baleen\Migrations\Version\Collection\IndexedVersions;

/**
 * A collection of Versions.
 *
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
class SortableVersions extends IndexedVersions
{

    /**
     * This makes the collection behave like a set - throwing an exception if the version already exists in the set.
     *
     * @param mixed $version
     * @return bool
     *
     * @throws CollectionException
     */
    public function isAcceptable($version)
    {
        /** @var Version $version */
        if (parent::isAcceptable($version) && $this->has($version->getId())) {
            throw new CollectionException(
                sprintf('Item with id "%s" already exists', $version->getId())
            );
        }
        return true;
    }

    /**
     * @param callable $comparator
     */
    public function sortWith(callable $comparator)
    {
        uasort($this->items, $comparator);
    }

    /**
     * @return static
     */
    public function getReverse()
    {
        return new static(array_reverse($this->items));
    }

    /**
     * Merges another set into this one, replacing versions that exist and adding those that don't.
     *
     * @param SortableVersions $collection
     *
     * @return $this
     */
    public function merge(SortableVersions $collection)
    {
        foreach ($collection as $version) {
            $this->addOrReplace($version);
        }

        return $this;
    }
}