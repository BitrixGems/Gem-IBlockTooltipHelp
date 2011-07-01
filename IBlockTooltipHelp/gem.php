<?php
/**
 * Подключаем jQuery :)
 *
 * @author		Vladimir Savenkov <iVariable@gmail.com>
 *
 */
class BitrixGem_IBlockTooltipHelp extends BaseBitrixGem{

	protected $aGemInfo = array(
		'GEM'			=> 'IBlockTooltipHelp',
		'AUTHOR'		=> 'Владимир Савенков',
		'AUTHOR_LINK'	=> 'http://bitrixgems.ru',
		'DATE'			=> '27.04.2011',
		'VERSION'		=> '0.1',
		'NAME' 			=> 'Всплывающие подсказки (IBlockTooltipHelp)',
		'DESCRIPTION' 	=> "Всплывающие подсказки, для свойств инфоблока. Позволяют указать произвольное описание для свойств инфоблока и это описание выводится при редактировании и создании элементов инфоблока.",
		'DESCRIPTION_FULL' => '',
		'CHANGELOG'		=> 'Релизная версия',
		'REQUIREMENTS'	=> 'jQuery',
		'REQUIRED_MODULES' => array('iblock'),
		'REQUIRED_GEMS'		=> array('BitrixURLTools'),
		'REQUIRED_MIN_MODULE_VERSION' => '1.2.0',
	);
	
	public function initGem(){
		if( defined( 'ADMIN_SECTION' ) ){
			AddEventHandler(
					'main',
					'OnProlog',
					array( $this , 'initTooltips')
			);
		}
	}
	
	public function processAjaxRequest( $aOptions ){
		$mResult = array();
		if( !isset( $aOptions['iIBlockID'] ) ) return '';
		if( isset( $aOptions['action'] ) ){
			switch( $aOptions['action'] ){
				
				case "getHelpers":
					$mResult = $this->getHelpers( $aOptions['iIBlockID'] );
					$mResult = json_encode( $mResult );
					break;
					
				case "getEditorWindow":
					if( isset( $aOptions['iID'] ) ){
						$mResult = $this->getEditorWindow( $aOptions['iIBlockID'], $aOptions['iID'] );
						$mResult = json_encode( $mResult );
					}
					break;
					
				case "saveHelper":
					if( isset( $aOptions['iID'] ) ){
						$bResult = $this->saveHelper( $aOptions['iIBlockID'],  $aOptions['iID'], $aOptions['hint'], $aOptions['tooltipTitle'], $aOptions['tooltipHint'] );
						if( $bResult ){
							$mResult = 'Подсказка успешно сохранена!';
						}else{
							$mResult = 'Ошибка при сохранении подсказки :( Свяжитесь с разработчиком.';
						};
					}else{
						$mResult = 'Переданы не все параметры!';
					}
					break;
				
				case "removeHelper":
					if( isset( $aOptions['iID'] ) ){
						$bResult = $this->removeHelper( $aOptions['iIBlockID'], $aOptions['iID'] );
						if( $bResult ){
							$mResult = 'Подсказка успешно удалена!';
						}else{
							$mResult = 'Ошибка при удалении подсказки :( Свяжитесь с разработчиком.';
						};
					}else{
						$mResult = 'Переданы не все параметры!';
					}
					break;
				
			}
		}
		return $mResult;
	}
	
	protected function getEditorWindow( $iIBlockID, $iID ){
		$aHelper = $this->getHelpers( $iIBlockID, $iID );
		if( !$aHelper ){
			$aHelper = array(
				'elementID' 	=> $iID,
				'hint'			=> '',
				'tooltipTitle'	=> '',
				'tooltipHint'	=> '',
			);
		}
		$mResult = array(
			'title' => 'Управление подсказкой',
			'head' => 'Подсказка для '.$aHelper['elementID'],
			'content' => '
				<form class="BG_IBlockTooltipHelper_actions" rel="_BG_IBlockTooltipHelper_'.$aHelper['elementID'].'" method="POST" style="overflow:hidden;" action="/bitrix/admin/bitrixgems_simpleresponder.php?gem=IBlockTooltipHelp&AJAXREQUEST=Y" >
					<input type="hidden" name="iID" value="'.$aHelper['elementID'].'" />
					<input type="hidden" name="iIBlockID" value="'.$iIBlockID.'" />
					Инлайн-подсказка: <br/><input type="text" name="hint" style="width: 474px;" value="'.htmlspecialchars( $aHelper['hint'] ).'" /><hr />
					Заголовок всплывающей подсказки: <br/><input type="text" name="tooltipTitle" value="'.htmlspecialchars( $aHelper['tooltipTitle'] ).'" style="width: 474px;"><br />
					Всплывающая подсказка (можно использовать html):
					<textarea name="tooltipHint" style="height: 128px; width: 474px;">'.htmlspecialchars( $aHelper['tooltipHint'] ).'</textarea>
					'.bitrix_sessid_post().'
				</form>
			',
		);
		return $mResult;
	}
	
