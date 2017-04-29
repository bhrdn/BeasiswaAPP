<?php
namespace BeasiswaAPP;
class Autoloader
{
    private $dir, $prefix, $prefcount;

    public function __construct()
    {
        $this->dir       = __DIR__;
        $this->prefix    = __NAMESPACE__ . '\\';
        $this->prefcount = strlen($this->prefix);
    }

    public function autoload($a)
    {
        if (0 === strpos($a, $this->prefix)) {
            $pecah = explode('\\', substr($a, $this->prefcount));
            $path  = $this->dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $pecah) . '.php';

            if (is_file($path)) {
                require $path;
            }
        }
    }

    public static function reg($a = false)
    {
        spl_autoload_register(array(new self, 'autoload'), true, $a);
    }
}
