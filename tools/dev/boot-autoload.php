<?php
/**
 * Minimal PSR-4/PSR-0/classmap autoloader built straight from composer.lock.
 * Only used to get Composer itself running; the project then gets a REAL
 * composer-generated autoloader via `dump-autoload`.
 */

function seekitar_boot_autoload(string $root): void
{
    $lock = json_decode(file_get_contents($root . '/composer.lock'), true);
    $packages = array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []);

    $psr4 = [];
    $psr0 = [];
    $classmapDirs = [];
    $files = [];

    foreach ($packages as $pkg) {
        $base = $root . '/vendor/' . $pkg['name'];
        if (!is_dir($base)) {
            continue;
        }
        $autoload = $pkg['autoload'] ?? [];

        foreach ($autoload['psr-4'] ?? [] as $ns => $paths) {
            foreach ((array) $paths as $p) {
                $psr4[$ns][] = rtrim($base . '/' . ltrim($p, '/'), '/');
            }
        }
        foreach ($autoload['psr-0'] ?? [] as $ns => $paths) {
            foreach ((array) $paths as $p) {
                $psr0[$ns][] = rtrim($base . '/' . ltrim($p, '/'), '/');
            }
        }
        foreach ($autoload['classmap'] ?? [] as $p) {
            $classmapDirs[] = $base . '/' . ltrim($p, '/');
        }
        foreach ($autoload['files'] ?? [] as $p) {
            $files[] = $base . '/' . ltrim($p, '/');
        }
    }

    // Root package autoload (App\, Database\Factories\, ...)
    $composerJson = json_decode(file_get_contents($root . '/composer.json'), true);
    foreach (($composerJson['autoload']['psr-4'] ?? []) as $ns => $paths) {
        foreach ((array) $paths as $p) {
            $psr4[$ns][] = rtrim($root . '/' . ltrim($p, '/'), '/');
        }
    }
    foreach (($composerJson['autoload-dev']['psr-4'] ?? []) as $ns => $paths) {
        foreach ((array) $paths as $p) {
            $psr4[$ns][] = rtrim($root . '/' . ltrim($p, '/'), '/');
        }
    }
    foreach (($composerJson['autoload']['files'] ?? []) as $p) {
        $files[] = $root . '/' . ltrim($p, '/');
    }

    // Build a classmap from explicit classmap entries.
    $classmap = [];
    foreach ($classmapDirs as $target) {
        if (is_file($target)) {
            seekitar_scan_file($target, $classmap);
            continue;
        }
        if (!is_dir($target)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php', 'inc'], true)) {
                seekitar_scan_file($file->getPathname(), $classmap);
            }
        }
    }

    // Longest namespace prefix first.
    uksort($psr4, fn($a, $b) => strlen($b) <=> strlen($a));
    uksort($psr0, fn($a, $b) => strlen($b) <=> strlen($a));

    spl_autoload_register(function ($class) use ($psr4, $psr0, $classmap) {
        if (isset($classmap[$class])) {
            require_once $classmap[$class];
            return true;
        }
        foreach ($psr4 as $prefix => $dirs) {
            if ($prefix !== '' && strpos($class, $prefix) !== 0) {
                continue;
            }
            $rest = substr($class, strlen($prefix));
            $rel = str_replace('\\', '/', $rest) . '.php';
            foreach ($dirs as $dir) {
                $candidate = $dir . '/' . $rel;
                if (is_file($candidate)) {
                    require_once $candidate;
                    return true;
                }
            }
        }
        foreach ($psr0 as $prefix => $dirs) {
            if ($prefix !== '' && strpos($class, $prefix) !== 0) {
                continue;
            }
            $rel = str_replace(['\\', '_'], '/', $class) . '.php';
            foreach ($dirs as $dir) {
                $candidate = $dir . '/' . $rel;
                if (is_file($candidate)) {
                    require_once $candidate;
                    return true;
                }
            }
        }
        return false;
    }, true, false);

    foreach ($files as $f) {
        if (is_file($f)) {
            require_once $f;
        }
    }
}

function seekitar_scan_file(string $path, array &$classmap): void
{
    $src = @file_get_contents($path);
    if ($src === false) {
        return;
    }
    $tokens = @token_get_all($src);
    $namespace = '';
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $t = $tokens[$i];
        if (!is_array($t)) {
            continue;
        }
        if ($t[0] === T_NAMESPACE) {
            $namespace = '';
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED], true)) {
                    $namespace = $tokens[$j][1];
                    break;
                }
                if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                    break;
                }
            }
        }
        if (in_array($t[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)
            || (defined('T_ENUM') && $t[0] === T_ENUM)) {
            // Skip ::class and anonymous classes
            $prev = $tokens[$i - 1] ?? null;
            if (is_array($prev) && $prev[0] === T_DOUBLE_COLON) {
                continue;
            }
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $name = $namespace ? $namespace . '\\' . $tokens[$j][1] : $tokens[$j][1];
                    $classmap[$name] = $path;
                    break;
                }
                if ($tokens[$j] === '(' || $tokens[$j] === '{') {
                    break;
                }
            }
        }
    }
}
