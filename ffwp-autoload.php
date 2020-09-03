<?php
/* * * * * * * * * * * * * * * * * * * * * *
 *   _       __      ____  _____ __  ____
 *  | |     / /___  / __ \/ ___// / / / /
 *  | | /| / / __ \/ / / /\__ \/ /_/ / /
 *  | |/ |/ / /_/ / /_/ /___/ / __  /_/
 *  |__/|__/\____/\____//____/_/ /_(_)
 *
 * @author   : Daan van den Bergh
 * @url      : https://ffwp.dev/wordpress-plugins/
 * @copyright: (c) 2020 Daan van den Bergh
 * @license  : GPL2v2 or later
 * * * * * * * * * * * * * * * * * * * * * */

class FFWP_Autoloader
{
    /** @var string $class */
    private $class;

    /** @var string $file */
    private $file;

    /**
     * FFWP_Autoloader constructor.
     *
     * @param $class
     */
    public function __construct(
        $class
    ) {
        $this->class = $class;

        $this->load();
    }

    /**
     * Build filepath for requested class.
     */
    public function load()
    {
        $path = explode('_', $this->class);
        $this->file = '';

        if (count($path) == 1) {
            if (ctype_upper($path[0])) {
                $this->file = 'class-' . strtolower(str_replace('_', '-', $this->class)) . '.php';
            } else {
                $parts = preg_split('/(?=[A-Z])/', lcfirst($path[0]));
                $this->file = 'class-' . strtolower(implode('-', $parts)) . '.php';
            }
        } else {
            array_shift($path);
            end($path);
            $i = 0;

            while ($i < key($path)) {
                $this->file .= strtolower($path[$i]) . '/';
                $i++;
            }

            // If entire part of path is written uppercase, we don't want to split.
            if (ctype_upper($path[$i])) {
                $pieces[] = $path[$i];
                // Words like OmgfPro or SuperStealth should be split up.
            } else {
                $pieces = preg_split('/(?=[A-Z])/', lcfirst($path[$i]));
            }

            $this->file .= 'class-' . strtolower(implode('-', $pieces)) . '.php';
        }

        return $this->file;
    }
}
