<?php

require_once 'Template.php';

class Yokaze_Parser extends Yokaze_Template
{
    private $tagOpened = false;
    private $attrOpend = false;
    private $behaviors = array('b' => 'nl2br',
                               'n' => 'number_format',
                               'u' => 'urlencode');
    private $plains = array('h', 'a');
    private $tagStack = array();
    public function __construct($templateDir = null, $cacheDir = null)
    {
        parent::__construct($templateDir, $cacheDir);
    }

    public function addBehavior($char, $callback, $isPlain = false)
    {
        $this->behaviors[$char] = $callback;
        if ($isPlain)
            $this->plains[] = $char;
    }
    private function parse($buf)
    {
        $length = strlen($buf);
        $ret = '';
        for ($i = 0; $i < $length;){
            $char = $buf[$i];

            if ($char === '<'){
                list($parsed, $len) = $this->parseTag(substr($buf, $i));
            }else if ($char === '{'){
                list($parsed, $len) = $this->parseVal(substr($buf, $i));
            }else{
                $parsed = $char;
                $len = 1;
            }
            $ret .= $parsed;
            $i += $len;
        }
        return $ret;
    }

    private function parseAttr($tagName, $close, $buf)
    {
        if (!preg_match("/^$close([^$close]+)$close/", $buf, $matches))
            return array($buf[0], 1);

        $val = $matches[1];
        $length = strlen($val);

        $ret = '';
        $append = null;
        $loop = '/^loop:([[:alnum:].]+):([[:alnum:]]+)(:[[:alnum:]]+)?/';
        $if = '/^if(el)?:([[:alnum:].<>=\(\)$-]+)/';
        for ($i = 0; $i < $length;){
            $char = $val[$i];

            $sub = substr($val, $i);
            if (!$append && preg_match($loop, $sub, $m)){

                $ite = str_replace('.', '->', $m[1]);
                if (isset($m[3])){
                    $v = ltrim($m[3], ':');
                    $append = "<?php foreach(\$$ite as \$$m[2]=>\$$v): ?> ";
                }else{
                    $append = "<?php foreach(\$$ite as \$$m[2]): ?> ";
                }
                $parsed = '';
                $len = strlen($m[0]);
                $this->tagStack[count($this->tagStack)-1]['php'] = 'endforeach';

            }else if (!$append && preg_match($if, $sub, $m)){
                if ($m[1]){
                    $this->tagStack[count($this->tagStack)-1]['php'] = 'else:';
                }else{
                    $this->tagStack[count($this->tagStack)-1]['php'] = 'endif';
                }
                $v = str_replace('.', '->', $m[2]);
                $append = "<?php if ($v): ?>";
                $parsed = '';
                $len = strlen($m[0]);

            }else if (!$append && preg_match('/^else:/', $sub, $m)){
                $this->tagStack[count($this->tagStack)-1]['php'] = 'endif';
                $parsed = '';
                $len = strlen($m[0]);

            }else if ($char === '{'){
                list($parsed, $len) = $this->parseVal(substr($val, $i));

            }else{
                $parsed = $char;
                $len = 1;
            }
            $ret .= $parsed;
            $i += $len;
        }

        return array($close . $ret . $close, $length + 2, $append);
    }

    private function popTag($closeTag)
    {
        $index = count($this->tagStack) - 1;
        for ($i = $index; $i >= 0; $i--){
            $tag = array_pop($this->tagStack);
            if ($tag['tag'] === $closeTag){
                if ($tag['php'])
                    return '<?php ' . $tag['php'] . ' ?>';
                else
                    return '';
            }
        }
        return '';
    }
    private function parseTag($buf)
    {
        if (preg_match('|^</ *([[:alnum:]]+) *>|', $buf, $matches)){
            // close tag
            $tagName = $matches[1];
            $append = $this->popTag($tagName);
            return array($matches[0] . $append, strlen($matches[0]));
        }
        if (!preg_match('/^< *([[:alnum:]]+)/', $buf, $matches))
            return array($buf[0], 1);

        $tagName = $matches[1];
        $this->tagStack[] = array('tag' => $tagName, 'php' => null);

        $length = strlen($buf);
        $ret = '';
        for ($i = 0; $i < $length;){
            $char = $buf[$i];
            if ($char === '"' || $char === "'"){
                list($parsed, $len, $append) =
                    $this->parseAttr($tagName, $char, substr($buf, $i));
                if ($append)
                    $ret = $append . $ret;

            }else if ($char === '{'){
                list($parsed, $len) = $this->parseVal(substr($buf, $i));

            }else if ($char === '>'){
                $ret .= $char;
                $i++;
                break;
            }else{
                $parsed = $char;
                $len = 1;
            }
            $ret .= $parsed;
            $i += $len;
        }
        return array($ret, $i);
    }
    private function parseVal($buf)
    {
        if (!preg_match('/^{([[:alnum:]\.:_]+)}/', $buf, $matches))
            return array($buf[0], 1);

        $name = $matches[1];
        $len = strlen($name) + 2;

        // special replacement
        if (preg_match('|^include:([[:alnum:]/]+\.html)|', $name, $matches)){
            $file = $matches[1];
            $templateFile = $this->templateDir . "/$file";
            $cacheFile = $this->cacheDir . "/$file";
            $ret = '<?php $__yokazeParser__ = new Yokaze_Parser();'
                . '$__yokazeParser__->compile('
                . "'$templateFile', '$cacheFile');"
                . "include '$cacheFile'; ?>";
            return array($ret, $len);
        }

        // variables replacement
        $name = str_replace('.', '->', $name);
        if (strpos($name, ':') !== false){
            list($name, $behaviors) = explode(':', $name);
            $val = '$' . $name;

            $isPlain = false;
            foreach ($this->plains as $p){
                if (strpos($behaviors, $p) !== false){
                    $isPlain = true;
                    break;
                }
            }
            if (!$isPlain)
                $val = 'htmlspecialchars($' . $name . ')';

            foreach ($this->behaviors as $behavior => $callback){
                if (strpos($behaviors, $behavior) !== false){
                    $val = $callback . '(' . $val . ')';
                }
            }
        }else {
            $val = 'htmlspecialchars($' . $name . ')';
        }
        $ret = '<?php echo ' .  $val . '; ?>';
        return array($ret, $len);
    }
    protected function compile($tmplFile, $cacheFile)
    {
        if (file_exists($cacheFile) && filemtime($tmplFile) <= filemtime($cacheFile)){
            return;
        }

        $tmpl = file_get_contents($tmplFile);
        $tmpl = $this->parse($tmpl);
        file_put_contents($cacheFile, $tmpl);
    }
}
