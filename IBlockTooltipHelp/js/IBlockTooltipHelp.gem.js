function IBlockTooltipHelpGem( URLTools, editable ){
	
	this.editable = !!editable;
	this.URLTools = URLTools;
	this.backendURL = '/bitrix/admin/bitrixgems_simpleresponder.php?gem=IBlockTooltipHelp&AJAXREQUEST=Y';	
	this.hints = [];
	
	this.drawHelpers4IBlock = function( iIBlockID ){
		var helperManager = this;
		helperManager.prepareFields();
		$.getJSON(
			this.backendURL,
			{ 
				"action": "getHelpers",
				"iIBlockID": iIBlockID		 
			},
			function( helpers ){				
				for( i in helpers ){
					if( helpers.hasOwnProperty( i ) ){
						helperManager.drawHelper( helpers[i], i )
					}
				}
				
				if( helperManager.editable ){
					helperManager.drawEditTools();
				}					
			}  
		)
		
	}	
	
	this.prepareFields = function(){
		$('.edit-table TR:not(.heading) TD.field-name').each( function(){
			var previousHTML = $(this).html();
			$(this).data( 'previousHTML', previousHTML );			
		});
	}
	
	this.removeHelpers = function(){
		$('.edit-table TR:not(.heading) TD.field-name').each( function(){
			var previousHTML = $(this).data( 'previousHTML' );
			if( previousHTML ){
				 $(this).html( previousHTML );
			}
		});
	}
	
	this.drawHelper = function( helper, index ){
		if (helper.tooltipHint) {
			var previousHTML = $('#'+helper.elementID+' TD.field-name').html();
			$('#'+helper.elementID+' TD.field-name').html( previousHTML.substr(0, previousHTML.length-1)+' (<a href="#" id="'+helper.elementID+'_BG_TOOLTIP" class="BG_IBlockTooltipHelp-tooltip-help">?</a>):' );
			
			var hint = new BX.CHint({
				parent: document.getElementById(helper.elementID+'_BG_TOOLTIP'),
				hint: helper.tooltipHint || '',
				title: helper.tooltipTitle || '',
				show_timeout: 0,				
				preventHide: true
			});
			
			this.hints.push( hint );
		}
		if(helper.hint){
			$('#'+helper.elementID+' TD.field-name').append( '<br /><span class="BG_IBlockTooltipHelp-inline-help">'+helper.hint+'</span>' );
		}	
	}
	
	this.showEditWindow = function( iIBlockID, iID ){
		$.getJSON(
			this.backendURL,
			{ 
				"action": "getEditorWindow",
				"iIBlockID": iIBlockID,
				"iID": iID		 
			},
			function( form ){
				try {
					var editWindow = new BX.CDialog({
						title: form.title,
						head: form.head,
						content: form.content,
						icon: 'head-block',
						resizable: true,
						draggable: true,
						height: '338',
						width: '500',
						buttons: ['<input type="button" value="Сохранить" onclick="$(\'form[rel=_BG_IBlockTooltipHelper_'+iID+']:visible\').append(\'<input type=\\\'hidden\\\' name=\\\'action\\\' value=\\\'saveHelper\\\'/>\').submit()" /><input type="button" value="Удалить" onclick="$(\'form[rel=_BG_IBlockTooltipHelper_'+iID+']:visible\').append(\'<input type=\\\'hidden\\\' name=\\\'action\\\' value=\\\'removeHelper\\\'/>\').submit()" />', BX.CDialog.btnCancel]
					});
					editWindow.Show();
					$('form[rel=_BG_IBlockTooltipHelper_'+iID+']:visible').data('editWindow', editWindow);
				}catch(e){
					alert('Ой! Какая то шляпа приключилась, обратитесь к разработчику Т_Т');
				}
			}
		);
	}
	
	this.drawEditTools = function(){
		var helperManager = this;
		var iIBlockID = this.URLTools.getIBlockID();
		$('.edit-table TR').not('.heading').each( function(){
			var $this = $(this);
			var id = $this.attr('id');
			if( !id ) return;
			$('<br /><span class="BG_IBlockTooltipHelp-edit-help">[<a href="#" onclick="$(window).data(\'BG_IBlockTooltipHelper\').showEditWindow( \''+iIBlockID+'\', \''+id+'\' );return false;">Управление подсказкой</a>]</span>').appendTo( $this.find('TD:first') );
		} )
	}
	
	this.bindFormsSubmit = function(){
		var helperManager = this;
		$('form.BG_IBlockTooltipHelper_actions').live(
			'submit',
			function(){
				var $form = $(this);		
				jsAjaxUtil.ShowLocalWaitWindow($form.get(0).id, $form.get(0), true);		
				$.post(
					helperManager.backendURL,
					$form.serializeArray(),
					function( data ){
						$form.data('editWindow').Hide();
						alert( data );						
						jsAjaxUtil.CloseLocalWaitWindow($form.get(0).id, $form.get(0));
						helperManager.removeHelpers();
						helperManager.drawHelpers4IBlock(helperManager.URLTools.getIBlockID());
					}
				);
				return false;				
			}
		);
	} 
	
	this.process = function(){		
		if (this.URLTools.isIBlockElementEditPage()) {
			this.drawHelpers4IBlock(this.URLTools.getIBlockID());
			this.bindFormsSubmit();							
		}		
	};
	
	this.process();
}
