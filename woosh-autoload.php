<?php
/* * * * * * * * * * * * * * * * * * * * * *
 *   _       __      ____  _____ __  ____
 *  | |     / /___  / __ \/ ___// / / / /
 *  | | /| / / __ \/ / / /\__ \/ /_/ / /
 *  | |/ |/ / /_/ / /_/ /___/ / __  /_/
 *  |__/|__/\____/\____//____/_/ /_(_)
 *
 * @author   : Daan van den Bergh
 * @url      : https://woosh.dev/wordpress-plugins/
 * @copyright: (c) 2020 Daan van den Bergh
 * @license  : GPL2v2 or later
 * * * * * * * * * * * * * * * * * * * * * */

class Woosh_Autoloader
{
    /** @var string $class */
    private $class;

    /** @var string $file */
    private $file;

    /**
     * Woosh_Autoloader constructor.
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
            $this->file = 'class-' . strtolower(str_replace('_', '-', $this->class)) . '.php';
        } elseif (count($path) == 2) {
            array_shift($path);
            $this->file = 'class-' . strtolower($path[0]) . '.php';
        } else {
            array_shift($path);
            end($path);
            $i = 0;

            while ($i < key($path)) {
                $this->file .= strtolower($path[$i]) . '/';
                $i++;
            }

            $pieces = preg_split('/(?=[A-Z])/', lcfirst($path[$i]));

            $this->file .= 'class-' . strtolower(implode('-', $pieces)) . '.php';
        }

        return $this->file;
    }
}
