<?php
class IDGL_Node
{
	public $type, $attributes, $raw;

	public function IDGL_Node($nodeData)
	{
		$this -> type = $nodeData["_name"];
		$this -> attributes = $nodeData;
	}

	public function getType()
	{
		return $this -> type;
	}

	public function getName()
	{
		return $this -> attributes["name"];
	}

	public function renderAdmin($data = null, $name = null, $nodeID = null, $group_name = "")
	{
		//trim($data);
		$postID = $_GET["post"];
		if($data != null)
		{
			// ??
		}
		else
		{
			$value = get_post_meta($postID, "_IDGL_elem_" . $this -> attributes["name"], false);
			$data = $value[0];
		}
		if($this -> type == "TabBegin")
		{
			$title = "";
			
			if(isset($this -> attributes["title"]))
			{
				$title = $this -> attributes["title"];
			}
			return "<div class='IDGL_TabBegin' id='" . $this -> attributes["id"] . "_" . Util::getCounter() . "' title='{$title}' >";
		}
		else if($this -> type == "TabEnd")
		{
			return "</div>";
		}
		else if($this -> type == "fieldSetStart")
		{
			if($this -> attributes["legend"] != null)
			{
				$add = "<legend>" . $this -> attributes["legend"] . "</legend>";
			}
			return "<fieldset class='" . $this -> attributes["class"] . "'>" . $add;
		}
		else if($this -> type == "fieldSetEnd")
		{
			return "</fieldset>";
		}
		else if($this -> type == "clear")
		{
			return "<div style='clear:both'></div>";
		}
		else if($this -> type == "heading")
		{
			return "<h2>" . $this -> attributes["value"] . "</h2>";
		}
		else if($this -> type == "subheading")
		{
			return "<h4>" . $this -> attributes["value"] . "</h4>";
		}
		
		switch($this -> type)
		{
			case "html":
				$out = "<div class='IDGL_listNode'>";
				$out .= "<p>" . $this -> attributes["value"] . "</p>";
				$out .= "</div>";
				break;
			case "Text":
				$node_name = ($name != null) ? $name : "IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]";
				$node_id = ($nodeID != null) ? $nodeID : "IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]";
				$out = "<div class='IDGL_listNode'>";
				$out .= "
							<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>
								<input class='elem' type='text' name='" . $node_name . "' id='" . $node_id . "' value='" . htmlspecialchars($data, ENT_QUOTES) . "' />
						   	</div>
					   ";
				$out .= "</div>";
				break;
			case "Callback":
				eval('$temp=' . $this -> attributes["fname"] . '("' . $this -> attributes["params"] . '","' . $this -> attributes["name"] . '","' . $group_name . '");');
				$out .= $temp;
				break;
			
			case "DatePicker":
				$node_name = ($name != null) ? $name : "IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]";
				$out = "<div class='IDGL_listNode'>";
				$out .= "
							<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>
								<input class='elem' type='text' name='" . $node_name . "' value='" . $data . "' />
						   	</div>
					   ";
				$out .= "</div>";
				break;
			
			case "Color":
				$out = "<div class='IDGL_listNode'>";
				$out .= "
							<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>
								<input style='background-color:" . $data . ";' type='text' class='elem' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]' value='" . $data . "' />
						   		<img class='IDGL_img-color trigger' src='" . IDGL_THEME_URL . "/lib/images/color.png' alt='...' />
								<div class='colorpickerHolder'></div>
							</div>
					   ";
				$out .= "</div>";
				break;
			
			case "Image":
				$out = "<div class='IDGL_listNode'>";
				$out .= "<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>";
				
				$out .= "<div class='imageWrap' style='min-width:" . $this -> attributes["width"] . "px;min-height:" . $this -> attributes["height"] . "px;'>
					<img class='elem' src='" . $data . "' value='" . $data . "'  width='" . $this -> attributes["width"] . "' height='" . $this -> attributes["height"] . "'  _scalex='" . $this -> attributes["width"] . "' _scaley='" . $this -> attributes["height"] . "' />
						   		<div>
						   			<input type='hidden' class='hiddenFld' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]' value='" . $data . "' />
						   			<a id='" . $this -> attributes["name"] . "' class='button uploadify' href='#'>Browse Your PC</a>";
				if($data != "")
				{
					$out .= "<a class='button image_remove' href='#'>Remove Image</a>";
				}
				else
				{
					$out .= "<a style='display:none' class='button image_remove' href='#'>Remove Image</a>";
				}
				$out .= "</div></div>
					   ";
				$out .= "</div>";
				$out .= "</div>";
				break;
			case "ImageGallery":
				$out = "<div class='IDGL_listNode'>";
				$out .= "<div class='IDGL_" . $this -> type . "'>";
				
