<?php
namespace Trogers1884\ExtendedMigration\Support;

use Trogers1884\ExtendedMigration\Contracts\SchemaManagerInterface;
use InvalidArgumentException;

class SchemaManager implements SchemaManagerInterface
{
    /**
     * The registered schemas.
     *
     * @var array
     */
    protected array $schemas = [];

    /**
     * Register a new schema with the manager.
     *
     * @param string $name The name of the schema
     * @param string $path The base path for schema migrations
     * @param array $dependencies Array of schema names this schema depends on
     * @return bool
     * @throws InvalidArgumentException
     */
    public function registerSchema(string $name, string $path, array $dependencies = []): bool
    {
        if ($this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is already registered.");
        }

        if (!is_dir($path)) {
            throw new InvalidArgumentException("Path '{$path}' does not exist.");
        }

        $this->schemas[$name] = [
            'name' => $name,
            'path' => $path,
            'dependencies' => $dependencies,
        ];

        return true;
    }

    /**
     * Check if a schema is registered.
     *
     * @param string $name
     * @return bool
     */
    public function hasSchema(string $name): bool
    {
        return isset($this->schemas[$name]);
    }

    /**
     * Get schema configuration if it exists.
     *
     * @param string $name
     * @return array|null
     */
    public function getSchema(string $name): ?array
    {
        return $this->schemas[$name] ?? null;
    }

    /**
     * Get all registered schemas.
     *
     * @return array
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Get schema dependencies.
     *
     * @param string $name
     * @return array
     * @throws InvalidArgumentException
     */
    public function getDependencies(string $name): array
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        return $this->schemas[$name]['dependencies'];
    }

    /**
     * Validate schema configuration.
     *
     * @param string $name
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateSchema(string $name): bool
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        $schema = $this->getSchema($name);

        // Validate path exists
        if (!is_dir($schema['path'])) {
            throw new InvalidArgumentException("Schema path '{$schema['path']}' does not exist.");
        }

        // Validate dependencies exist
        foreach ($schema['dependencies'] as $dependency) {
            if (!$this->hasSchema($dependency)) {
                throw new InvalidArgumentException("Dependency schema '{$dependency}' is not registered.");
            }
        }

        return true;
    }

    /**
     * Set the migration path for a schema.
     *
     * @param string $name The name of the schema
     * @param string $path The path to set
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setPath(string $name, string $path): bool
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        $this->validatePath($path);
        $this->schemas[$name]['path'] = $path;

        return true;
    }

    /**
     * Get the migration path for a schema.
     *
     * @param string $name The name of the schema
     * @return string
     * @throws InvalidArgumentException
     */
    public function getPath(string $name): string
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        return $this->schemas[$name]['path'];
    }

    /**
     * Check if a schema's migration path exists.
     *
     * @param string $name The name of the schema
     * @return bool
     * @throws InvalidArgumentException
     */
    public function pathExists(string $name): bool
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        return is_dir($this->getPath($name));
    }

    /**
     * Create the migration path for a schema if it doesn't exist.
     *
     * @param string $name The name of the schema
     * @return bool
     * @throws InvalidArgumentException
     */
    public function createPath(string $name): bool
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        $path = $this->getPath($name);

        if ($this->pathExists($name)) {
            return true;
        }

        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new InvalidArgumentException("Failed to create path '{$path}'");
        }

        return true;
    }

    /**
     * Validate a path for use with schemas.
     *
     * @param string $path The path to validate
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validatePath(string $path): bool
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty');
        }

        // Check if path is absolute
        if (!str_starts_with($path, DIRECTORY_SEPARATOR) && !preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path)) {
            throw new InvalidArgumentException('Path must be absolute');
        }

        // Check if path contains invalid characters
        if (preg_match('/[<>:"|?*]/', $path)) {
            throw new InvalidArgumentException('Path contains invalid characters');
        }

        return true;
    }

    /**
     * Add a dependency to a schema.
     *
     * @param string $name Schema name
     * @param string $dependency Schema name to add as dependency
     * @return bool
     * @throws InvalidArgumentException
     */
    public function addDependency(string $name, string $dependency): bool
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        if (!$this->hasSchema($dependency)) {
            throw new InvalidArgumentException("Dependency schema '{$dependency}' is not registered.");
        }

        if ($name === $dependency) {
            throw new InvalidArgumentException("Schema cannot depend on itself.");
        }

        // Add dependency if it doesn't exist
        if (!in_array($dependency, $this->schemas[$name]['dependencies'])) {
            $this->schemas[$name]['dependencies'][] = $dependency;

            // Check for circular dependencies
            try {
                $this->checkCircularDependencies($name);
            } catch (InvalidArgumentException $e) {
                // Remove the dependency if it creates a circular reference
                array_pop($this->schemas[$name]['dependencies']);
                throw new InvalidArgumentException("Adding this dependency would create a circular reference: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Remove a dependency from a schema.
     *
     * @param string $name Schema name
     * @param string $dependency Schema name to remove from dependencies
     * @return bool
     * @throws InvalidArgumentException
     */
    public function removeDependency(string $name, string $dependency): bool
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        $key = array_search($dependency, $this->schemas[$name]['dependencies']);
        if ($key === false) {
            throw new InvalidArgumentException("Schema '{$name}' does not depend on '{$dependency}'.");
        }

        unset($this->schemas[$name]['dependencies'][$key]);
        $this->schemas[$name]['dependencies'] = array_values($this->schemas[$name]['dependencies']);

        return true;
    }

    /**
     * Validate all dependencies for a schema.
     *
     * @param string $name Schema name
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateDependencies(string $name): bool
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        foreach ($this->schemas[$name]['dependencies'] as $dependency) {
            if (!$this->hasSchema($dependency)) {
                throw new InvalidArgumentException("Dependency schema '{$dependency}' is not registered.");
            }
        }

        return $this->checkCircularDependencies($name);
    }

    /**
     * Get the complete dependency graph for all schemas.
     *
     * @return array<string, array<string>>
     */
    public function getDependencyGraph(): array
    {
        $graph = [];
        foreach ($this->schemas as $name => $schema) {
            $graph[$name] = $schema['dependencies'];
        }
        return $graph;
    }

    /**
     * Check for circular dependencies in the schema.
     *
     * @param string $name Schema name to check
     * @return bool True if no circular dependencies are found
     * @throws InvalidArgumentException If circular dependencies are detected
     */
    public function checkCircularDependencies(string $name): bool
    {
        if (!$this->hasSchema($name)) {
            throw new InvalidArgumentException("Schema '{$name}' is not registered.");
        }

        $visited = [];
        $path = [];

        $detectCycle = function(string $current) use (&$detectCycle, &$visited, &$path): void {
            $visited[$current] = true;
            $path[$current] = true;

            foreach ($this->schemas[$current]['dependencies'] as $dependency) {
                if (!isset($visited[$dependency])) {
                    $detectCycle($dependency);
                } elseif (isset($path[$dependency])) {
                    throw new InvalidArgumentException(
                        "Circular dependency detected: " . implode(" -> ", array_keys($path)) . " -> " . $dependency
                    );
                }
            }

            unset($path[$current]);
        };

        $detectCycle($name);
        return true;
    }


}
