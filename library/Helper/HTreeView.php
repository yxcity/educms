<?php
/**
 *1.与树结点层级显示相关的处理类
 *2.所有递归输入输出相关的处理均可存放于此
 *3.该类为Controller|Model提供辅助静态方法,目的是保持Controller|Model的纯洁性及代码的可重用性 
 */
namespace library\Helper;

class HTreeView{
   /**
     * 生成权限勾选树
     * 
     * @param undefined $options
     *        	用于选择的权限范围
     * @param undefined $existed
     *        	当前已经拥有的权限
     *        	
     */
    public static function genAclCheckTree($options, $existed = array()) {
        $html = "<ul>";
        if (empty($options)) {
            return $html;
        }
        foreach ($options as $opt) {
            $checked = in_array($opt ['acl_id'], $existed) ? "checked='checked'" : "";
            $class = ($opt ['parent_id'] > 0 && count($opt ['sub_tree']) == 0) ? "sub_item" : "item";
            $html .= "<li class='$class'><input type='checkbox' name='acl_id[]' $checked value='" . $opt ['acl_id'] . "'/> " . $opt ['acl_name'];
            if (count($opt ['sub_tree']) > 0) {
                $html .= self::genAclCheckTree($opt ['sub_tree'], $existed);
            }
            $html .= "</li>";
        }
        $html .= "</ul>";
        return $html;
    }

    /**
     * 生成分类的节点树结构
     *
     * @param undefined $rows        	
     * @param $defOpt 选择项的默认值        	
     * @param $this_id 当前分类自身的ID        	
     * @return html
     */
    public static function getTypeTree($rows, $name = "pid", $id = "pid", $defOpt = NULL, $this_id = NULL,$classid = 0,$deepIdx = 0) {
        $select = "<select name='$name' id='$id'>";
		if($classid != 11){//不为新闻分类时才有此选择项,否则直接显示顶级分类[公司动态]
			$select .= "<option value='0'>--请选择--</option>";	
		}
        $select .= self::genOptionHtml($rows, $deepIdx, $defOpt, $this_id);
        $select .= "</select>";
        return $select;
    }

    /**
     *
     * @param undefined $rows        	
     * @param undefined $deepIdx        	
     * @param undefined $defOpt
     *        	选择项的默认值
     * @param undefined $this_id
     *        	当前分类自身的ID
     *        	
     */
    public static function genOptionHtml($rows, $deepIdx = 0, $defOpt = NULL, $this_id = NULL) {
        $option = "";
        if (empty($rows)) {
            return $option;
        }
        $se = self::genLayerDiv($deepIdx);
        foreach ($rows as $r) {
            $name = $r ['is_child'] ? $se . $r ['name'] : $r ['name'];
            $selected = $r ['id'] == $defOpt ? "selected='selected'" : "";
            if ($r ['id'] == $this_id) {
                continue;
            }
			if($r['id'] == 60){//60-公司动态-新闻分类下的一级分类
				$option .= "<option value='" . $r ['id'] . "' $selected >--请选择--</option>";	
			}else{
				$option .= "<option value='" . $r ['id'] . "' $selected>" . $name . "</option>";
			}
            
            if (count($r ['sub_tree']) > 0) {
                $option .= self::genOptionHtml($r ['sub_tree'], $deepIdx + 1, $defOpt, $this_id);
            }
        }
        return $option;
    }

    /**
     * 生成用于列表显示的分类树结构
     *
     * @param undefined $rows        	
     *
     */
    public static function genDisplayTreeList($rows, $deepIdx = 0) {
        $displayData = array();
        $se = self::genLayerDiv($deepIdx);
        foreach ($rows as $r) {
            $tmp = array(
                'id' => $r ['id'],
				'domain'=> $r['domain'],
                'name' => $r ['is_child'] ? $se . $r ['name'] : $r ['name'],
                'pid' => $r ['pid'],
                'is_child' => $r ['is_child'],
                'display' => $r ['display'],
                'sorting' => $r ['sorting']
            );
            $displayData [] = $tmp;
            if (count($r ['sub_tree']) > 0) {
                $displayData = array_merge($displayData, self::genDisplayTreeList($r ['sub_tree'], $deepIdx + 1));
            }
        }
        return $displayData;
    }

    /**
     * 依据当前登录账户的角色权限生成相应的导航菜单
     * 
     * @param undefined $rows        	
     *
     */
    public static function genRoleMenu($rows = NULL) {
        $html = '';
        if ($rows) {
            foreach ($rows as $r) {
                if (!$r ['is_menu']) {
                    continue;
                }
                if ($r ['parent_id'] == 0) {
                    if (count($r ['sub_tree']) > 0) {
                        $html .= '<a href="#' . $r ["act_key"] . '-menu" class="nav-header" data-toggle="collapse"><i class="' . $r ["acl_icon"] . '"></i>' . $r ["acl_name"] . '<i class="icon-chevron-up"></i></a>';
                        $html .= '<ul id="' . $r ["act_key"] . '-menu" class="nav nav-list collapse">';
                        $html .= self::genRoleMenu($r ['sub_tree']);
                        $html .= "</ul>";
                    }
                } else {
					$atid = substr(md5($r ['acl_id']),10);
                    $html .= '<li><a href="' . $r ['acl_url'] . '" id='.$atid.'>' . $r ['acl_name'] . '</a></li>';
                }
            }
        }
        return $html;
    }

    /**
     * 生成用于列表显示的操作树结构
     *
     * @param undefined $rows        	
     * @return array()
     *
     */
    public static function genAccessTreeList($rows, $deepIdx = 0) {
        $displayData = array();
        $se = self::genLayerDiv($deepIdx);
        foreach ($rows as $r) {
            $tmp = array(
                'acl_id' => $r ['acl_id'],
                'acl_name' => $r ['parent_id'] ? $se . $r ['acl_name'] : $r ['acl_name'],
                'acl_url' => $r ['acl_url'],
                'act_key' => $r ['act_key'],
                'is_menu' => $r ['is_menu'],
                'parent_id' => $r ['parent_id'],
                'is_child' => $r ['is_child'],
                'acl_sorting' => $r ['acl_sorting']
            );
            $displayData [] = $tmp;
            if (count($r ['sub_tree']) > 0) {
                $displayData = array_merge($displayData, self::genAccessTreeList($r ['sub_tree'], $deepIdx + 1));
            }
        }
        return $displayData;
    }

    /**
     * 依据层级生成对应的分割符
     *
     * @param $deepIdx 当前层级        	
     * @return string 分割符
     */
    public static function genLayerDiv($deepIdx = 0) {
        $div = "";
        if ($deepIdx > 0) {
            for ($i = 0; $i < $deepIdx; $i++) {
                $div .= "|-- ";
            }
        }
        return $div;
    } 
}    
    
?>