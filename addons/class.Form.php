<?php

class i8_Form {

	var $namespace = 'options';

	function create($options)
	{
		$defaults = array(
			'form'		=> 'wp',
			'method'	=> 'post',
			'action'	=> 'options.php',
			'submit'	=> 'Submit'
		);
		$options = wp_parse_args($options, $defaults);
		
		if ('wp' == $options['form'])
			$this->form_table($options);
	}
	
	
	function build_postboxes(&$fieldsets)
	{	
		foreach ($fieldsets as $title => $internals) 
			echo $this->postbox($title, $internals);
	}
	
	
	function postbox($title, $internals)
	{
		ob_start(); 
				
		?><div class="postbox" id="<?php echo sanitize_with_underscores($title); ?>">
        	<div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><span><?php echo $title; ?></span></h3>
        	<div class="inside">
            <table class="form-table">
        	<tbody>
            <?php foreach ($internals as $row_title => $set) : //$this->normalize($set) ?>
            <tr valign="top" master="<?php echo $set['name']; ?>" <?php 
				if ($set['master']) 			
					echo 'slave="'.join('|', (array)$set['master']).'" style="display:none"';
			?>>
            	<?php if (!$set['notitle']) : ?> 
            	<th scope="row">
                	<strong><?php echo $row_title; ?></strong> 
                    <?php if (!empty($set['description'])) : ?>
                    	<p><em><?php echo $set['description']; ?></em></p>
					<?php endif; ?>
                </th>
                <td>
                <?php else : ?>
                <td colspan="2">
                	<?php if ($set['description']) : ?>
                    <em><?php echo $set['description']; ?></em>
                    <?php endif; ?>
                
				<?php endif; ?>
                
                	<fieldset class="type-<?php echo $set['type']; ?> for-<?php echo $set['name']; ?>">
            		<legend class="screen-reader-text"><span><?php echo $row_title; ?></span></legend>
                    <?php echo $this->field($set); ?>
                    </fieldset>
                </td>
	        <?php endforeach; ?>
            </tbody>
            </table>
            </div>
        </div><?php
		
		return ob_get_clean();
	}
	
	
	function form_table(&$options)
	{	
		extract($options);
	
		?><form method="<?php echo $method; ?>" action="<?php echo $action; ?>">
        <table class="form-table">
		<?php foreach ($fields as $field): $this->normalize($field); ?>
        <tr valign="top">
        	<th scope="row"><label><?php echo $field['label']; ?></label></th>
            <td>
            	<?php echo $this->field($field); ?> <?php echo $field['description']; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </table> 
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php echo $submit; ?>" />
        </p>
    
        </form><?php
	
	}
	
	
	function field(&$field)
	{	
            $func = $field['type'];
            if (method_exists($this->plugin, $func))
                echo $this->plugin->$func($field);
            else
            {
                if (method_exists($this, $func))
                    echo $this->$func($field);
            }
	}
	
	
	function text(&$field)
	{		
            ob_start();
	?><input id="<?php echo $field['name']; ?>" type="<?php echo $field['type']; ?>" name="<?php echo $field['name']; ?>" value="<?php echo $this->options[$field['name']]; ?>" class="<?php echo !empty($field['class']) ? $field['class'] : 'text'; ?>" <?php if (!empty($field['style'])) echo 'style="'.$field['style'].'"'; ?> /><?php
            return ob_get_clean();
	}
	
	
	function file(&$field)
	{
            return $this->text($field);
	}
	
	
	function password(&$field)
	{
            return $this->text($field);
	}


        function select(&$field)
        {
            ob_start();

            if (!empty($field['items'])) :
            ?><select name="<?php echo $field['name']; ?>" class="<?php echo !empty($field['class']) ? $field['class'] : 'select'; ?>"><?php

            foreach ($field['items'] as $value => $label) :

                ?><option value="<?php echo $value; ?>" <?php if ($value == $this->options[$field['name']]) echo 'selected="selected"'; ?>><?php echo $label; ?></option><?php

            endforeach;


            ?></select><?php
            endif;
            return ob_get_clean();
        }


