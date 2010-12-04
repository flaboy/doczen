<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    
    <title><?php echo $data['title'];
	if($data['title'] != $data['index'][1]){
		echo ' &mdash; ',$data['index'][1];
	}
 	?>
	</title>
    <link rel="stylesheet" href="<?php echo $data['static']; ?>/default.css" type="text/css" />
    <link rel="stylesheet" href="<?php echo $data['static']; ?>/pygments.css" type="text/css" />
    <link rel="top" title="<?php echo $data['index'][1]; ?>" href="<?php echo $data['index'][0]; ?>" />
    <link rel="up" title="<?php echo $data['nav'][count($data['nav'])-1][1]; ?>" href="<?php echo $data['nav'][count($data['nav'])-1][0]; ?>" />

<?php if($data['next']){ ?>
    <link rel="next" title="<?php echo $data['next'][0]; ?>" href="<?php echo $data['next'][1]; ?>.html" /> 
<?php } if($data['prev']){ ?>
    <link rel="prev" title="<?php echo $data['prev'][0]; ?>" href="<?php echo $data['prev'][1]; ?>.html" /> 
<?php } ?>
  </head>
  <body>
	<!-- insert your head here -->
    <?php ob_start(); ?>
    <div class="related">
      <h3>导航</h3>
	<ul>
        <li class="right" style="margin-right: 10px">
          <a href="<?php echo $data['index'][0]; ?>" title="总目录"
             accesskey="I">索引</a></li>
<?php if($data['next']){ ?>
	<li class="right" >
	  <a href="<?php echo $data['next'][0]; ?>.html" title="<?php echo $data['next'][1]; ?>"
	     accesskey="N">下一页</a> |</li>
<?php } if($data['prev']){ ?>
	<li class="right" >
	  <a href="<?php echo $data['prev'][0]; ?>.html" title="<?php echo $data['prev'][1]; ?>"
	     accesskey="P">上一页</a> |</li>
<?php } ?>
		
		<?php foreach($data['nav'] as $item){ ?>
	      <li><a href="<?php echo $item[0]; ?>"><?php echo $item[1]; ?></a> &raquo;</li>
		<?php } ?>
      </ul>
    </div>
<?php $navbar = ob_get_contents();ob_end_flush(); ?> 

    <div class="document">
      <div class="documentwrapper">
        <div class="bodywrapper">
          <div class="body">

            <h1> <?php echo $data['title']; ?></h1>

 <?php echo $data['body']; ?>

          </div>
        </div>
      </div>
      <div class="doczensidebar">
        <div class="doczensidebarwrapper">
  <h3><a href="<?php echo $data['index'][0]; ?>">內容目录</a></h3>
<!--  <ul>
<li><a class="reference internal" href="#">base</a><ul>
<li><a class="reference internal" href="#reference">Reference</a><ul>
</ul> -->

<?php echo $data['toc']; ?>

</li>
</ul>
</li>
</ul>

<?php if($data['prev']){ ?>
  <h4>上一个主题</h4>
  <p class="topless"><a href="<?php echo $data['prev'][0]; ?>.html"
                        title="上一章"><?php echo $data['prev'][1]; ?></a></p>
<?php } if($data['next']){ ?>
  <h4>下一个主题</h4>
  <p class="topless"><a href="<?php echo $data['next'][0]; ?>.html"
                        title="下一章"><?php echo $data['next'][1]; ?></a></p>
<?php } ?>

<div id="searchbox" style="display: none">
  <h3>快速搜索</h3>
    <form class="search" action="../../../../search.html" method="get">
      <input type="text" name="q" size="18" />
      <input type="submit" value="搜索" />
      <input type="hidden" name="check_keywords" value="yes" />
      <input type="hidden" name="area" value="default" />
    </form>
    <p class="searchtip" style="font-size: 90%">
    输入相关的模块，术语，类或者函数名称进行搜索
    </p>
</div>
<script type="text/javascript">$('#searchbox').show(0);</script>
        </div>
      </div>
      <div class="clearer"></div>
    </div>
<?php echo $navbar; ?>
    <div class="footer">
      <?php echo doczen_utils::poweredby(); ?>
    </div>
  </body>
</html>