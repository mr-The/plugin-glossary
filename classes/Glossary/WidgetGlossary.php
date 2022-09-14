<?php

namespace Glossary;

use \Laminas\Router\Http\Regex;

class WidgetGlossary extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;

  protected $_params = array(
  'struct'         => '',
  'page_h1'        => '',
  'css_class'      => 'widget-glossary',
  'template'       => 'default.twig',
  );

  static public function initPage($glossaryCatalogs) {
    $router = \Cetera\Application::getInstance()->getRouter();
    for($i = 0; $i < count($glossaryCatalogs); $i++) {
      $url = $glossaryCatalogs[$i]->getUrl();
      $router->addRoute('glossary-' . (string)$i, Regex::factory([
        'regex' => $url . '?',
        'defaults' => ['controller' => '\Glossary\WidgetGlossary', 'action' => 'index'],
        'spec' => $url,
      ]), 1);
    }
  }

  static public function index($params) {
    $a = \Cetera\Application::getInstance();
    $catalog = $a->getCatalog();
    $materials = $catalog->getMaterials();

    $title = $catalog['meta_title'];
    $description = $catalog['meta_description'];
    $keywords = $catalog['meta_keywords'];

    if(!empty($title)) {
      $a->setPageProperty('title', $title);
      $a->addHeadString('<meta property="og:title" content="'.$title.'"/>', 'og:title');
    } else {
      $a->setPageProperty('title', "Глоссарий сайта");
      $a->addHeadString('<meta property="og:title" content="'."Глоссарий сайта".'"/>', 'og:title');
    }

    if(!empty($description)) {
      $a->setPageProperty('description', $description);
      $a->addHeadString('<meta property="og:description" content="'.htmlspecialchars($description).'"/>', 'og:description');
    } else {
      $a->setPageProperty('description', "Глоссарий сайта. Словарь терминов с их определением и ссылками на страницы сайта, на которых они упоминаются");
      $a->addHeadString('<meta property="og:description" content="'."Глоссарий сайта. Словарь терминов с их определением и ссылками на страницы сайта, на которых они упоминаются".'"/>', 'og:description');
    }

    if(!empty($keywords)) {
      $a->setPageProperty('keywords', $keywords);
    } else {
      $a->setPageProperty('keywords', "Глоссарий, словарь, термин, определение");
    }

    $a->getWidget('Glossary', array(
      'page_h1' => $catalog['name'],
      'struct' => self::createTemplateGlossaryData($materials)
    ))->display();
  }

  static protected function createTemplateGlossaryData($materials) {
    $result = [];
    for($i = 0; $i < count($materials); $i++) {
      $char = mb_strtoupper(mb_substr($materials[$i]['name'], 0, 1));
      $result[$char] = $result[$char] ?? ['char' => $char, 'data' => []];
      $result[$char]['data'][] = $materials[$i];
    }
    usort($result, fn($a, $b) => $a['char'] <=> $b['char']);

    return $result;
  }
}