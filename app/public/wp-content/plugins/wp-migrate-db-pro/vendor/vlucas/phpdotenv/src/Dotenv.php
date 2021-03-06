<?php

namespace DeliciousBrains\WPMDB\Container\Dotenv;

use DeliciousBrains\WPMDB\Container\Dotenv\Exception\InvalidPathException;
use DeliciousBrains\WPMDB\Container\Dotenv\Loader\Loader;
use DeliciousBrains\WPMDB\Container\Dotenv\Loader\LoaderInterface;
use DeliciousBrains\WPMDB\Container\Dotenv\Repository\Adapter\ArrayAdapter;
use DeliciousBrains\WPMDB\Container\Dotenv\Repository\RepositoryBuilder;
use DeliciousBrains\WPMDB\Container\Dotenv\Repository\RepositoryInterface;
use DeliciousBrains\WPMDB\Container\Dotenv\Store\FileStore;
use DeliciousBrains\WPMDB\Container\Dotenv\Store\StoreBuilder;
use DeliciousBrains\WPMDB\Container\Dotenv\Store\StringStore;
class Dotenv
{
    /**
     * The loader instance.
     *
     * @var \Dotenv\Loader\LoaderInterface
     */
    protected $loader;
    /**
     * The repository instance.
     *
     * @var \Dotenv\Repository\RepositoryInterface
     */
    protected $repository;
    /**
     * The store instance.
     *
     * @var \Dotenv\Store\StoreInterface
     */
    protected $store;
    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Loader\LoaderInterface         $loader
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param \Dotenv\Store\StoreInterface|string[]  $store
     *
     * @return void
     */
    public function __construct(\DeliciousBrains\WPMDB\Container\Dotenv\Loader\LoaderInterface $loader, \DeliciousBrains\WPMDB\Container\Dotenv\Repository\RepositoryInterface $repository, $store)
    {
        $this->loader = $loader;
        $this->repository = $repository;
        $this->store = \is_array($store) ? new \DeliciousBrains\WPMDB\Container\Dotenv\Store\FileStore($store, \true) : $store;
    }
    /**
     * Create a new dotenv instance.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param string|string[]                        $paths
     * @param string|string[]|null                   $names
     * @param bool                                   $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function create(\DeliciousBrains\WPMDB\Container\Dotenv\Repository\RepositoryInterface $repository, $paths, $names = null, $shortCircuit = \true)
    {
        $builder = \DeliciousBrains\WPMDB\Container\Dotenv\Store\StoreBuilder::create()->withPaths($paths)->withNames($names);
        if ($shortCircuit) {
            $builder = $builder->shortCircuit();
        }
        return new self(new \DeliciousBrains\WPMDB\Container\Dotenv\Loader\Loader(), $repository, $builder->make());
    }
    /**
     * Create a new mutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function createMutable($paths, $names = null, $shortCircuit = \true)
    {
        $repository = \DeliciousBrains\WPMDB\Container\Dotenv\Repository\RepositoryBuilder::create()->make();
        return self::create($repository, $paths, $names, $shortCircuit);
    }
    /**
     * Create a new immutable dotenv instance with default repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function createImmutable($paths, $names = null, $shortCircuit = \true)
    {
        $repository = \DeliciousBrains\WPMDB\Container\Dotenv\Repository\RepositoryBuilder::create()->immutable()->make();
        return self::create($repository, $paths, $names, $shortCircuit);
    }
    /**
     * Create a new dotenv instance with an array backed repository.
     *
     * @param string|string[]      $paths
     * @param string|string[]|null $names
     * @param bool                 $shortCircuit
     *
     * @return \Dotenv\Dotenv
     */
    public static function createArrayBacked($paths, $names = null, $shortCircuit = \true)
    {
        $adapter = new \DeliciousBrains\WPMDB\Container\Dotenv\Repository\Adapter\ArrayAdapter();
        $repository = \DeliciousBrains\WPMDB\Container\Dotenv\Repository\RepositoryBuilder::create()->withReaders([$adapter])->withWriters([$adapter])->make();
        return self::create($repository, $paths, $names, $shortCircuit);
    }
    /**
     * Parse the given content and resolve nested variables.
     *
     * This method behaves just like load(), only without mutating your actual
     * environment. We do this by using an array backed repository.
     *
     * @param string $content
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public static function parse($content)
    {
        $adapter = new \DeliciousBrains\WPMDB\Container\Dotenv\Repository\Adapter\ArrayAdapter();
        $repository = \DeliciousBrains\WPMDB\Container\Dotenv\Repository\RepositoryBuilder::create()->withReaders([$adapter])->withWriters([$adapter])->make();
        $phpdotenv = new self(new \DeliciousBrains\WPMDB\Container\Dotenv\Loader\Loader(), $repository, new \DeliciousBrains\WPMDB\Container\Dotenv\Store\StringStore($content));
        return $phpdotenv->load();
    }
    /**
     * Read and load environment file(s).
     *
     * @throws \Dotenv\Exception\InvalidPathException|\Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function load()
    {
        return $this->loader->load($this->repository, $this->store->read());
    }
    /**
     * Read and load environment file(s), silently failing if no files can be read.
     *
     * @throws \Dotenv\Exception\InvalidFileException
     *
     * @return array<string,string|null>
     */
    public function safeLoad()
    {
        try {
            return $this->load();
        } catch (\DeliciousBrains\WPMDB\Container\Dotenv\Exception\InvalidPathException $e) {
            // suppressing exception
            return [];
        }
    }
    /**
     * Required ensures that the specified variables exist, and returns a new validator object.
     *
     * @param string|string[] $variables
     *
     * @return \Dotenv\Validator
     */
    public function required($variables)
    {
        return new \DeliciousBrains\WPMDB\Container\Dotenv\Validator($this->repository, (array) $variables);
    }
    /**
     * Returns a new validator object that won't check if the specified variables exist.
     *
     * @param string|string[] $variables
     *
     * @return \Dotenv\Validator
     */
    public function ifPresent($variables)
    {
        return new \DeliciousBrains\WPMDB\Container\Dotenv\Validator($this->repository, (array) $variables, \false);
    }
}
