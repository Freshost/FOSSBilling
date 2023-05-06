<?php
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc. 
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

class Box_TwigLoader extends Twig\Loader\FilesystemLoader
{
    protected $options = array();

    /**
     * Constructor.
     *
     * @param string|array $options A path or an array of options and paths
     */
    public function __construct(array $options)
    {
        parent::__construct();
        if (!isset($options['mods'])) {
            throw new \Box_Exception('Missing mods param for Box_TwigLoader');
        }

        if (!isset($options['theme'])) {
            throw new \Box_Exception('Missing theme param for Box_TwigLoader');
        }

        if (!isset($options['type'])) {
            throw new \Box_Exception('Missing type param for Box_TwigLoader');
        }

        $this->options = $options;
        $paths_arr = array($options['mods'], $options['theme']);
        $this->setPaths($paths_arr);
    }


    protected function findTemplate($name, $throw = true)
    {
        // normalize name
        $name = preg_replace('#/{2,}#', '/', strtr($name, '\\', '/'));

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $name_split = explode("_", $name);

        $paths = array();
        $paths[] = $this->options["theme"] . DIRECTORY_SEPARATOR . "html";
        if (isset($name_split[1])) {
            $paths[] = $this->options["mods"] . DIRECTORY_SEPARATOR . ucfirst($name_split[1]) . DIRECTORY_SEPARATOR . "html_" . $this->options["type"];
        }

        foreach ($paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $name)) {
                return $this->cache[$name] = $path . '/' . $name;
            }

            if (str_ends_with($name, 'icon.svg') && file_exists(dirname($path) . DIRECTORY_SEPARATOR . 'icon.svg')) {
                return $this->cache[$name] = dirname($path) . DIRECTORY_SEPARATOR . 'icon.svg';
            }
        }

        throw new \Twig\Error\LoaderError(sprintf('Unable to find template "%s" (looked into: %s).', $name,  implode(', ', $paths)));
    }
}
