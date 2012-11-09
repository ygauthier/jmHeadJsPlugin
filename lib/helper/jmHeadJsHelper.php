<?php

// Helper to use HeadJs for Javascript loading
function jm_get_headjs()
{
  $path = sfConfig::get('app_jm_headjs', '/jmHeadJsPlugin/js/head.js');
  return javascript_include_tag($path);
}

function jm_include_headjs()
{
  echo jm_get_headjs();
}

function jm_include_javascripts()
{
  echo jm_get_javascripts();
}

function jm_get_javascripts()
{
  sfContext::getInstance()->getConfiguration()->loadHelpers(array('JavascriptBase'));
  $response = sfContext::getInstance()->getResponse();
  $javascripts = $response->getJavascripts();
  $tmp = array();
  if (sfConfig::get('app_a_minify', false))
  {
    $tmp = _jm_get_assets_body($javascripts);
  }
  else
  {
    foreach ($javascripts as $source => $sourceOptions)
    {
      $tmp[] = _jm_get_source($source, $sourceOptions);
    }
  }
  
  $javascripts = implode(', ', $tmp);
  return javascript_tag('head.js(' . $javascripts . ');');
}

function jm_get_js_calls()
{
  $html = '';
  if (count(aTools::$jsCalls))
  {
    $html .= '<script type="text/javascript">' . "\n";
    $html .= 'head.ready(function() {' . "\n";
    $html .= '$(function() {' . "\n";
    $html .= a_get_js_calls_only();
    $html .= '});' . "\n";
    $html .= '});' . "\n";
    $html .= '</script>' . "\n";
  }
  return $html;
}

function jm_include_js_calls()
{
  echo(jm_get_js_calls());
}

function jm_include_js_calls_only()
{
  echo('<script type="text/javascript">');
  echo('head.ready(function(){');
  echo(a_get_js_calls_only());
  echo('});');
  echo('</script>');
}

function _jm_get_source($source, $sourceOptions)
{
  $absolute = false;
  if (isset($sourceOptions['absolute']))
  {
    unset($sourceOptions['absolute']);
    $absolute = true;
  }

  $condition = null;
  if (isset($sourceOptions['condition']))
  {
    $condition = $sourceOptions['condition'];
    unset($sourceOptions['condition']);
  }

  if (!isset($sourceOptions['raw_name']))
  {
    $source = javascript_path($source, $absolute);
  }
  else
  {
    unset($sourceOptions['raw_name']);
  }
  return "'$source'";
}

function _jm_get_assets_body($assets)
{
  $gzip = sfConfig::get('app_a_minify_gzip', false);
  sfConfig::set('symfony.asset.javscripts_included', true);

  $includes = array();

  $html = '';
  $sets = array();
  foreach ($assets as $file => $options)
  {
    if (preg_match('/^http(s)?:/', $file) || (isset($options['data-minify']) && $options['data-minify'] === 0))
    {
      // Nonlocal URL or minify was explicitly shut off. 
      // Don't get cute with it, otherwise things
      // like Addthis and ckeditor don't work
      //$html .= javascript_include_tag($file, $options);
      $includes[] = _jm_get_source($file, $options);
      continue;
    }
    /*
     *
     * Guts borrowed from stylesheet_tag and javascript_tag. We still do a tag if it's
     * a conditional stylesheet
     *
     */

    $absolute = false;
    if (isset($options['absolute']) && $options['absolute'])
    {
      unset($options['absolute']);
      $absolute = true;
    }

    $condition = null;
    if (isset($options['condition']))
    {
      $condition = $options['condition'];
      unset($options['condition']);
    }

    if (!isset($options['raw_name']))
    {
      $file = javascript_path($file, $absolute);
    }
    else
    {
      unset($options['raw_name']);
    }

    if (is_null($options))
    {
      $options = array();
    }
    $options = array_merge(array('type' => 'text/javascript', 'src' => $file), $options);

    unset($options['href'], $options['src']);
    $optionGroupKey = json_encode($options);
    $set[$optionGroupKey][] = $file;
    // echo($file);
    // $html .= "<style>\n";
    // $html .= file_get_contents(sfConfig::get('sf_web_dir') . '/' . $file);
    // $html .= "</style>\n";
  }

  // CSS files with the same options grouped together to be loaded together

  foreach ($set as $optionsJson => $files)
  {
    $groupFilename = aAssets::getGroupFilename($files);
    $groupFilename .= '.js';
    if ($gzip)
    {
      $groupFilename .= 'gz';
    }
    $dir = aFiles::getUploadFolder(array('asset-cache'));
    if (!file_exists($dir . '/' . $groupFilename))
    {
      $content = '';
      foreach ($files as $file)
      {
        $path = null;
        if (sfConfig::get('app_a_stylesheet_cache_http', false))
        {
          $url = sfContext::getRequest()->getUriPrefix() . $file;
          $fileContent = file_get_contents($url);
        }
        else
        {
          $path = sfConfig::get('sf_web_dir') . $file;
          $fileContent = file_get_contents($path);
        }


        // Trailing carriage return makes behavior more consistent with
        // JavaScript's behavior when loading separate files. For instance,
        // a missing trailing semicolon should be tolerated to the same
        // degree it would be with separate files. The minifier is not
        // a lint tool and should not surprise you with breakage
        $fileContent = JSMin::minify($fileContent) . "\n";

        $content .= $fileContent;
      }
      if ($gzip)
      {
        _gz_file_put_contents($dir . '/' . $groupFilename . '.tmp', $content);
      }
      else
      {
        file_put_contents($dir . '/' . $groupFilename . '.tmp', $content);
      }
      @rename($dir . '/' . $groupFilename . '.tmp', $dir . '/' . $groupFilename);
    }
    $options = json_decode($optionsJson, true);

    $includes[] = "'".javascript_path(sfConfig::get('app_a_assetCacheUrl', '/uploads/asset-cache') . '/' . $groupFilename)."'";
  }
  return $includes;
}