				$out .= "<span class='label'>" . $this -> attributes["label"] . "</span>";
				$out .= "<ol class='sortable'>";
				$count = 0;
				if(is_array($data))
				{
					foreach($data as $img)
					{
						$class = "";
						if($count == 0)
						{
							$out .= "<li><div class='imageWrap model' style='display:none;'>
								<img class='elem' src='' value=''  width='" . $this -> attributes["thumb_width"] . "' height='" . $this -> attributes["thumb_height"] . "'  _scalex='" . $this -> attributes["width"] . "' _scaley='" . $this -> attributes["height"] . "' />
						  			<div>
						  				<input type='hidden' class='hiddenFld' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "][]' value='' />
							   			<a class='button image_remove' href='#'>Remove Image</a>
						  			</div>
						  </div></li>";
						}
						$out .= "<li><div class='imageWrap " . $class . "'>
									<img class='elem' src='" . str_replace("img_", "thumb_img_", $img) . "' value='" . $img . "'  width='" . $this -> attributes["thumb_width"] . "' height='" . $this -> attributes["thumb_height"] . "'  _scalex='" . $this -> attributes["width"] . "' _scaley='" . $this -> attributes["height"] . "' />
							  			<div>
							  				<input type='hidden' class='hiddenFld' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "][]' value='" . $img . "' />
								   			<a class='button image_remove' href='#'>Remove Image</a>
							  			</div>
							  </div></li>";
						$count++;
					}
				}
				else
				{
					$out .= "<li><div class='imageWrap model' style='display:none;'>
								<img class='elem' src='' value=''  width='" . $this -> attributes["thumb_width"] . "' height='" . $this -> attributes["thumb_height"] . "'  _scalex='" . $this -> attributes["width"] . "' _scaley='" . $this -> attributes["height"] . "' />
						  			<div>
						  				<input type='hidden' class='hiddenFld' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "][]' value='' />
							   			<a class='button image_remove' href='#'>Remove Image</a>
						  			</div>
						  </div></li>";
				}
				$out .= "</ol>";
				$out .= "<a id='" . $this -> attributes["name"] . "' class='button uploadify' href='#'>Browse Your PC</a>";
				
				$out .= "</div>";
				$out .= "</div>";
				break;
			case "Video":
				$out = "<div class='IDGL_listNode'>";
				$out .= "<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>
								<div class='elem' src='" . $data . "' value='" . $data . "'></div>
								";
				
