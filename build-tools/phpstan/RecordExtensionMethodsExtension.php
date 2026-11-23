<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\BuildTools\PHPStan;

use FilesystemIterator;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * @brief   PHPStan: methods added to records by record extensions.
 *
 * MetaRecord::extend() adds the public static methods of a class to a record,
 * called through MetaRecord::__call() with the record as first argument.
 * PHPStan can not see them: this extension finds the classes implementing
 * RecordExtensionInterface (Dotclear ones, and plugin or theme ones, ex: a class
 * extending Post) and gives their methods, without the first parameter,
 * to MetaRecord, Record and StaticRecord.
 *
 * Classes are searched in Dotclear sources (src and plugins folders), analysed paths
 * and scanned directories.
 * When several extensions have the same method, each one is a variant of the
 * method (the last extension given to extend() wins at runtime, which static
 * analysis can not know).
 */
class RecordExtensionMethodsExtension implements MethodsClassReflectionExtension
{
    private const INTERFACE = 'Dotclear\Interface\Database\RecordExtensionInterface';

    private const RECORDS = [
        'Dotclear\Database\MetaRecord',
        'Dotclear\Database\Record',
        'Dotclear\Database\StaticRecord',
    ];

    /**
     * Extension methods by lower case name.
     *
     * @var null|array<string, list<MethodReflection>>
     */
    private ?array $methods = null;

    /**
     * @param   list<string>    $analysedPaths      Analysed paths
     * @param   list<string>    $scanDirectories    Scanned directories
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $analysedPaths,
        private readonly array $scanDirectories,
    ) {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->isRecord($classReflection) && isset($this->methods()[strtolower($methodName)]);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new RecordExtensionMethodReflection($classReflection, $methodName, $this->methods()[strtolower($methodName)] ?? []);
    }

    private function isRecord(ClassReflection $classReflection): bool
    {
        foreach (self::RECORDS as $record) {
            if ($classReflection->getName() === $record || $classReflection->isSubclassOf($record)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get (and index once) extension methods.
     *
     * @return  array<string, list<MethodReflection>>
     */
    private function methods(): array
    {
        if ($this->methods !== null) {
            return $this->methods;
        }

        $this->methods = [];
        if (!$this->reflectionProvider->hasClass(self::INTERFACE)) {
            return $this->methods;
        }

        // Dotclear sources, even when they are not analysed nor scanned (ex: analyse of one file)
        $dotclear = [];
        $file     = $this->reflectionProvider->getClass(self::INTERFACE)->getFileName();
        if (is_string($file)) {
            $root     = dirname($file, 4);
            $dotclear = [$root . '/src', $root . '/plugins'];
        }

        $classes = [];
        foreach ($this->files($dotclear) as $file) {
            foreach ($this->classesOf($file) as $class) {
                $classes[$class] = true;
            }
        }

        foreach (array_keys($classes) as $class) {
            try {
                if (!$this->reflectionProvider->hasClass($class)) {
                    continue;
                }
                $reflection = $this->reflectionProvider->getClass($class);
                if (!$reflection->implementsInterface(self::INTERFACE)) {
                    continue;
                }
                foreach ($reflection->getNativeReflection()->getMethods() as $native) {
                    if (!$native->isPublic() || !$native->isStatic() || $native->getNumberOfParameters() === 0) {
                        continue;
                    }
                    // A method inherited from a parent extension is indexed once, with its parent
                    if ($native->getDeclaringClass()->getName() !== $reflection->getName()) {
                        continue;
                    }
                    $this->methods[strtolower($native->getName())][] = $reflection->getNativeMethod($native->getName());
                }
            } catch (Throwable) {
                // Class not usable by static analysis: ignore it
            }
        }

        return $this->methods;
    }

    /**
     * PHP files of Dotclear sources, analysed paths and scanned directories.
     *
     * @param   list<string>    $dotclear   Dotclear sources
     *
     * @return  list<string>
     */
    private function files(array $dotclear): array
    {
        $files = [];
        foreach ([...$dotclear, ...$this->analysedPaths, ...$this->scanDirectories] as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    $name = $file->getPathname();
                    if ($file->getExtension() === 'php' && !preg_match('#[/\\\](vendor|node_modules|\.git)[/\\\]#', $name)) {
                        $files[] = $name;
                    }
                }
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * Names of classes declared in a file, which may be record extensions.
     *
     * @return  list<string>
     */
    private function classesOf(string $file): array
    {
        $code = (string) @file_get_contents($file);
        // Quick filter: a record extension has static methods with a record as first parameter
        if (!str_contains($code, 'static function') || !preg_match('/\bclass\s+\w+\s+(extends|implements)\b/i', $code)) {
            return [];
        }

        $classes   = [];
        $namespace = '';
        $tokens    = token_get_all($code);
        $count     = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i])) {
                continue;
            }
            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < $count && $tokens[$j] !== ';' && $tokens[$j] !== '{'; $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED], true)) {
                        $namespace .= $tokens[$j][1];
                    }
                }
            } elseif ($tokens[$i][0] === T_CLASS) {
                // Skip ::class and anonymous classes
                $prev = $i - 1;
                while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) {
                    $prev--;
                }
                if ($prev >= 0 && is_array($tokens[$prev]) && in_array($tokens[$prev][0], [T_DOUBLE_COLON, T_NEW], true)) {
                    continue;
                }
                $next = $i + 1;
                while ($next < $count && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) {
                    $next++;
                }
                if ($next < $count && is_array($tokens[$next]) && $tokens[$next][0] === T_STRING) {
                    $classes[] = ($namespace !== '' ? $namespace . '\\' : '') . $tokens[$next][1];
                }
            }
        }

        return $classes;
    }
}
