<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgJshoppingFl_seo extends JPlugin {
	
	const REGEX = '/{(.*?)\:(.*?)}/i';
	
	protected $subcategories = array();
	
	// public function __construct(&$subject, $config)
	// {
		// parent::__construct($subject, $config);
	// }
	
	public function onBeforeDisplayMainCategory(&$category, &$categories, &$params)
	{
		$this->setSubcategories($categories);
		
		$this->setMetaTags('maincategory', $category);
	}
	
	public function onBeforeDisplayCategory(&$category, &$categories)
	{
		$props = array();
		$props['title'] = $category->name;
		$props['type'] = 'category';
		
		if($category->category_image)
		{
			$props['image'] = JSFactory::getConfig()->image_category_live_path .'/'. $category->category_image;
		}
		
		$this->setOpenGrathTags($props);
		
		$this->setSubcategories($categories);
		
		$this->setMetaTags('category', $category);
	}
	
	public function onBeforeDisplayProduct(&$product, &$view, &$images, &$videos, &$demofiles)
	{
		$props = array();
		$props['og:title'] = $product->name;
		$props['og:type'] = 'product';
		
		if($product->image)
		{
			$props['og:image'] = JSFactory::getConfig()->image_product_live_path .'/'. $product->image;
		}
		
		$this->setOpenGrathTags($props);
		
		$this->setMetaTags('product', $product);
	}
	
	function setOpenGrathTags($props)
	{
		$props['og:locale'] = str_replace('-', '_', JSFactory::getLang()->lang);
		$props['og:url'] = Juri::current();
		$props['og:site_name'] = JFactory::getConfig()->get('sitename');
		
		$doc = JFactory::getDocument();
		
		foreach($props as $prop => $content)
		{
			if($content)
			{
				$doc->setMetadata($prop, htmlspecialchars($content), 'property');
			}
		}
	}
	
	protected function setMetaTags($type, &$item)
	{
		$params = $this->params->get($type);
		
		if($params)
		{
			$isSet = 0;
			
			foreach($params as $key => $value)
			{
				if(($item->$key && $value->replace == 'write') || $value->string == '')
				{
					continue;
				}
				
				$method = 'set' . $type . 'Tag';
				
				$this->$method($key, $value, $item);
				
				$isSet++;
			}
			
			if($isSet)
			{
				setMetaData($item->meta_title, $item->meta_keyword, $item->meta_description);
			}
		}
	}
	
	protected function setSubcategories($subcategories)
	{
		foreach($subcategories as $subcategory)
		{
			$this->subcategories[] = $subcategory->name;
		}
	}
	
	protected function setMaincategoryTag(&$tag, &$params, &$category)
	{
		$this->setCategoryTag($tag, $params, $category);
	}
	
	protected function setCategoryTag(&$tag, &$params, &$category)
	{
		$string = $params->string;
		
		preg_match_all(self::REGEX, $string, $matches, PREG_SET_ORDER);
		
		if(count($matches))
		{
			foreach ($matches as $match)
			{
				$search = $match[0];
				
				switch($match[1])
				{
					case 'text':
						$replace = JText::_(trim($match[2]));
						break;
					case 'category':
						$replace = $category->{$match[2]};
						break;
					case 'subcategories':
						$replace = count($this->subcategories) ? implode(', ', $this->subcategories) : ($match[2] ? $match[2] : $category->name);
						break;
				}
				
				$string = str_replace($search, $replace, $string);
			}
		}
		
		$category->$tag = $string;
	}
	
	protected function setProductTag(&$tag, &$params, &$product)
	{
		static $category;
		
		$string = $params->string;
		
		preg_match_all(self::REGEX, $string, $matches, PREG_SET_ORDER);
		
		if(count($matches))
		{
			foreach ($matches as $match)
			{
				switch($match[1])
				{
					case 'text':
						$string = str_replace($match[0], JText::_(trim($match[2])), $string);
						break;
					case 'extra':
						$field = $product->extra_field[$match[2]]['value'];
						$string = str_replace($match[0], $field, $string);
						break;
					case 'product':
						$string = str_replace($match[0], $product->$match[2], $string);
						break;
					case 'manufacturer':
						if($product->manufacturer_info)
							$string = str_replace($match[0], $product->manufacturer_info->$match[2], $string);
						break;
					case 'vendor':
						if($product->vendor_info)
							$string = str_replace($match[0], $product->vendor_info->$match[2], $string);
						break;
					case 'category':
						if(!$category)
						{
							$category = JTable::getInstance('category', 'jshop');
							$category->load($product->getCategory());
							$category->name = $category->getName();
							$lang = JSFactory::getLang()->lang;
							$category->alias = $category->{'alias_' . $lang};
							$category->short_description = $category->{'short_description_' . $lang};
							$category->description = $category->{'description_' . $lang};
							$category->meta_title = $category->{'meta_title_' . $lang};
							$category->meta_description = $category->{'meta_description_' . $lang};
						}
						$string = str_replace($match[0], $category->$match[2], $string);
						break;
				}
			}
		}
		
		$product->$tag = $string;
	}
}