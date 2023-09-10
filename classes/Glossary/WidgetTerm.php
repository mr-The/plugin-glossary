<?php

namespace Glossary;

use \Laminas\Router\Http\Segment;

class WidgetTerm extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;

  protected $_params = array(
  'material'       => '',
  'links'          => '',
  'css_class'      => 'widget-glossary-term',
  'template'       => 'default.twig',
  );

  static public function initPage($glossaryCatalogs) {
    $router = \Cetera\Application::getInstance()->getRouter();
    for($i = 0; $i < count($glossaryCatalogs); $i++) {
      $url = $glossaryCatalogs[$i]->getUrl();
      $router->addRoute('glossary_term-' . (string)$i, Segment::factory([
        'route' => $url . ':id[/]',
        'defaults' => ['controller' => '\Glossary\WidgetTerm', 'action' => 'index'],
      ]), 1);    
    }
  }

  static public function index($params) {
    $alias = $params['id'];
    $a = \Cetera\Application::getInstance();
    $iterator = $a->getCatalog()->getMaterials();
    $materials = $iterator->where('alias=:d')->setParameter(':d', $alias);

    if(count($materials) === 0) {
      $twig = $a->getTwig()->display('page_section.twig', []);
      return;
    }

    $termMaterial = $materials[0];

    $slot = new \Cetera\Cache\Slot\User($termMaterial->getUrl());
    $cachedLinks = $slot->load();

    if ($cachedLinks === false) {
      $links = self::findTermReference($termMaterial);
      $slot->save($links);
    }

    $title = $termMaterial['meta_title'];
    $description = $termMaterial['meta_description'];
    $keywords = $termMaterial['meta_keywords'];
    $termName = $termMaterial['name'];

    if(!empty($title)) {
      $a->setPageProperty('title', $title);
      $a->addHeadString('<meta property="og:title" content="'.$title.'"/>', 'og:title');
    } else {
      $a->setPageProperty('title', "Термин &laquo;$termName&raquo;");
      $a->addHeadString('<meta property="og:title" content="'."Термин &laquo;$termName&raquo;".'"/>', 'og:title');
    }

    if(!empty($description)) {
      $a->setPageProperty('description', $description);
      $a->addHeadString('<meta property="og:description" content="'.htmlspecialchars($description).'"/>', 'og:description');
    } else {
      $a->setPageProperty('description', "Глоссарий сайта. Страница термина &laquo;$termName&raquo;");
      $a->addHeadString('<meta property="og:description" content="'."Глоссарий сайта. Страница термина &laquo;$termName&raquo;".'"/>', 'og:description');
    }

    if(!empty($keywords)) {
      $a->setPageProperty('keywords', $keywords);
    } else {
      $a->setPageProperty('keywords', "Глоссарий, термин, $termName");
    }

    $a->getWidget('Term', array(
      'material'      => $termMaterial,
      'links'         => $cachedLinks ?: $links
      ))->display();
  }

  static protected function findTermReference($termMaterial) {
    $termAndSynonyms = empty($termMaterial['synonyms']) ? [$termMaterial['name']] : [$termMaterial['name'], ...mb_split(", ?", $termMaterial['synonyms'])];
    $mainCatalog = \Cetera\Application::getInstance()->getServer();
    $links = self::findReferenceInChildrenCatalogs($mainCatalog, $termAndSynonyms, []);

    return $links;
  }

  static protected function findReferenceInMaterials($catalog, $termAndSynonyms, $links) {
    $materials = $catalog->getMaterials();
    for($i = 0; $i < count($materials); $i++) {
      $html = $materials[$i]['text'];
      foreach($termAndSynonyms as $term) {

        if(mb_stripos($html, $term) === false)
          continue;

        $onlyText = implode("", mb_split("</?.*?>", $html));
        $regExp = PageHandler::termFindRegExp($term);
  
        if(preg_match($regExp, $onlyText) === 1) {
          $links[] = ['title' => $materials[$i]['name'], 'link' => $materials[$i]->getUrl()];
          break;
        }
      }
    }
    return $links;
  }

  static public function findReferenceInChildrenCatalogs($catalog, $termAndSynonyms, $links) {
    $childrenCatalogs = $catalog->getChildren();
    for($i = 0; $i < count($childrenCatalogs); $i++) {
      $links = self::findReferenceInMaterials($childrenCatalogs[$i], $termAndSynonyms, $links);
      $links = self::findReferenceInChildrenCatalogs($childrenCatalogs[$i], $termAndSynonyms, $links);
    }
    return $links;
  }

  static public function clearCache() {
    $cacheStorage = new \Laminas\Cache\Storage\Adapter\Filesystem([
		'cache_dir'=>FILECACHE_DIR,
		'ttl'=>3600 * 24,
	]);
	$cacheStorage->clearExpired();
  }
}