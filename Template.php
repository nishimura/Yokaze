<?php

class Yokaze_Template
{
    protected $templateDir = 'template';
    protected $cacheDir = 'cache';
    private $ext = 'html';
    private $vars;
    private $file;
    public function __construct($templateDir = null, $cacheDir = null)
    {
        if ($templateDir)
            $this->templateDir = $templateDir;
        if ($cacheDir)
            $this->cacheDir = $cacheDir;

        if (!is_writeable($this->cacheDir))
            throw new RuntimeException("$this->cacheDir directory is not writeable.");

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

    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }
    public function getFile()
    {
        if ($this->file !== null)
            return $this->file;
        return basename($_SERVER['SCRIPT_FILENAME'], '.php') . '.' . $this->ext;
    }
    public function show($vars = null)
    {
        if ($vars === null)
            $vars = $this->vars;
        $file = $this->getFile();
        $tmplFile = $this->templateDir . '/' . $file;
        $cacheFile = $this->cacheDir . '/' . $file;

        if (!$this->compile($tmplFile, $cacheFile))
            return;

        $this->showCache($cacheFile, $vars);
    }

    public function get($vars = null)
    {
        ob_start();
        $this->show($vars);
        return ob_get_clean();
    }

    protected function show404()
    {
        header($_SERVER['SERVER_PROTOCOL']. " 404 Not Found");

        echo <<<END
            <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
            <html><head>
            <title>404 Not Found</title>
            </head><body>
            <h1>Not Found</h1>
            <p>The requested URL was not found on this server.</p>
            <hr>
            <address>Yokaze-template</address>
END;
    }
    protected function compile($tmplFile, $cacheFile)
    {
        if (!file_exists($tmplFile)){
            if (file_exists($cacheFile))
                unlink($cacheFile);
            $this->show404();
            return false;
        }

        if (file_exists($cacheFile) && filemtime($tmplFile) <= filemtime($cacheFile)){
            return true;
        }

        $tmpl = $this->compileInternal($tmplFile, $cacheFile);

        $this->file_force_contents($cacheFile, $tmpl);
        return true;
    }
    protected function compileInternal($tmplFile, $cacheFile)
    {
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
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*):b\}/', '<?php echo nl2br(htmlspecialchars($$1)); ?>', $tmpl);
        $tmpl = preg_replace('/\{([[:alnum:]_>-]*)\}/', '<?php echo htmlspecialchars($$1); ?>', $tmpl);

        return $tmpl;
    }

    // http://php.net/function.file-put-contents.php#84180
    protected function file_force_contents($path, $contents,
                                           $flag = 0, $context = null)
    {
        $parts = explode('/', $path);
        $file = array_pop($parts);
        $dir = '.';
        foreach($parts as $part)
            if(!is_dir($dir .= "/$part")) mkdir($dir);
        return file_put_contents("$dir/$file", $contents, $flag, $context);
    }
    private function showCache($__cacheFile__, $__vars__)
    {
        foreach ($__vars__ as $k => $v)
            $$k = $v;
        include $__cacheFile__;
    }
}
