====================
Yokaze: PHP Framework
====================

Yokaze is simple framework that is developed with PHP5.


Usage
=====
Add ``cache`` directory in project directory::

   cd public_html
   mkdir cache
   chmod o+w cache

Include ``Template.php`` or ``Parser.php`` file::

   cat > index.php
   <?php
   require_once 'Yokaze/Parser.php';
   $t = new Yokaze_Parser();
   $vars = new StdClass();
   $vars->foo = 'World!';
   $t->show($vars);

Add template file of top page::

   mkdir template
   echo 'Hello {foo}' > template/index.html

Using Request class::
   require_once 'Yokaze/Request.php';
   $r = new Yokaze_Request($vars);
   $t->show($r);


