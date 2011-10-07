<?php

class Yokaze_Template
{
    private $templateDir = 'template';
    private $cacheDir = 'cache';
    private $ext = 'html';
    private $vars;
    public function __construct($templateDir = null, $cacheDir = null)
    {
        if ($templateDir)
            $this->templateDir = $templateDir;
        if ($cacheDir)
            $this->cacheDir;
        $this->vars = new StdClass();
    }
    public function setExtension($ext)
    {
        $this->ext = $ext;
    }
    public function setVars($vars)
    {
        $this->vars = $vars;
    }
    public function show($vars = null)
    {
        if ($vars === null)
            $vars = $this->vars;
        $file = basename($_SERVER['SCRIPT_FILENAME'], '.php') . '.' . $this->ext;
        $tmplFile = $this->templateDir . '/' . $file;
        $cacheFile = $this->cacheDir . '/' . $file;
        if (file_exists($cacheFile) && filemtime($tmplFile) <= filemtime($cacheFile)){
            $this->showCache($cacheFile, $vars);
            return;
        }

        $tmpl = file_get_contents($tmplFile);
        $tmpl = $this->compile($tmpl);
        file_put_contents($cacheFile, $tmpl);
        $this->showCache($cacheFile, $vars);
    }
    private function compile($tmpl)
    {
        // simple variables
        $tmpl = preg_replace('/(\{[[:alnum:]]+)\.([[:alnum:]]+\})/', '$1->$2', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*):h\}/', '<?php echo $$1; ?>', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*)\}/', '<?php echo htmlspecialchars($$1); ?>', $tmpl);


        /*
         * {loop:argName} feature
         */

        /* $tmpl = '<?php $c = 0; ?>' . $tmpl;
         * $tmpl = preg_replace('/\{loop:(.*?)\}/', '<?php $c++; ${\'b\' . $c} = clone $t; foreach($t->$1 as ${\'l\' . $c}){ foreach ( ${\'l\' . $c} as ${\'k\' . $c} => ${\'v\' . $c}){ $t->${\'k\' . $c} = ${\'v\' . $c}; } ?>', $tmpl);
         * $tmpl = preg_replace('/\{endloop:}/', '<?php } $t = ${\'b\' . $c}; $c--; ?>', $tmpl); */

        return $tmpl;
    }
    public function showCache($__cacheFile__, $__vars__)
    {
        foreach ($__vars__ as $k => $v)
            $$k = $v;
        include $__cacheFile__;
    }
}
