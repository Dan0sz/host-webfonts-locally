<?php
/* * * * * * * * * * * * * * * * * * * * * *
 * @author   : Daan van den Bergh
 * @url      : https://ffw.press/wordpress-plugins/
 * @copyright: (c) 2021 Daan van den Bergh
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
    }

    /**
     * Build filepath for requested class.
     */
    public function load()
    {
        $path       = explode('_', $this->class);
        $this->file = '';
        $i          = 0;

        if (count($path) > 1) {
            array_shift($path);
        }
        end($path);

        /**
         * Build directory path.
         */
        while ($i < key($path)) {
            $this->build($path[$i], '', '/');

            $i++;
        }

        /**
         * Build filename.
         */
        $this->build($path[$i], 'class', '.php');

        return $this->file;
    }

    /**
     * Checks if $path is written uppercase entirely, otherwise it'll split $path up and build a string glued with
     * dashes.
     *
     * @param        $path
     * @param string $prefix
     * @param string $suffix
     */
    private function build($path, $prefix = '', $suffix = '/')
    {
        if (ctype_upper($path)) {
            $this->file .= ($prefix ? $prefix . '-' : '') . strtolower($path) . $suffix;
        } else {
            $parts = preg_split('/(?=[A-Z])/', lcfirst($path));
            $this->file .= ($prefix ? $prefix . '-' : '') . strtolower(implode('-', $parts)) . $suffix;
        }
    }
}
