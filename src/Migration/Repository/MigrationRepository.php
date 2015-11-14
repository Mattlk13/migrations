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

namespace Baleen\Migrations\Migration\Repository;

use Baleen\Migrations\Migration\Repository\Mapper\DefinitionInterface;
use Baleen\Migrations\Migration\Repository\Mapper\RepositoryMapperInterface;
use Baleen\Migrations\Version\Collection\Collection;
use Baleen\Migrations\Version\Comparator\ComparatorInterface;
use Baleen\Migrations\Version\Comparator\MigrationComparator;
use Baleen\Migrations\Version\Repository\VersionRepositoryInterface as VersionRepositoryInterface;
use Baleen\Migrations\Version\Version;
use Baleen\Migrations\Version\VersionId;

/**
 * Class MigrationRepository.
 *
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
final class MigrationRepository implements MigrationRepositoryInterface
{
    /** @var ComparatorInterface */
    private $comparator = null;

    /** @var VersionRepositoryInterface */
    private $storage;

    /** @var RepositoryMapperInterface */
    private $mapper;

    /**
     * MigrationRepository constructor
     *
     * @param VersionRepositoryInterface $storage
     * @param RepositoryMapperInterface $mapper
     * @param ComparatorInterface $comparator
     */
    public function __construct(
        VersionRepositoryInterface $storage,
        RepositoryMapperInterface $mapper,
        ComparatorInterface $comparator = null
    ) {
        if (null === $comparator) {
            // this is the default because we're hashing IDs by default
            $comparator = new MigrationComparator();
        }
        $this->comparator = $comparator;

        $this->storage = $storage;
        $this->mapper = $mapper;
    }

    /**
     * @inheritdoc
     */
    public function fetchAll()
    {
        $definitions = $this->mapper->fetchAll();
        $stored = array_map(function (VersionId $id) {
            return $id->toString();
        }, $this->storage->fetchAll());

        $collection = new Collection();
        foreach ($definitions as $definition) {
            /** @var DefinitionInterface $definition */
            $migration = $definition->getMigration();
            $id = $definition->getId();
            $migrated = in_array($id->toString(), $stored);
            $version = new Version($migration, $migrated, $id);
            $collection->add($version);
        }

        return $collection->sort($this->comparator);
    }
}
