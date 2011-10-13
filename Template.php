<?php

class Yokaze_Template
{
    protected $templateDir = 'template';
    protected $cacheDir = 'cache';
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

        $this->compile($tmplFile, $cacheFile);

        $this->showCache($cacheFile, $vars);
    }
    protected function compile($tmplFile, $cacheFile)
    {
        if (file_exists($cacheFile) && filemtime($tmplFile) <= filemtime($cacheFile)){
            return;
        }

        $tmpl = file_get_contents($tmplFile);

        // include feature
        $incPattern = '|{include:([[:alnum:]/]+\.html)}|';
        if (preg_match($incPattern, $tmpl)){
            $incReplace =
                '<?php $this->compile(\'' .
                $this->templateDir . '/$1\', \'' .
                $this->cacheDir . '/$1\'' . ');' .
                ' include \'' . $this->cacheDir . '/' . '$1\'; ?>';
            $tmpl = preg_replace($incPattern, $incReplace, $tmpl);
        }

        // simple variables
        $tmpl = preg_replace('/(\{[[:alnum:]]+)\.([[:alnum:]]+(:[a-z]+)?\})/', '$1->$2', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*):h\}/', '<?php echo $$1; ?>', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*):n\}/', '<?php echo nl2br(htmlspecialchars($$1)); ?>', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*)\}/', '<?php echo htmlspecialchars($$1); ?>', $tmpl);

        file_put_contents($cacheFile, $tmpl);
    }
    private function showCache($__cacheFile__, $__vars__)
    {
        foreach ($__vars__ as $k => $v)
            $$k = $v;
        include $__cacheFile__;
    }
}