        function textarea(&$field)
        {
            ob_start();
            ?><textarea name="<?php echo $field['name']; ?>" class="<?php echo $field['class']; ?> textarea"><?php echo $this->options[$field['name']]; ?></textarea><?php
            return ob_get_clean();
        }

	
	function normalize(&$params)
	{
		$defaults = array(
			'type' 		=> 'text',
			'class'		=> 'regular-text',
			'style'		=> '',
			'value'		=> '',
			'label'		=> 'Default Label',
			'required'	=> false
		);
	
		if ( is_string($params) ) 
			$params = array(
				'label' => $params,
				'name'	=> sanitize_with_underscores($params)
			);
			
		if (empty($params['name']))
			$params['name'] = sanitize_with_underscores($params['label']);
			
		return wp_parse_args($params, $defaults);
	}
	
	
	function widget_options(&$params)
	{
		if (!class_exists('PP_Pro'))
			return;
	
		$class_name = $this->options['wp_widget_0'];
		$options = $this->options[$params['name']];
		
		ob_start();
		$this->plugin->ctrls['popupspro']->widget_control($class_name, $options);
		$form = ob_get_clean();
	
		?><div id="widget_options"><?php echo $form; ?></div>
        <script>
			(function($) {
				
				var widgetOptions = $('.for-popup_source select[name=wp_widget_0]'),
					loader = $('<img src="<?php echo $this->plugin->url; ?>/imgs/ajax-loader.gif" style="display:none" />')
								.insertAfter(widgetOptions);
				
				widgetOptions.change(function() 
				{	
					if ($.trim(widgetOptions.val()) == '')
					{
						$('#widget_options').empty();
						return;
					}
				
					loader.show();
					
					$.post(ajaxurl, {
							widget_class: widgetOptions.val(),
							action: 'pp_widget_control',
							_wpnonce: '<?php echo wp_create_nonce('pp_widget_control'); ?>' 
						}, function(data) {
							var control = $(data);
							if ($.trim(data) != '')
								$('#widget_options')
									.html(control); 
																
							loader.hide();	
						}, 'html'
					);
				});
				
			})(jQuery);
			
		</script><?php
	}
	
