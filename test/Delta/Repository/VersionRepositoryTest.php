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
 * <http://www.doctrine-project.org>.
 */

namespace BaleenTest\Migrations\Delta\Repository;

use Baleen\Migrations\Delta\Collection\Collection;
use Baleen\Migrations\Delta\Collection\Migrated;
use Baleen\Migrations\Delta\Repository\Mapper\VersionMapperInterface;
use Baleen\Migrations\Delta\Repository\VersionRepository;
use Baleen\Migrations\Delta\DeltaId;
use Baleen\Migrations\Delta\DeltaInterface;
use BaleenTest\Migrations\BaseTestCase;
use Mockery as m;

/**
 * Class VersionRepositoryTest
 *
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
class VersionRepositoryTest extends BaseTestCase
{
    /**
     * testUpdate
     * @param $isMigrated
     * @param $return
     *
     * @dataProvider updateProvider
     */
    public function testUpdate($isMigrated, $return)
    {
        $v = $this->buildVersion('v1', $isMigrated);
        /** @var VersionMapperInterface|m\Mock $mapper */
        $mapper = m::mock(VersionMapperInterface::class);
        $repository = new VersionRepository($mapper);
        $mapper->shouldReceive($isMigrated ? 'save' : 'delete')->once()->with($v->getId())->andReturn($return);
        $result = $repository->update($v);
        $this->assertSame($return, $result);
    }

    /**
     * updateProvider
     * @return array
     */
    public function updateProvider()
    {
        $trueFalse = [true, false];
        $results = [m::mock(DeltaInterface::class), null];
        return $this->combinations([$trueFalse, $results]);
    }

    /**
     * testFetchAll
     * @param m\Mock|Migrated $collection
     * @dataProvider fetchAllProvider
     */
    public function testFetchAll($collection)
    {
        /** @var VersionMapperInterface|m\Mock $mapper */
        $mapper = m::mock(VersionMapperInterface::class);
        $repository = new VersionRepository($mapper);
        $mapper->shouldReceive('fetchAll')->once()->andReturn($collection);
        $results = $repository->fetchAll();
        $this->assertContainsOnlyInstancesOf(DeltaId::class, $results);
    }

    /**
     * fetchAllProvider
     * @return array
     */
    public function fetchAllProvider()
    {
        return [
            [[]],
            [['v4']],
            [range(5, 10)],
            [[1.0]],
        ];
    }

    /**
     * testUpdateAll
     * @return void
     */
    public function testUpdateAll()
    {
        /** @var VersionMapperInterface|m\Mock $mapper */
        $mapper = m::mock(VersionMapperInterface::class);
        $repo = new VersionRepository($mapper);
        $versions = $this->buildVersions(range(1, 5));

        $ids = array_map(function (DeltaInterface $v) { return $v->getId(); }, $versions);
        $mapper->shouldReceive('saveAll')->with(m::on(function (array $values) use ($ids) {
            foreach ($values as $value) {
                if (!in_array($value, $ids)) {
                    return false;
                }
            }
            return true;
        }))->once()->andReturn(true); // returning true is important here

        $mapper->shouldReceive('deleteAll')->with(m::on(function (array $values) use ($ids) {
            foreach ($values as $value) {
                if (!in_array($value, $ids)) {
                    return false;
                }
            }
            return true;
        }))->once()->andReturn(false); // returning false is important here

        $collection = new Collection($versions);
        $result = $repo->updateAll($collection);

        $this->assertEquals(false, $result);
    }

    /**
     * testUpdateAllExistsEarlyIfEmpty
     * @return void
     */
    public function testUpdateAllExistsEarlyIfEmpty()
    {
        /** @var VersionMapperInterface|m\Mock $mapper */
        $mapper = m::mock(VersionMapperInterface::class);
        $repo = new VersionRepository($mapper);
        $repo->updateAll(new Collection());
    }

    /**
     * testFetch
     * @return void
     */
    public function testFetch()
    {
        /** @var VersionMapperInterface|m\Mock $mapper */
        $mapper = m::mock(VersionMapperInterface::class);
        $repo = new VersionRepository($mapper);

        $mapper->shouldReceive('fetch')->with(m::type(DeltaId::class))->once()->andReturn('something');

        $result = $repo->fetch('v1');
        $this->assertEquals('something', $result);
    }
}
