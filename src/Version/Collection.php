<?php

namespace Baleen\Migrations\Version;

use Baleen\Migrations\Exception\CollectionException;
use Baleen\Migrations\Exception\InvalidArgumentException;
use Baleen\Migrations\Version\Collection\Resolver\DefaultResolverStackFactory;
use Baleen\Migrations\Version\Collection\Resolver\ResolverInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Zend\Stdlib\ArrayUtils;

/**
 * Class Collection.
 *
 * @author Gabriel Somoza <gabriel@strategery.io>
 *
 * IMPROVE: this class has 11 methods. Consider refactoring it to keep number of methods under 10.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @method VersionInterface first()
 * @method VersionInterface last()
 * @method VersionInterface next()
 * @method VersionInterface current()
 * @method VersionInterface offsetGet($offset)
 * @method VersionInterface offsetUnset($offset)
 * @method VersionInterface[] toArray()
 * @method VersionInterface[] getValues()
 * @property VersionInterface[] elements
 */
class Collection extends ArrayCollection
{
    /** @var ResolverInterface */
    protected $resolver;

    /**
     * @param array|\Traversable $versions
     *
     * @param ResolverInterface $resolver
     * @throws CollectionException
     * @throws InvalidArgumentException
     */
    public function __construct($versions = array(), ResolverInterface $resolver = null)
    {
        if (!is_array($versions)) {
            if ($versions instanceof \Traversable) {
                $versions = ArrayUtils::iteratorToArray($versions);
            } else {
                throw new InvalidArgumentException(
                    "Constructor parameter 'versions' must be an array or Traversable"
                );
            }
        }
        if (null !== $resolver) {
            $this->setResolver($resolver);
        }
        foreach ($versions as $version) {
            $this->validate($version);
        }
        parent::__construct($versions);
    }

    /**
     * @param ResolverInterface $resolver
     */
    public function setResolver(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @return ResolverInterface
     */
    public function getResolver()
    {
        if (null === $this->resolver) {
            $this->resolver = DefaultResolverStackFactory::create();
        }
        return $this->resolver;
    }

    /**
     * Gets an element.
     *
     * @param mixed $key If an alias is given then it will be resolved to an element. Otherwise the $key will be used
     *                   to fetch the element by index.
     * @param bool $resolve Whether to use the resolver or not.
     *
     * @return VersionInterface|null Null if not present
     */
    public function get($key, $resolve = true)
    {
        $result = null;

        if (is_object($key) && $key instanceof VersionInterface) {
            return $this->getById($key->getId());
        }

        if ($resolve && is_string($key)) {
            $result = $this->resolve($key);
        }

        if (null === $result && is_scalar($key)) {
            $result = parent::get($key);
        }

        return $result;
    }

    /**
     * Gets a version by id
     *
     * @param $id
     *
     * @return VersionInterface
     */
    public function getById($id)
    {
        $index = $this->indexOfId($id);
        return $this->get($index, false);
    }

    /**
     * Returns the index of the version that has the given id. Returns null if not found.
     *
     * @param string $id
     *
     * @return int|null
     */
    public function indexOfId($id)
    {
        $id = (string) $id;
        $result = null;
        $this->forAll(function ($index, VersionInterface $version) use ($id, &$result) {
            if ($version->getId() === $id) {
                $result = $index;
                return false; // break
            }
            return true; // continue
        });
        return $result;
    }

    /**
     * Resolves an alias in to a version
     *
     * @param string $alias
     *
     * @return VersionInterface|null
     */
    protected function resolve($alias)
    {
        return $this->getResolver()->resolve((string) $alias, $this);
    }

    /**
     * Returns whether the key exists in the collection.
     *
     * @param      $index
     * @param bool $resolve
     *
     * @return bool
     */
    public function has($index, $resolve = true)
    {
        return $this->get($index, $resolve) !== null;
    }

    /**
     * Returns true if the specified version is valid (can be added) to the collection. Otherwise, it MUST throw
     * an exception.
     *
     * @param VersionInterface $element
     *
     * @return bool
     *
     * @throws CollectionException
     */
    public function validate(VersionInterface $element)
    {
        if (!$this->isEmpty() && $this->contains($element)) {
            throw new CollectionException(
                sprintf('Item with id "%s" already exists.', $element->getId())
            );
        }

        return true; // if there are no exceptions then result is true
    }

    /**
     * invalidateResolverCache
     */
    protected function invalidateResolverCache()
    {
        $this->getResolver()->clearCache($this);
    }

    /**
     * Add a version to the collection
     *
     * @param mixed $element
     *
     * @return bool
     *
     * @throws CollectionException
     * @throws InvalidArgumentException
     */
    public function add($element)
    {
        if (!$element instanceof VersionInterface) {
            throw new InvalidArgumentException(sprintf(
                'Invalid type "%s". Can only add instances of "%s" to this collection.',
                VersionInterface::class,
                is_object($element) ? get_class($element) : gettype($element)
            ));
        }

        if ($this->validate($element)) {
            parent::add($element);
            $this->invalidateResolverCache();
        } else {
            // this should never happen
            throw new CollectionException(
                'Validate should either return true or throw an exception'
            );
        }
        return true;
    }

    /**
     * @param $version
     *
     * @return VersionInterface|null
     */
    public function remove($version)
    {
        if (is_object($version)) {
            $result = $this->removeElement($version);
        } else {
            $result = parent::remove($version);
        }

        if (null !== $result) {
            $this->invalidateResolverCache();
        }
        return $result;
    }

    /**
     * Adds a new version to the collection if it doesn't exist or replaces it if it does.
     *
     * @param VersionInterface $version
     */
    public function addOrReplace(VersionInterface $version)
    {
        $index = $this->indexOfId($version->getId());
        if (null !== $index) {
            $this->remove($index);
        }
        $this->add($version);
    }
}
