<?php

$this->registerWidget(array(
    'name'    => 'Glossary',
    'class'   => '\\Glossary\\WidgetGlossary',
    'not_placeable' => true,
));

$this->registerWidget(array(
    'name'    => 'Term',
    'class'   => '\\Glossary\\WidgetTerm',
    'not_placeable' => true,
));

\Cetera\Event::attach(EVENT_CORE_MATERIAL_AFTER_SAVE, function($event, $data){
	\Glossary\WidgetTerm::clearCache($data);
});

if($this->isFrontOffice()) {
    $typeId = \Cetera\ObjectDefinition::findByAlias('glossary')->getId();
    $glossaryCatalogs = \Cetera\ObjectDefinition::findById($typeId)->getCatalogs();
    \Glossary\WidgetGlossary::initPage($glossaryCatalogs);
    \Glossary\WidgetTerm::initPage($glossaryCatalogs);
    \Glossary\PageHandler::init($glossaryCatalogs);
}
