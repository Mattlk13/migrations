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

namespace Baleen\Storage;

use Baleen\Exception\InvalidArgumentException;
use Baleen\Version;

/**
 * {@inheritDoc}
 *
 * @author Gabriel Somoza <gabriel@strategery.io>
 */
class FileStorage implements StorageInterface
{

    protected $path;

    /**
     * @param $path
     * @throws InvalidArgumentException
     */
    public function __construct($path)
    {
        if (!is_file($path) && !is_writeable(realpath(dirname($path)))) {
            throw new InvalidArgumentException('Argument "path" must be a valid path to a file which must be writable.');
        }
        $this->path = $path;
    }

    /**
     * Reads versions from the storage file.
     * @return array
     */
    public function readMigratedVersions()
    {
        $contents = explode("\n", file_get_contents($this->path));
        $versions = [];
        foreach ($contents as $versionId) {
            $versionId = trim($versionId);
            if (!empty($versionId)) { // skip empty lines
                $version = new Version($versionId);
                $version->setMigrated(true);
                $versions[] = $version;
            }
        }
        return $versions;
    }

    /**
     * Write a collection of versions to the storage file.
     * @param array $versions
     * @return int
     */
    public function writeMigratedVersions(array $versions)
    {
        $ids = [];
        foreach ($versions as $version) {
            /** @var Version $version */
            if ($version->isMigrated()) {
                $ids[] = $version->getId();
            }
        }
        $contents = implode("\n", $ids);
        return file_put_contents($this->path, $contents) !== false;
    }
}
