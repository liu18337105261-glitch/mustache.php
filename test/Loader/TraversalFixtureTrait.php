<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mustache\Test\Loader;

trait TraversalFixtureTrait
{
    private function createTraversalFixture($prefix)
    {
        $root = sys_get_temp_dir() . '/' . $prefix . '_' . str_replace('.', '', uniqid('', true));
        $base = $root . '/views/partials';
        $secret = $root . '/secret';

        mkdir($base, 0700, true);
        mkdir($secret, 0700, true);
        file_put_contents($base . '/safe.mustache', 'safe contents');
        file_put_contents($secret . '/leaked.mustache', 'leaked contents');
        file_put_contents($secret . '/raw.txt', 'raw contents');

        return [
            'root' => $root,
            'base' => $base,
            'secret' => $secret,
        ];
    }

    private function removeTraversalFixture(array $paths)
    {
        $this->removeTraversalDirectory($paths['root']);
    }

    private function removeTraversalDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . '/' . $file;
            if (is_dir($path) && !is_link($path)) {
                $this->removeTraversalDirectory($path);
            } elseif (file_exists($path)) {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