	public function initTooltips(){
		global $APPLICATION, $USER;
		
		$APPLICATION->AddHeadScript('/bitrix/js/iv.bitrixgems/IBlockTooltipHelp/IBlockTooltipHelp.gem.js');
		
		$APPLICATION->AddHeadString(
				'<style type="text/css">
				.BG_IBlockTooltipHelp-inline-help {
					font-size:80%;
					color: #AAA;
				}
				.BG_IBlockTooltipHelp-tooltip-help {}
				</style>
				<script type="text/javascript">
				if( typeof jQuery != "undefined" ){
					jQuery(function(){
						var BG_IBlockTooltipHelper = new IBlockTooltipHelpGem( new BitrixURLTools(), '.(($this->canUserEditHints( $USER ))?'true':'false').' );
						jQuery(window).data("BG_IBlockTooltipHelper", BG_IBlockTooltipHelper );
					})
				}
				</script>
				'
		);
		CAjax::Init(); //для jsAjaxUtil
	}
	
	protected function getDefaultOptions(){
		return array(
			'aEditorsUserGroups' => array(
				'name' => 'Группы пользователей, которым разрешено создание/редактирование хинтов',
				'type' => 'select|usergroup',
				"multiple" => true,
				'value' => array(),
			),
		);
	}
	
	public function needAdminPage(){
		return true;
	}
	
	public function canUserEditHints( CUser $oUser ){
		$aAllowedUG = $this->getEditors();
		$aUG = CUser::GetUserGroup( $oUser->GetID() );
		$aIntersection = array_intersect( $aUG, $aAllowedUG );
		return !empty($aIntersection);
	}
	
	public function getEditors(){
		$aOptions = $this->getOptions();
		return $aOptions['aEditorsUserGroups'];
	}
	
	public function removeHelper( $iIBlockID, $sHelperID ){
		$aHelpers = @include( $this->getGemFolder().'/options/helpers.php' );
		unset( $aHelpers[$iIBlockID][ $sHelperID ] );
		return file_put_contents( $this->getGemFolder().'/options/helpers.php', '<?php return '.var_export( $aHelpers, true ).'?>' );
	}
	
	public function saveHelper( $iIBlockID, $sHelperID, $sHint = '', $sTooltipTitle = '', $sTooltipHint = '' ){
		$aHelpers = @include( $this->getGemFolder().'/options/helpers.php' );
		if( !$aHelpers ) $aHelpers = array();
		if( !isset( $aHelpers[$iIBlockID] ) ) $aHelpers[$iIBlockID] = array();
		$aHelpers[$iIBlockID][ $sHelperID ] = array(
			'elementID' 	=> (string)$sHelperID,
			'hint'			=> (string)$sHint,
			'tooltipTitle'	=> (string)$sTooltipTitle,
			'tooltipHint'	=> (string)$sTooltipHint,
		);
		return file_put_contents( $this->getGemFolder().'/options/helpers.php', '<?php return '.var_export( $aHelpers, true ).'?>' );
	}
	
	public function getHelpers( $iIBlockID = null, $sHelperID = null ){
		$aHelpers = @include( $this->getGemFolder().'/options/helpers.php' );
		
		if( !$aHelpers ) $aHelpers = array();
		if( $iIBlockID ) $aHelpers = $aHelpers[ $iIBlockID ];
		if( $sHelperID ) $aHelpers = $aHelpers[ $sHelperID ];
		return $aHelpers;
	}
	
}