				$out .= "<div class='videoWrap'><div>
						   			<input type='hidden' class='hiddenFld' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]' value='" . $data . "' />
						   			<a id='" . $this -> attributes["name"] . "' class='button uploadify' href='#'>Browse Your PC</a>";
				if($data != "")
				{
					$out .= "<a class='button image_remove' href='#'>Remove Image</a>";
				}
				else
				{
					$out .= "<a style='display:none' class='button image_remove' href='#'>Remove Image</a>";
				}
				$out .= "</div></div>
					   ";
				$out .= "</div>";
				$out .= "</div>";
				
				break;
			case "Textarea":
				$out = "<div class='IDGL_listNode'>";
				$out .= "
							<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>
								<textarea class='elem' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]'>" . str_replace('"', '&quot;', $data) . "</textarea>
						   	</div>
					   ";
				$out .= "</div>";
				break;
			case "Wysiwyg":
				$out = "<div class='IDGL_listNode'>";
				$out .= "
							<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>
								
								<textarea style='min-height:100px;' class='elem theEditor' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]'>" . htmlspecialchars(nl2br($data), ENT_QUOTES) . "</textarea>
						   	</div>
					   ";
				$out .= "</div>";
				break;
			
			case "Select":
				$out = "<div class='IDGL_listNode'>";
				$out .= "
							<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>
								
								<select class='elem'  name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]' >";
				/*$nodeArr=$this->raw->children();
								foreach($nodeArr as $node){
									if(((string)$node["value"])==$data){
										$out.="<option selected='selected' >".$node["value"]."</option>";
									}else{
										$out.="<option>".$node["value"]."</option>";
									}
								}*/
				$out .= "</select>
								</div>";
				$out .= "</div>";
				break;
			
			case "GoogleMap":
				$out = "<div class='IDGL_listNode'>";
				$out .= "
							<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "</span>
								<input type='text' class='GooleMapNode elem' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]' value='" . $data . "' />
								<img class='trigger' src='" . IDGL_THEME_URL . "/lib/images/map-google.png' alt='...' />
							</div>
					   ";
				$out .= "</div>";
				break;
			case "Slider":
				$out = "<div class='IDGL_listNode'>";
				$out .= "
							<div class='IDGL_" . $this -> type . "'>
								<span class='label'>" . $this -> attributes["label"] . "<strong class='localValue'>" . $data . "</strong></span>
								<input type='hidden' class='IDGL_SliderValue elem' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]' value='" . $data . "' options='" . $this -> attributes["options"] . "' />
						   		<div class='slideHolder'></div>
							</div>
					   ";
				$out .= "</div>";
				break;
			
			case "Options":
				$out = "<div class='IDGL_listNode " . $this -> attributes["class"] . "'>
				<div class='IDGL_" . $this -> type . "'>
				<span class='label'>" . $this -> attributes["label"] . "</span>
				";
				switch($this -> attributes['type'])
				{
					case "select":
						$out .= "<select  class='elem' name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]'>";
						for($i = 0; $i < count($this -> attributes); $i++)
						{
							if($this -> attributes[$i]["value"] != "")
							{
								if($data == (string) $this -> attributes[$i]["value"])
								{
									$out .= "<option selected='selected' value='" . $this -> attributes[$i]["value"] . "' >" . $this -> attributes[$i]["label"] . "</option>";
								}
								else
								{
									$out .= "<option value='" . $this -> attributes[$i]["value"] . "' >" . $this -> attributes[$i]["label"] . "</option>";
								}
							}
						}
						$out .= "</select>";
						break;
					case "checkBoxList":
						//$out.="<select  class='elem' name='IDGL_elem[".$this->attributes["name"]."]'>";
						

						for($i = 0; $i < count($this -> attributes); $i++)
						{
							if($this -> attributes[$i]["value"] != "")
							{
								//error if empty
								if(is_array($data))
								{
									if(in_array((string) $this -> attributes[$i]["value"], $data))
									{
										$out .= "<input name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "][]' type='checkbox' checked='checked' value='" . $this -> attributes[$i]["value"] . "' />" . $this -> attributes[$i]["label"] . "<br/>";
									}
									else
									{
										$out .= "<input name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "][]' type='checkbox' value='" . $this -> attributes[$i]["value"] . "' />" . $this -> attributes[$i]["label"] . "<br/>";
									}
								}
								else
								{
									$out .= "<input name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "][]' type='checkbox' value='" . $this -> attributes[$i]["value"] . "' />" . $this -> attributes[$i]["label"] . "<br/>";
								}
							}
						}
						//$out.="</select>";
						break;
					case "radioList":
						for($i = 0; $i < count($this -> attributes); $i++)
						{
							if($this -> attributes[$i]["value"] != "")
							{
								//if(is_array($data)){
								if($this -> attributes[$i]["value"] == $data)
								{
									$out .= "<input name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]' type='radio' checked='checked' value='" . $this -> attributes[$i]["value"] . "' />" . $this -> attributes[$i]["label"];
								}
								else
								{
									$out .= "<input name='IDGL_elem" . $group_name . "[" . $this -> attributes["name"] . "]' type='radio' value='" . $this -> attributes[$i]["value"] . "' />" . $this -> attributes[$i]["label"];
								}
							
		//}else{
							//$out.="<input name='IDGL_elem[".$this->attributes["name"]."]' type='radio' value='".$this->attributes[$i]["value"]."' />".$this->attributes[$i]["label"];
							//}
							}
						}
						break;
				}
				$out .= "</div></div>";
				
				break;
		
		}
		//echo $this->type."<br/>";
		return $out;
	}

	public static function getDataFiles($componentName, $selected)
	{
		return Util::generateDropDown(File::getFileList(DATA_PATH . $componentName), "dataFile", $selected);
	}

	public static function getDataArray($componentName)
	{
		return File::getFileList(DATA_PATH . $componentName);
	}

	public static function newDataFile($componentName, $newFileName)
	{
		return File::create(DATA_PATH . $componentName . "/" . $newFileName);
	}

	public static function deleteDataFile($componentName, $newFileName)
	{
		return File::delete(DATA_PATH . $componentName . "/" . $newFileName);
	}

	public static function getTemplateArray($componentName)
	{
		return File::getFileList(TEMPLATE_PATH . $componentName);
	}

	public static function getTemplateFiles($componentName, $selected)
	{
		return Util::generateDropDown(File::getFileList(TEMPLATE_PATH . $componentName), "templateFile", $selected);
	}
}
?>