	function advanced_options(&$params)
	{
		if (!class_exists('PP_Pro'))
			return;
	
		list($master, $select) = explode('::', $params['master']);
		$select .= '_0';
		
		ob_start();
		$this->plugin->ctrls['popupspro']->effect_options(array(
				'type' => $this->options[$select], 
				'name' => $params['name']
			),
			$this->options[$params['name']]
		);
		$form = ob_get_clean();
	
		?><div id="<?php echo $params['name']; ?>"><?php echo $form; ?></div>
        <script>
			(function($) {
				
				var optionsEl = $('select[name=<?php echo $select ?>]'),
					loader = $('<img src="<?php echo $this->plugin->url; ?>/imgs/ajax-loader.gif" style="display:none" />')
								.insertAfter(optionsEl);
				
				optionsEl.change(function() 
				{	
					if ($.trim(optionsEl.val()) == '')
					{
						$('#<?php echo $params['name']; ?>').empty();
						return;
					}
				
					loader.show();
					
					$.post(ajaxurl, {
							type: optionsEl.val(),
							name: '<?php echo $params['name']; ?>',
							action: 'pp_effect_options',
							_wpnonce: '<?php echo wp_create_nonce('pp_effect_options'); ?>' 
						}, function(data) {
							var control = $(data);
							if ($.trim(data) != '')
								$('#<?php echo $params['name']; ?>')
									.html(control); 
																
							loader.hide();	
						}, 'html'
					);
				});
				
			})(jQuery);
			
		</script><?php
	}
	
	
	function editor(&$params)
	{
		?>
        <div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
        <?php
		
		$content = $this->options['content']; 		
		$id = 'content'; 
		$prev_id = 'title'; 
		$media_buttons = true; 
		$tab_index = 2;
	
		$rows = get_option('default_post_edit_rows');
		if (($rows < 3) || ($rows > 100))
			$rows = 12;
	
		if ( !current_user_can( 'upload_files' ) )
			$media_buttons = false;
	
		$richedit =  user_can_richedit();
		$class = '';
	
		if ( $richedit || $media_buttons ) { ?>
		<div id="editor-toolbar">
	<?php
		if ( $richedit ) {
			$wp_default_editor = wp_default_editor(); ?>
			<div class="zerosize"><input accesskey="e" type="button" onclick="switchEditors.go('<?php echo $id; ?>')" /></div>
	<?php	if ( 'html' == $wp_default_editor ) {
				add_filter('the_editor_content', 'wp_htmledit_pre'); ?>
				<a id="edButtonHTML" class="active hide-if-no-js" onclick="switchEditors.go('<?php echo $id; ?>', 'html');"><?php _e('HTML'); ?></a>
				<a id="edButtonPreview" class="hide-if-no-js" onclick="switchEditors.go('<?php echo $id; ?>', 'tinymce');"><?php _e('Visual'); ?></a>
	<?php	} else {
				$class = " class='theEditor'";
				add_filter('the_editor_content', 'wp_richedit_pre'); ?>
				<a id="edButtonHTML" class="hide-if-no-js" onclick="switchEditors.go('<?php echo $id; ?>', 'html');"><?php _e('HTML'); ?></a>
				<a id="edButtonPreview" class="active hide-if-no-js" onclick="switchEditors.go('<?php echo $id; ?>', 'tinymce');"><?php _e('Visual'); ?></a>
	<?php	}
		}
	
		if ( $media_buttons ) { ?>
			<div id="media-buttons" class="hide-if-no-js">
	<?php	do_action( 'media_buttons' ); ?>
			</div>
	<?php
		} ?>
		</div>
	<?php
		}
	?>
		<div id="quicktags"><?php
		wp_enqueue_script('quicktags'); ?>
		<script type="text/javascript">edToolbar()</script>
		</div>
	
	<?php
		$the_editor = apply_filters('the_editor', "<div id='editorcontainer'><textarea rows='$rows'$class cols='40' name='$id' tabindex='$tab_index' id='$id'>%s</textarea></div>\n");
		$the_editor_content = apply_filters('the_editor_content', $content);
	
		printf($the_editor, $the_editor_content);
	
	?>
		<script type="text/javascript">
		edCanvas = document.getElementById('<?php echo $id; ?>');
		</script>
 
        <table id="post-status-info" cellspacing="0"><tbody><tr>
            <td colspan="2" height="22"> </td>
        </tr></tbody></table>
        </div>
        
        <?php if (!file_exists(WP_PLUGIN_DIR . '/tinymce-advanced/tinymce-advanced.php')) : ?>
		<em>Consider installing <a href="http://wordpress.org/extend/plugins/tinymce-advanced/" target="_blank">TinyMCE Advanced</a> plugin, which will add 15 additional features to the default WordPress Rich Text Editor: Advanced HR, Advanced Image, Advanced Link, Context Menu, Emoticons (Smilies), Date and Time, IESpell, Layer, Nonbreaking, Print, Search and Replace, Style, Table, Visual Characters and XHTML Extras.</em>
        <?php endif;
		
	}
	
	
	function reset_counters(&$params)
	{
		static $counter = 0;
		if ($counter) return; // is meant to be called only once
			
		?><input type="checkbox" name="<?php echo $params['name']; ?>[]" value="1" <?php if ($this->options[$params['name']][0]) echo 'checked="checked"'; ?> /> Check this, if you want <b>Visitor Counters</b> to be <b>Reset</b> for this Popup whenever it updates, or <b>Reset</b> them <b>manually</b> 
        <input type="button" class="button" value="now!" onclick="pp_reset_counters(this)" <?php if (!is_numeric($this->options['popup_id'])) echo 'disabled="disabled"'; ?> /> <img src="<?php echo $this->url . '/imgs/ajax-loader.gif'; ?>" style="display:none" /> <em>(<b>Note:</b> page won't refresh!)</em>
        <script>
			function pp_reset_counters(b)
			{
				var loader = jQuery(b).next('img');
				
				loader.show();
				jQuery(b).attr('disabled', 'disabled');
				
				jQuery.post(ajaxurl, {
						popup_id: '<?php echo $this->options['popup_id']; ?>',
						action: 'pp_reset_counters',
						_wpnonce: '<?php echo wp_create_nonce('pp_reset_counters'); ?>'
					}, function (r) {
						jQuery(b).removeAttr('disabled');
						loader.hide();
					}, 'json'
				);
			}
		</script>
		<?php 
		$counter++;
	}
	
	
	function radiogroup(&$params)
	{
		foreach ($params['items'] as $value => $label) : ?>
        <span>
            <input type="radio" name="<?php echo $params['name']; ?>" value="<?php echo $value; ?>" <?php 
                if ($value == $this->options[$params['name']]) echo 'checked="checked"'; 
            ?> <?php if (in_array($value, (array)$this->plugin->disabled)) echo 'disabled="disabled"'; ?> />
       
			<?php echo $params['tokens'] ? $this->replace_tokens($label, $value, $this->options[$params['name']], true) : $label; ?>
            <br /> 
        </span> 
        <?php endforeach; 
	}
	
	function checkgroup(&$params)
	{
		$i = 0;
		foreach ($params['items'] as $value => $label) :  ?>
        <span>
            <input type="checkbox" name="<?php echo $params['name']; ?>[<?php echo $i; ?>]" value="<?php echo $value; ?>" <?php 
                if ($this->options[$params['name']][$i]) echo 'checked="checked"'; 
            ?> <?php if (in_array($value, (array)$this->plugin->disabled)) echo 'disabled="disabled"'; ?> />
			<?php echo $params['tokens'] ? $this->replace_tokens($label, $value, $this->options[$params['name']][$i]) : $label; ?>
            <br /> 
        </span>
        <?php $i++; endforeach; 
	}
	
	
	function replace_tokens($label, $prefix, $value = null, $radiogroup = false)
	{	
		$offset = $counter = 0;
		
		while (preg_match('#\{([^\}]*)\}#', $label, $matches, PREG_OFFSET_CAPTURE, $offset)) :
		
			# increment offset pointer
			$offset = $matches[1][1] + strlen($matches[1][0]);
			
			# select token needs special care
			if (preg_match('|select options="([^"]+)"|', $matches[1][0], $options)) {
				$token = 'select';
				$options = explode('|', $options[1]);
			} else
				$token = $matches[1][0];
			
			# name for in-label-inputs	
			$name = $prefix . '_' . $counter;
			
			$disabled = false;
			if ($radiogroup && $value != $prefix || !$radiogroup && empty($value))
				$disabled = true;
					
			# define replacement	
			switch ($token):
			
			case 'small-text':
			case 'medium-text':
			case 'regular-text':
				$replace = '<input type="text" class="'.$token.' in-label-input" name="'.$name.'" value="'.$this->options[$name].'" '.($disabled ? 'disabled="disabled"' : '') .'/>';
				break;
				
			case 'date-field':
				$replace = '<input type="text" class="date-field in-label-input" name="'.$name.'" value="'.$this->options[$name].'" '.($disabled ? 'disabled="disabled"' : '') .'/> <em>(mm/dd/yyyy)</em>';
				break;
				
			case 'color-picker':
				$replace = '<input type="text" class="color-picker in-label-input" name="'.$name.'" value="'.$this->options[$name].'"  '.($disabled ? 'disabled="disabled"' : '') .'/>';
				break;
				
			case 'select':
				ob_start(); 				
				?><select class="in-label-input" name="<?php echo $name; ?>" <?php if ($disabled) echo 'disabled="disabled"'; ?>>
				<?php foreach ($options as $item) : ?>
					<option value="<?php echo $item; ?>" <?php if (!strcasecmp($item, $this->options[$name])) echo 'selected="selected"'; ?>>
						<?php echo $item; ?>
					</option>
				<?php endforeach; ?>
				</select><?php 
				$replace = ob_get_clean();
				break;
				
			case 'selectwidgets':
			
				ob_start();
				global $wp_registered_widgets;

				$sort = $wp_registered_widgets;
				usort( $sort, create_function( '$a, $b', 'return strnatcasecmp( $a["name"], $b["name"] );' ) );
				
				$done = array();

				?><select class="in-label-input" name="<?php echo $name; ?>" <?php if ($disabled) echo 'disabled="disabled"'; ?>>
					<option value="">Choose...</option>
				<?php foreach ( $sort as $widget ) {
					
					$class_name = get_class($widget['callback'][0]);
					if ( in_array( $class_name, $done, true ) || !class_exists($class_name)) // We already showed this multi-widget
						continue;
			
					$done[] = $class_name;
			
					?><option value="<?php echo $class_name; ?>" <?php if ($class_name == $this->options[$name]) echo 'selected="selected"'; ?>><?php echo $widget['name']; ?></option><?php

				} ?>
                </select>
                <?php
				$replace = ob_get_clean();
				break;
				
			case 'checkbox':
				ob_start();
				?><input type="checkbox" class="in-label-input" name="<?php echo $name; ?>" value="1" <?php if ($this->options[$name]) echo 'checked="checked"';  if($disabled) echo 'disabled="disabled"'; ?>/><?php
				$replace = ob_get_clean();
				break;
				
			case 'popup_id':
				$replace = is_numeric($this->options['popup_id']) ? $this->options['popup_id'] : '{popup_id}';
				break;
				
			case 'theme_name':
				$replace = get_current_theme();
				break;
			
			default:
				continue 2;
			endswitch;
			
			# do a replacement
			$label = substr_replace($label, $replace, $matches[0][1], strlen($matches[0][0]));
			$counter++;
			
		endwhile;
		
		return $label;
	}
	
	
	function textstack(&$params)
	{
		if (!empty($this->options[$params['name']]))
			foreach ((array)$this->options[$params['name']] as $url)  
	{ ?>
    	<div class="textstack-item textstack-item-old">
            <input type="text" name="<?php echo $params['name']; ?>[]" value="<?php echo $url; ?>" class="big-text" />  
            <span class="textstack-delete textstack-action"><!-- do --></span>
		</div><?php }
	
	  ?><div class="textstack-item textstack-item-new">
            <input type="text" name="<?php echo $params['name']; ?>[]" class="big-text" />  
            <span class="textstack-add textstack-action"><!-- do --></span> 
            <span class="textstack-delete textstack-action" style="display:none;"><!-- do --></span>
        </div>
        <div><em>Use the '*' wildcard to represent any possible text or continuation at the end of a URL,<br /> e.g. http://www.yourwebsite.com/category/*</em></div>  
		<script>
			jQuery('.for-<?php echo $params['name']; ?>')
				.click(function(e) {
					var me = jQuery(e.target);
					
					if (!me.is('.textstack-add, .textstack-delete'))
						return true;
						
					if (me.is('.textstack-delete')) 
						me.parent('.textstack-item')
							.fadeOut('fast', function() { jQuery(this).remove(); });
							
					if (me.is('.textstack-add'))
					{
						var newItem = me.parent(),
							clone = newItem.clone(true);
							
						clone
							.find('input:text')
								.val('')
								.end()
							.hide()
							.insertAfter(newItem)
							.fadeIn('fast');
							
						newItem
							.removeClass('textstack-item-new')
							.addClass('textstack-item-old')
							.find('.textstack-add')
								.hide()
								.end()
							.find('.textstack-delete')
								.show()
								.end();
					}
					
				}
			);
		</script><?php
	}
	
	
	function iframe(&$params)
	{
		$height = intval($params['height']);
		if (empty($height))
			$height = 100;
	
		?><iframe src="<?php echo $params['url']; ?>" style="width:100%;height:<?php echo $height; ?>px" frameborder="0"></iframe><?php 
	}
	
	
	function print_errors($wp_error)
	{
		if ( $wp_error->get_error_code() ) {
			$errors = '';
			$messages = '';
			

			foreach ( $wp_error->errors as $code => $error ) {
				if ( 'message' == $code )
					$messages .= '<p>' . join("</p><p>", $error) . '</p>';
				else
					$errors .= '<p>' . join("</p><p>", $error) . '</p>';
			}
			if ( !empty($errors) )
				echo '<div class="error">' . $errors . "</div>\n";
			if ( !empty($messages) )
				echo '<div class="updated">' . $messages . "</div>\n";
		}
	}

}

?>